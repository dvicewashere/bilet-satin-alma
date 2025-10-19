<?php
require_once 'config.php';

requireRole(['company']);

$db = getDB();
$message = '';

// Firma yöneticisinin firmasını al
$stmt = $db->prepare("SELECT company_id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$company_id = $user['company_id'];

// Firma detaylarını al
$stmt = $db->prepare("SELECT * FROM bus_companies WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch();


$edit_trip_id = $_GET['edit_trip'] ?? null;
$edit_trip_data = null;
if ($edit_trip_id) {
    $stmt = $db->prepare("SELECT * FROM trips WHERE id = ? AND company_id = ?");
    $stmt->execute([$edit_trip_id, $company_id]);
    $edit_trip_data = $stmt->fetch();
}

// Form gönderimlerini işle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_trip'])) {
        $departure_city = sanitize($_POST['departure_city']);
        $destination_city = sanitize($_POST['destination_city']);
        $departure_time = sanitize($_POST['departure_time']);
        $arrival_time = sanitize($_POST['arrival_time']);
        $price = (int)$_POST['price']; // Price in TL
        $capacity = (int)$_POST['capacity'];
        
        if (empty($departure_city) || empty($destination_city) || empty($departure_time) || empty($arrival_time) || $price <= 0 || $capacity <= 0) {
            $message = '<div class="alert alert-error">Tüm alanları doğru şekilde doldurun.</div>';
        } else {
            $trip_id = generateUUID();
            $stmt = $db->prepare("
                INSERT INTO trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$trip_id, $company_id, $departure_city, $destination_city, $departure_time, $arrival_time, $price, $capacity])) {
                $message = '<div class="alert alert-success">Sefer başarıyla eklendi.</div>';
            } else {
                $message = '<div class="alert alert-error">Sefer eklenirken bir hata oluştu.</div>';
            }
        }
    }
    
    if (isset($_POST['update_trip'])) {
        $trip_id = sanitize($_POST['trip_id']);
        $departure_city = sanitize($_POST['departure_city']);
        $destination_city = sanitize($_POST['destination_city']);
        $departure_time = sanitize($_POST['departure_time']);
        $arrival_time = sanitize($_POST['arrival_time']);
        $price = (int)$_POST['price'];
        $capacity = (int)$_POST['capacity'];
        
        $stmt = $db->prepare("
            UPDATE trips 
            SET departure_city = ?, destination_city = ?, departure_time = ?, arrival_time = ?, price = ?, capacity = ?
            WHERE id = ? AND company_id = ?
        ");
        
        if ($stmt->execute([$departure_city, $destination_city, $departure_time, $arrival_time, $price, $capacity, $trip_id, $company_id])) {
            $message = '<div class="alert alert-success">Sefer başarıyla güncellendi.</div>';
        } else {
            $message = '<div class="alert alert-error">Sefer güncellenirken bir hata oluştu.</div>';
        }
    }
    
    if (isset($_POST['delete_trip'])) {
        $trip_id = sanitize($_POST['trip_id']);
        
        // Seferin aktif biletleri var mı kontrol et
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM tickets t 
            JOIN trips tr ON t.trip_id = tr.id 
            WHERE tr.id = ? AND t.status = 'ACTIVE'
        ");
        $stmt->execute([$trip_id]);
        $active_tickets = $stmt->fetch()['count'];
        
        if ($active_tickets > 0) {
            $message = '<div class="alert alert-error">Bu seferde aktif biletler bulunduğu için silinemez.</div>';
        } else {
            $stmt = $db->prepare("DELETE FROM trips WHERE id = ? AND company_id = ?");
            if ($stmt->execute([$trip_id, $company_id])) {
                $message = '<div class="alert alert-success">Sefer başarıyla silindi.</div>';
            } else {
                $message = '<div class="alert alert-error">Sefer silinirken bir hata oluştu.</div>';
            }
        }
    }
}

// Firmanın seferlerini al
$stmt = $db->prepare("
    SELECT t.*, 
           COUNT(tk.id) as ticket_count,
           SUM(CASE WHEN tk.status = 'ACTIVE' THEN 1 ELSE 0 END) as active_tickets,
           SUM(CASE WHEN tk.status = 'ACTIVE' THEN tk.total_price ELSE 0 END) as revenue
    FROM trips t 
    LEFT JOIN tickets tk ON t.id = tk.trip_id 
    WHERE t.company_id = ? 
    GROUP BY t.id 
    ORDER BY t.departure_time DESC
");
$stmt->execute([$company_id]);
$trips = $stmt->fetchAll();


$stmt = $db->prepare("
    SELECT c.*, 
           COUNT(uc.id) as used_count
    FROM coupons c 
    LEFT JOIN user_coupons uc ON c.id = uc.coupon_id 
    WHERE c.company_id = ? 
    GROUP BY c.id 
    ORDER BY c.created_at DESC
");
try {
    $stmt->execute([$company_id]);
    $coupons = $stmt->fetchAll();
} catch (PDOException $e) {
 
    if (strpos($e->getMessage(), 'no such column: c.company_id') !== false || 
        strpos($e->getMessage(), 'no such column: discount') !== false) {
        
 
        $stmt = $db->query("PRAGMA table_info(coupons)");
        $columns = $stmt->fetchAll();
        
        $has_company_id = false;
        $has_discount_type = false;
        $has_old_discount = false;
        
        foreach ($columns as $column) {
            if ($column['name'] === 'company_id') $has_company_id = true;
            if ($column['name'] === 'discount_type') $has_discount_type = true;
            if ($column['name'] === 'discount') $has_old_discount = true;
        }
        
        // Eski yapı varsa mevcut verileri yedekle
        $existing_coupons = [];
        if ($has_old_discount) {
            $stmt = $db->query("SELECT * FROM coupons");
            $existing_coupons = $stmt->fetchAll();
        }
        
        // Tabloyu sil ve yeni yapıyla yeniden oluştur
        $db->exec("DROP TABLE IF EXISTS coupons");
        $db->exec("
            CREATE TABLE coupons (
                id TEXT PRIMARY KEY,
                company_id TEXT,
                code TEXT NOT NULL,
                discount_type TEXT NOT NULL CHECK(discount_type IN ('percentage', 'fixed')),
                discount_value REAL NOT NULL,
                min_amount REAL DEFAULT 0,
                max_uses INTEGER NOT NULL,
                expiry_date DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES bus_companies(id)
            )
        ");
        
      
        $stmt = $db->query("SELECT id FROM bus_companies LIMIT 1");
        $first_company = $stmt->fetch();
        $company_id_for_migration = $first_company ? $first_company['id'] : null;
        
 
        if (!empty($existing_coupons) && $company_id_for_migration) {
            foreach ($existing_coupons as $coupon) {
                $stmt = $db->prepare("
                    INSERT INTO coupons (id, company_id, code, discount_type, discount_value, min_amount, max_uses, expiry_date, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $coupon['id'],
                    $company_id_for_migration,
                    $coupon['code'],
                    'percentage',
                    $coupon['discount'] ?? 0,
                    0, 
                    $coupon['usage_limit'] ?? 1,
                    $coupon['expire_date'] ?? '2024-12-31 23:59:59',
                    $coupon['created_at'] ?? date('Y-m-d H:i:s')
                ]);
            }
        }
        
        // Sorguyu used_count ile tekrar dene
        $stmt = $db->prepare("
            SELECT c.*, 
                   COUNT(uc.id) as used_count
            FROM coupons c 
            LEFT JOIN user_coupons uc ON c.id = uc.coupon_id 
            WHERE c.company_id = ? 
            GROUP BY c.id 
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$company_id]);
        $coupons = $stmt->fetchAll();
    } else {
        throw $e;
    }
}

// Kupon yönetimini işle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code = sanitize($_POST['code']);
    $discount_type = sanitize($_POST['discount_type']);
    $discount_value = (float)$_POST['discount_value'];
    $min_amount = (float)$_POST['min_amount'];
    $max_uses = (int)$_POST['max_uses'];
    $expiry_date = sanitize($_POST['expiry_date']);
    
    if (empty($code) || empty($discount_type) || $discount_value <= 0 || $max_uses <= 0 || empty($expiry_date)) {
        $message = '<div class="alert alert-error">Tüm alanları doğru şekilde doldurun.</div>';
    } else {
        // Kupon kodu zaten var mı kontrol et
        $stmt = $db->prepare("SELECT id FROM coupons WHERE code = ?");
        $stmt->execute([$code]);
        if ($stmt->fetch()) {
            $message = '<div class="alert alert-error">Bu kupon kodu zaten kullanılıyor.</div>';
    } else {
        $coupon_id = generateUUID();
        $stmt = $db->prepare("
                INSERT INTO coupons (id, company_id, code, discount_type, discount_value, min_amount, max_uses, expiry_date, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
            if ($stmt->execute([$coupon_id, $company_id, $code, $discount_type, $discount_value, $min_amount, $max_uses, $expiry_date])) {
                $message = '<div class="alert alert-success">Kupon başarıyla oluşturuldu.</div>';
        } else {
                $message = '<div class="alert alert-error">Kupon oluşturulurken bir hata oluştu.</div>';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_coupon'])) {
    $coupon_id = sanitize($_POST['coupon_id']);
    $code = sanitize($_POST['code']);
    $discount_type = sanitize($_POST['discount_type']);
    $discount_value = (float)$_POST['discount_value'];
    $min_amount = (float)$_POST['min_amount'];
    $max_uses = (int)$_POST['max_uses'];
    $expiry_date = sanitize($_POST['expiry_date']);
    
    if (empty($code) || empty($discount_type) || $discount_value <= 0 || $max_uses <= 0 || empty($expiry_date)) {
        $message = '<div class="alert alert-error">Tüm alanları doğru şekilde doldurun.</div>';
    } else {
        // Kuponun bu firmaya ait olup olmadığını kontrol et
        $stmt = $db->prepare("SELECT id FROM coupons WHERE id = ? AND company_id = ?");
        $stmt->execute([$coupon_id, $company_id]);
        if (!$stmt->fetch()) {
            $message = '<div class="alert alert-error">Bu kuponu düzenleme yetkiniz yok.</div>';
        } else {
            // Kupon kodu zaten var mı kontrol et 
            $stmt = $db->prepare("SELECT id FROM coupons WHERE code = ? AND id != ?");
            $stmt->execute([$code, $coupon_id]);
            if ($stmt->fetch()) {
                $message = '<div class="alert alert-error">Bu kupon kodu zaten kullanılıyor.</div>';
            } else {
                $stmt = $db->prepare("
                    UPDATE coupons 
                    SET code = ?, discount_type = ?, discount_value = ?, min_amount = ?, max_uses = ?, expiry_date = ?
                    WHERE id = ? AND company_id = ?
                ");
                
                if ($stmt->execute([$code, $discount_type, $discount_value, $min_amount, $max_uses, $expiry_date, $coupon_id, $company_id])) {
                    $message = '<div class="alert alert-success">Kupon başarıyla güncellendi.</div>';
                } else {
                    $message = '<div class="alert alert-error">Kupon güncellenirken bir hata oluştu.</div>';
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_coupon'])) {
    $coupon_id = sanitize($_POST['coupon_id']);
    
    // Kuponun bu firmaya ait olup olmadığını kontrol et
    $stmt = $db->prepare("SELECT id FROM coupons WHERE id = ? AND company_id = ?");
    $stmt->execute([$coupon_id, $company_id]);
    if (!$stmt->fetch()) {
        $message = '<div class="alert alert-error">Bu kuponu silme yetkiniz yok.</div>';
    } else {
        $stmt = $db->prepare("DELETE FROM coupons WHERE id = ? AND company_id = ?");
        if ($stmt->execute([$coupon_id, $company_id])) {
            $message = '<div class="alert alert-success">Kupon başarıyla silindi.</div>';
        } else {
            $message = '<div class="alert alert-error">Kupon silinirken bir hata oluştu.</div>';
        }
    }
}

// Bilet iptalini işle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_ticket'])) {
    $ticket_id = sanitize($_POST['ticket_id']);
    
    // Firmanın bu bilete sahip olup olmadığını kontrol et
    $stmt = $db->prepare("
        SELECT t.*, tr.departure_time 
        FROM tickets t 
        JOIN trips tr ON t.trip_id = tr.id 
        WHERE t.id = ? AND tr.company_id = ? AND t.status = 'ACTIVE'
    ");
    $stmt->execute([$ticket_id, $company_id]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        $message = '<div class="alert alert-error">Bilet bulunamadı veya iptal edilemez.</div>';
    } else {
        // 1 saat kuralını kontrol et
        $departureDateTime = new DateTime($ticket['departure_time']);
        $currentDateTime = new DateTime();
        $timeDifference = $departureDateTime->getTimestamp() - $currentDateTime->getTimestamp();
        
        if ($timeDifference <= 3600) {
            $message = '<div class="alert alert-error">Kalkış saatinden 1 saatten az süre kaldığı için bilet iptal edilemez.</div>';
        } else {
            $stmt = $db->prepare("UPDATE tickets SET status = 'CANCELED' WHERE id = ?");
            if ($stmt->execute([$ticket_id])) {
                $message = '<div class="alert alert-success">Bilet başarıyla iptal edildi.</div>';
            } else {
                $message = '<div class="alert alert-error">Bilet iptal edilirken bir hata oluştu.</div>';
            }
        }
    }
}

// İstatistikleri al
$stats = [];

$stmt = $db->prepare("SELECT COUNT(*) as count FROM trips WHERE company_id = ?");
$stmt->execute([$company_id]);
$stats['total_trips'] = $stmt->fetch()['count'];

$stmt = $db->prepare("
    SELECT COUNT(*) as count FROM tickets tk 
    JOIN trips t ON tk.trip_id = t.id 
    WHERE t.company_id = ? AND tk.status = 'ACTIVE'
");
$stmt->execute([$company_id]);
$stats['active_tickets'] = $stmt->fetch()['count'];

$stmt = $db->prepare("
    SELECT SUM(tk.total_price) as total FROM tickets tk 
    JOIN trips t ON tk.trip_id = t.id 
    WHERE t.company_id = ? AND tk.status = 'ACTIVE'
");
$stmt->execute([$company_id]);
$stats['total_revenue'] = $stmt->fetch()['total'] ?: 0;

// Firmanın kuponlarını al
$stmt = $db->prepare("SELECT * FROM coupons ORDER BY created_at DESC");
$stmt->execute();
$coupons = $stmt->fetchAll();

$stmt = $db->prepare("
    SELECT t.*, tr.departure_city, tr.destination_city, tr.departure_time, tr.arrival_time, tr.price,
           u.full_name, u.email
    FROM tickets t
    JOIN trips tr ON t.trip_id = tr.id
    JOIN users u ON t.user_id = u.id
    WHERE tr.company_id = ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$company_id]);
$company_tickets = $stmt->fetchAll();

// Türkiye'nin 81 ili
$all_cities = [
    'Adana', 'Adıyaman', 'Afyonkarahisar', 'Ağrı', 'Aksaray', 'Amasya', 'Ankara', 'Antalya', 'Ardahan', 'Artvin',
    'Aydın', 'Balıkesir', 'Bartın', 'Batman', 'Bayburt', 'Bilecik', 'Bingöl', 'Bitlis', 'Bolu', 'Burdur',
    'Bursa', 'Çanakkale', 'Çankırı', 'Çorum', 'Denizli', 'Diyarbakır', 'Düzce', 'Edirne', 'Elazığ', 'Erzincan',
    'Erzurum', 'Eskişehir', 'Gaziantep', 'Giresun', 'Gümüşhane', 'Hakkari', 'Hatay', 'Iğdır', 'Isparta', 'İstanbul',
    'İzmir', 'Kahramanmaraş', 'Karabük', 'Karaman', 'Kars', 'Kastamonu', 'Kayseri', 'Kırıkkale', 'Kırklareli', 'Kırşehir',
    'Kilis', 'Kocaeli', 'Konya', 'Kütahya', 'Malatya', 'Manisa', 'Mardin', 'Mersin', 'Muğla', 'Muş',
    'Nevşehir', 'Niğde', 'Ordu', 'Osmaniye', 'Rize', 'Sakarya', 'Samsun', 'Siirt', 'Sinop', 'Sivas',
    'Şanlıurfa', 'Şırnak', 'Tekirdağ', 'Tokat', 'Trabzon', 'Tunceli', 'Uşak', 'Van', 'Yalova', 'Yozgat', 'Zonguldak'
];


?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Panel - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="css/style.css?v=3.9">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .company-header {
            background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .company-info {
            text-align: center;
        }
        .company-info h1 {
            margin-bottom: 0.5rem;
        }
        .company-info p {
            opacity: 0.9;
            margin: 0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #e22027;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: #666;
        }
        .trip-form {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .trip-form h3 {
            margin-bottom: 1.5rem;
            color: #333;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .trips-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .trips-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .trips-table th,
        .trips-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .trips-table th {
            background: #f8f9fa !important;
            font-weight: 600;
            color: #181818 !important;
            padding: 1rem !important;
            border-bottom: 2px solid #e22027 !important;
        }
        .trips-table td {
            color: #181818 !important;
            padding: 1rem !important;
            border-bottom: 1px solid #e9ecef !important;
        }
        .trips-table tbody tr:hover {
            background: #f8f9fa !important;
        }
        .trip-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            color: #ffffff !important;
            background: #e22027 !important;
            border: none !important;
            border-radius: 5px !important;
            font-weight: 500 !important;
        }
        .btn-sm:hover {
            background: #c41e3a !important;
            color: #ffffff !important;
        }
        .btn-danger {
            background: #dc3545 !important;
        }
        .btn-danger:hover {
            background: #c82333 !important;
        }
        
        /* Company Header Logo Styles */
        .company-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }
        
        .company-logo-section {
            display: flex;
            align-items: center;
        }
        
        .company-logo-header {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            object-fit: contain;
            background: white;
            padding: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .company-text-section {
            flex: 1;
            text-align: center;
        }
        
        .company-text-section h1 {
            margin: 0;
            color: white;
            font-size: 2.5rem;
            font-weight: bold;
        }
        
        .company-text-section p {
            margin: 0.5rem 0 0 0;
            color: rgba(255,255,255,0.9);
            font-size: 1.1rem;
        }
        
        /* Clean Beautiful Grid for Coupon Management */
        .coupon-management {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .coupon-management::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(102, 126, 234, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(118, 75, 162, 0.05) 0%, transparent 50%);
            opacity: 0.8;
            pointer-events: none;
        }
        
        .coupon-management .admin-section {
            position: relative;
            z-index: 2;
        }
        
        .coupon-management h3 {
            color: #2d3748;
            font-size: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            border-bottom: 3px solid rgba(102, 126, 234, 0.3);
            padding-bottom: 1rem;
        }
        
        .coupon-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .coupon-form-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .coupon-form-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            border-color: rgba(102, 126, 234, 0.3);
        }
        
        .coupon-form-card h4 {
            color: #2d3748;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .coupon-form-grid .form-group {
            margin-bottom: 1.5rem;
        }
        
        .coupon-form-grid .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2d3748;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .coupon-form-grid .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            color: #2d3748;
        }
        
        .coupon-form-grid .form-group input:focus {
            outline: none;
            border-color: #e22027;
            box-shadow: 0 0 0 3px rgba(226, 32, 39, 0.1);
            background: white;
        }
        
        .coupon-add-btn {
            background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%) !important;
            color: #ffffff !important;
            border: none !important;
            padding: 1rem 2rem !important;
            border-radius: 50px !important;
            font-size: 1.1rem !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            box-shadow: 0 4px 15px rgba(226, 32, 39, 0.3) !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            margin: 0 auto !important;
        }
        
        .coupon-add-btn:hover {
            background: linear-gradient(135deg, #c41e3a 0%, #a01a2e 100%) !important;
            color: #ffffff !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(226, 32, 39, 0.4) !important;
        }
        
        .coupon-table-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-top: 2rem;
        }
        
        .coupon-table-container h4 {
            color: #181818 !important;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600 !important;
        }
        
        .coupon-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .coupon-table thead {
            background: #f8f9fa !important;
            color: #181818 !important;
        }
        
        .coupon-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #181818 !important;
            background: #f8f9fa !important;
            border-bottom: 2px solid #e22027 !important;
        }
        
        .coupon-table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            color: #181818 !important;
            background: white;
        }
        
        .coupon-table td strong {
            color: #181818 !important;
            font-weight: 600 !important;
        }
        
        .coupon-table tbody tr:hover {
            background: #f8f9fa !important;
            transform: none !important;
            transition: background 0.2s ease !important;
        }
        
        .coupon-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #181818 !important;
            background: #f8f9fa !important;
            border: 1px solid #e9ecef !important;
        }
        
        .status-active {
            background: #f8f9fa !important;
            color: #181818 !important;
            border: 1px solid #e9ecef !important;
        }
        
        .status-expired {
            background: #f8f9fa !important;
            color: #181818 !important;
            border: 1px solid #e9ecef !important;
        }
        
        .status-canceled {
            background: #f8f9fa !important;
            color: #181818 !important;
            border: 1px solid #e9ecef !important;
        }
        
     
        .modern-table-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            margin-top: 2rem;
        }
        
        .modern-table-container h4 {
            color: #181818 !important;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600 !important;
        }
        
        .modern-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .modern-table thead {
            background: #f8f9fa !important;
        }
        
        .modern-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #181818 !important;
            background: #f8f9fa !important;
            border-bottom: 2px solid #e22027 !important;
        }
        
        .modern-table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            color: #181818 !important;
            background: white;
            vertical-align: middle;
        }
        
        .modern-table td strong {
            color: #181818 !important;
            font-weight: 600 !important;
        }
        
        .modern-table tbody tr:hover {
            background: #f8f9fa !important;
            transition: background 0.2s ease !important;
        }
        
        .modern-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Modern Form Styles */
        .modern-form {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .modern-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .modern-form .form-group {
            margin-bottom: 1.5rem;
        }
        
        .modern-form .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #181818;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .modern-form .form-group input,
        .modern-form .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            color: #181818;
        }
        
        .modern-form .form-group input:focus,
        .modern-form .form-group select:focus {
            outline: none;
            border-color: #e22027;
            box-shadow: 0 0 0 3px rgba(226, 32, 39, 0.1);
        }
        
        .modern-btn {
            background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%) !important;
            color: #ffffff !important;
            border: none !important;
            padding: 1rem 2rem !important;
            border-radius: 50px !important;
            font-size: 1.1rem !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            box-shadow: 0 4px 15px rgba(226, 32, 39, 0.3) !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            margin: 0 auto !important;
        }
        
        .modern-btn:hover {
            background: linear-gradient(135deg, #c41e3a 0%, #a01a2e 100%) !important;
            color: #ffffff !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(226, 32, 39, 0.4) !important;
        }
        
        @media (max-width: 768px) {
            .coupon-form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .coupon-management {
                padding: 1rem;
                margin: 1rem 0;
            }
            
            .coupon-form-card {
                padding: 1.5rem;
            }
            
            .modern-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .modern-table-container {
                padding: 1rem;
            }
            
            .modern-form {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Company Header -->
    <header class="company-header">
        <div class="container">
            <div class="company-info">
                <div class="company-logo-section">
                    <img src="<?php echo $company['logo_path'] ?: 'images/default-company.png'; ?>" 
                         alt="<?php echo $company['name']; ?>" class="company-logo-header">
                </div>
                <div class="company-text-section">
                <h1><?php echo $company['name']; ?></h1>
                <p>Firma Yönetim Paneli</p>
            </div>
        </div>
        </div>
    <!-- Navigation -->
    <nav class="navbar" style="background: rgba(24, 24, 24, 0.95); padding: 1rem 0; color: #ffffff; box-shadow: 0 2px 20px rgba(0,0,0,0.3); width: 100%; margin: 0; border-bottom: 2px solid #e22027; backdrop-filter: blur(10px);">
        <div class="nav-container" style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <div class="nav-logo">
                <a href="index.php" style="color: #ffffff; text-decoration: none; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; gap: 10px;">
                    <img src="images/logos/dvicebilet-logo.svg" alt="DVICEBILET" style="height: 65px; filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.3)); border-radius: 5px;">
                </a>
            </div>
            
            <div class="nav-menu" style="display: flex; gap: 2rem; align-items: center;">
                <?php renderNavbar('company_panel'); ?>
            </div>
        </div>
    </nav>
    </header>



    <div class="container">
        <?php echo $message; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_trips']; ?></div>
                <div class="stat-label">Toplam Sefer</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['active_tickets']; ?></div>
                <div class="stat-label">Aktif Bilet</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo formatCurrency($stats['total_revenue']); ?></div>
                <div class="stat-label">Toplam Gelir</div>
            </div>
        </div>

       
        <div class="trip-form">
            <h3><i class="fas fa-plus"></i> Yeni Sefer Ekle</h3>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="departure_city">Kalkış Şehri *</label>
                        <select id="departure_city" name="departure_city" required>
                            <option value="">Kalkış şehri seçin</option>
                            <?php foreach ($all_cities as $city): ?>
                                <option value="<?php echo $city; ?>" <?php echo ($edit_trip_data && $edit_trip_data['departure_city'] === $city) ? 'selected' : ''; ?>><?php echo $city; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="destination_city">Varış Şehri *</label>
                        <select id="destination_city" name="destination_city" required>
                            <option value="">Varış şehri seçin</option>
                            <?php foreach ($all_cities as $city): ?>
                                <option value="<?php echo $city; ?>" <?php echo ($edit_trip_data && $edit_trip_data['destination_city'] === $city) ? 'selected' : ''; ?>><?php echo $city; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="departure_time">Kalkış Saati *</label>
                        <input type="datetime-local" id="departure_time" name="departure_time" value="<?php echo $edit_trip_data ? str_replace(' ', 'T', $edit_trip_data['departure_time']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="arrival_time">Varış Saati *</label>
                        <input type="datetime-local" id="arrival_time" name="arrival_time" value="<?php echo $edit_trip_data ? str_replace(' ', 'T', $edit_trip_data['arrival_time']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Fiyat (TL) *</label>
                        <input type="number" id="price" name="price" min="1" value="<?php echo $edit_trip_data ? $edit_trip_data['price'] : ''; ?>" required placeholder="150">
                    </div>
                    
                    <div class="form-group">
                        <label for="capacity">Koltuk Sayısı *</label>
                        <input type="number" id="capacity" name="capacity" min="1" max="60" value="<?php echo $edit_trip_data ? $edit_trip_data['capacity'] : ''; ?>" required placeholder="45">
                        <small id="capacityPreview" style="display:block; margin-top: .35rem; color:#e22027; font-weight:600;">
                            Toplam koltuk: <span id="capacityValue"><?php echo $edit_trip_data ? (int)$edit_trip_data['capacity'] : 0; ?></span>
                        </small>
                    </div>
                </div>
                
                
                <?php if ($edit_trip_data): ?>
                    <input type="hidden" name="trip_id" value="<?php echo $edit_trip_data['id']; ?>">
                    <button type="submit" name="update_trip" class="btn btn-primary">
                        <i class="fas fa-save"></i> Sefer Güncelle
                    </button>
                <?php else: ?>
                <button type="submit" name="add_trip" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Sefer Ekle
                </button>
                <?php endif; ?>
            </form>
        </div>

        <!-- firma bilet yönetimi -->
        <div class="coupon-management">
            <div class="admin-section">
                <h3><i class="fas fa-ticket-alt"></i> Şirket Biletleri Yönetimi</h3>
                
                <div class="coupon-table-container">
                    <h4><i class="fas fa-list"></i> Satılan Biletler</h4>
                    <table class="coupon-table">
                    <thead>
                        <tr>
                                <th>Bilet No</th>
                                <th>Yolcu</th>
                                <th>Güzergah</th>
                                <th>Kalkış</th>
                                <th>Koltuklar</th>
                                <th>Fiyat</th>
                            <th>Durum</th>
                                <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                            <?php if (empty($company_tickets)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 2rem; color: #a0aec0;">
                                        <i class="fas fa-ticket-alt" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                        Henüz satılan bilet bulunmuyor.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($company_tickets as $ticket): ?>
                                    <tr>
                                        <td>
                                            <strong style="font-family: monospace; font-size: 0.9rem;">
                                                <?php echo substr($ticket['id'], 0, 8); ?>...
                                            </strong>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($ticket['full_name']); ?></div>
                                            <div style="font-size: 0.8rem; color: #a0aec0;"><?php echo htmlspecialchars($ticket['email']); ?></div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($ticket['departure_city'] . ' → ' . $ticket['destination_city']); ?></strong>
                                        </td>
                                        <td>
                                            <div><?php echo date('d.m.Y', strtotime($ticket['departure_time'])); ?></div>
                                            <div style="font-size: 0.9rem; color: #e22027;"><?php echo date('H:i', strtotime($ticket['departure_time'])); ?></div>
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-wrap: wrap; gap: 0.2rem;">
                                                <?php 
                                             
                                                $seat_stmt = $db->prepare("SELECT seat_number FROM booked_seats WHERE ticket_id = ? ORDER BY seat_number");
                                                $seat_stmt->execute([$ticket['id']]);
                                                $seats = $seat_stmt->fetchAll(PDO::FETCH_COLUMN);
                                                
                                                if (!empty($seats)):
                                                    foreach ($seats as $seat): 
                                                ?>
                                                    <span style="background: #e22027; color: white; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">
                                                        <?php echo htmlspecialchars($seat); ?>
                                                    </span>
                                                <?php 
                                                    endforeach;
                                                else:
                                                ?>
                                                    <span style="color: #a0aec0; font-size: 0.8rem;">Koltuk bilgisi yok</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong style="color: #48bb78;"><?php echo formatCurrency($ticket['total_price']); ?></strong>
                                            <?php if (!empty($ticket['coupon_code'])): ?>
                                                <div style="font-size: 0.8rem; color: #ed8936;">
                                                    Kupon: <?php echo htmlspecialchars($ticket['coupon_code']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($ticket['status'] === 'ACTIVE'): ?>
                                                <span class="status-badge status-active">Aktif</span>
                                            <?php elseif ($ticket['status'] === 'CANCELED'): ?>
                                                <span class="status-badge status-canceled">İptal</span>
                                    <?php else: ?>
                                                <span class="status-badge status-expired">Süresi Doldu</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ($ticket['status'] === 'ACTIVE'): ?>
                                                <?php
                                                $departureDateTime = new DateTime($ticket['departure_time']);
                                                $currentDateTime = new DateTime();
                                                $timeDifference = $departureDateTime->getTimestamp() - $currentDateTime->getTimestamp();
                                                $canCancel = $timeDifference > 3600;
                                                ?>
                                                <?php if ($canCancel): ?>
                                                    <form method="POST" style="display: inline-block;">
                                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                        <button type="submit" name="cancel_ticket" 
                                                                style="background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
                                                                       color: white;
                                                                       border: none;
                                                                       padding: 0.6rem 1.2rem;
                                                                       border-radius: 25px;
                                                                       font-size: 0.85rem;
                                                                       font-weight: 600;
                                                                       cursor: pointer;
                                                                       transition: all 0.3s ease;
                                                                       box-shadow: 0 2px 8px rgba(245, 101, 101, 0.3);
                                                                       display: inline-flex;
                                                                       align-items: center;
                                                                       gap: 0.4rem;
                                                                       min-width: 100px;
                                                                       justify-content: center;"
                                                                onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(245, 101, 101, 0.4)';"
                                                                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(245, 101, 101, 0.3)';"
                                                                onclick="return confirm('Bu bilet iptal edilecek. Emin misiniz?')">
                                                            <i class="fas fa-times"></i> İptal Et
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <div style="background: #f7fafc;
                                                               color: #a0aec0;
                                                               padding: 0.6rem 1.2rem;
                                                               border-radius: 25px;
                                                               font-size: 0.85rem;
                                                               font-weight: 500;
                                                               border: 1px solid #e2e8f0;
                                                               display: inline-flex;
                                                               align-items: center;
                                                               gap: 0.4rem;
                                                               min-width: 100px;
                                                               justify-content: center;">
                                                        <i class="fas fa-clock"></i> İptal Edilemez
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div style="background: #f7fafc;
                                                           color: #a0aec0;
                                                           padding: 0.6rem 1.2rem;
                                                           border-radius: 25px;
                                                           font-size: 0.85rem;
                                                           font-weight: 500;
                                                           border: 1px solid #e2e8f0;
                                                           display: inline-flex;
                                                           align-items: center;
                                                           gap: 0.4rem;
                                                           min-width: 100px;
                                                           justify-content: center;">
                                                    <i class="fas fa-ban"></i> İptal Edilemez
                                                </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                            <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

    
        <div class="trips-table">
            <div style="padding: 2rem 2rem 0 2rem;">
                <h3><i class="fas fa-list"></i> Seferlerim</h3>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Güzergah</th>
                        <th>Tarih/Saat</th>
                        <th>Fiyat</th>
                        <th>Kapasite</th>
                        <th>Biletler</th>
                        <th>Gelir</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trips as $trip): ?>
                        <tr>
                            <td>
                                <strong><?php echo $trip['departure_city']; ?> → <?php echo $trip['destination_city']; ?></strong>
                            </td>
                            <td>
                                <div><?php echo date('d.m.Y', strtotime($trip['departure_time'])); ?></div>
                                <small><?php echo date('H:i', strtotime($trip['departure_time'])); ?> - <?php echo date('H:i', strtotime($trip['arrival_time'])); ?></small>
                            </td>
                            <td><?php echo formatCurrency($trip['price']); ?></td>
                            <td><?php echo $trip['capacity']; ?></td>
                            <td>
                                <span style="color: <?php echo $trip['active_tickets'] > 0 ? '#28a745' : '#666'; ?>;">
                                    <?php echo $trip['active_tickets']; ?> / <?php echo $trip['ticket_count']; ?>
                                </span>
                            </td>
                            <td><?php echo formatCurrency($trip['revenue']); ?></td>
                            <td>
                                <div class="trip-actions">
                                    <a href="?edit_trip=<?php echo $trip['id']; ?>" class="btn btn-outline btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
                                        <button type="submit" name="delete_trip" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- kupon yönetimi -->
        <div class="modern-card" style="margin-top: 2rem;">
            <h3><i class="fas fa-ticket-alt"></i> Kupon Yönetimi</h3>
            
            <!-- kupon ekle düzenle  -->
            <form method="POST" class="modern-form" id="couponForm">
                <input type="hidden" id="coupon_id" name="coupon_id" value="">
                
                <div class="modern-grid">
                    <div class="form-group">
                        <label for="code">Kupon Kodu *</label>
                        <input type="text" id="code" name="code" required placeholder="Örn: YAZ2024" style="text-transform: uppercase;">
    </div>

                    <div class="form-group">
                        <label for="discount_type">İndirim Türü *</label>
                        <select id="discount_type" name="discount_type" required>
                            <option value="">Seçiniz</option>
                            <option value="percentage">Yüzde (%)</option>
                            <option value="fixed">Sabit Tutar (TL)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="discount_value">İndirim Değeri *</label>
                        <input type="number" id="discount_value" name="discount_value" step="0.01" min="0.01" required placeholder="10">
                    </div>
                    
                    <div class="form-group">
                        <label for="min_amount">Minimum Tutar (TL)</label>
                        <input type="number" id="min_amount" name="min_amount" step="0.01" min="0" placeholder="50">
                    </div>
                    
                    <div class="form-group">
                        <label for="max_uses">Maksimum Kullanım *</label>
                        <input type="number" id="max_uses" name="max_uses" min="1" required placeholder="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="expiry_date">Son Kullanma Tarihi *</label>
                        <input type="date" id="expiry_date" name="expiry_date" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_coupon" id="submitBtn" class="modern-btn">
                            <i class="fas fa-plus"></i> Kupon Oluştur
                        </button>
                        <button type="button" id="cancelBtn" onclick="cancelEdit()" class="btn btn-outline" style="display: none;">
                            <i class="fas fa-times"></i> İptal
                        </button>
                    </div>
                </div>
            </form>
            
            <!-- kupon listesi -->
            <div class="modern-table-container" style="margin-top: 2rem;">
                <h4 style="color: #181818 !important; font-weight: 600 !important;"><i class="fas fa-list"></i> Mevcut Kuponlar</h4>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Kupon Kodu</th>
                            <th>İndirim</th>
                            <th>Min. Tutar</th>
                            <th>Kullanım</th>
                            <th>Son Tarih</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coupons as $coupon): ?>
                            <?php 
                            $is_expired = strtotime($coupon['expiry_date']) < time();
                            $used_count = isset($coupon['used_count']) ? $coupon['used_count'] : 0;
                            $is_maxed_out = $used_count >= $coupon['max_uses'];
                            $status = $is_expired ? 'Süresi Dolmuş' : ($is_maxed_out ? 'Limit Dolmuş' : 'Aktif');
                            $status_class = $is_expired ? 'status-expired' : ($is_maxed_out ? 'status-canceled' : 'status-active');
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($coupon['code']); ?></strong></td>
                                <td>
                                    <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                        %<?php echo $coupon['discount_value']; ?>
                                    <?php else: ?>
                                        <?php echo formatCurrency($coupon['discount_value']); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $coupon['min_amount'] > 0 ? formatCurrency($coupon['min_amount']) : '-'; ?></td>
                                <td><?php echo $used_count; ?> / <?php echo $coupon['max_uses']; ?></td>
                                <td><?php echo date('d.m.Y', strtotime($coupon['expiry_date'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $status; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button onclick="editCoupon('<?php echo $coupon['id']; ?>', '<?php echo htmlspecialchars($coupon['code']); ?>', '<?php echo $coupon['discount_type']; ?>', '<?php echo $coupon['discount_value']; ?>', '<?php echo $coupon['min_amount']; ?>', '<?php echo $coupon['max_uses']; ?>', '<?php echo $coupon['expiry_date']; ?>')" 
                                                class="btn btn-outline btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                            <button type="submit" name="delete_coupon" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Bu kuponu silmek istediğinizden emin misiniz?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($coupons)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem; color: #666;">
                                    <i class="fas fa-ticket-alt" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                    Henüz kupon oluşturmadınız.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php renderFooter(); ?>

</body>
</html>
    <script>
// Koltuk sayısı önizleme ve sınır kontrolü
(function(){
  var input = document.getElementById('capacity');
  var valueEl = document.getElementById('capacityValue');
  if(!input || !valueEl) return;
  function clamp(val,min,max){ val = parseInt(val||0,10); if(isNaN(val)) val=min; return Math.max(min, Math.min(max, val)); }
  function update(){
    var v = clamp(input.value, parseInt(input.min||'1',10), parseInt(input.max||'60',10));
    if(v != input.value) input.value = v;
    valueEl.textContent = v;
  }
  input.addEventListener('input', update);
  input.addEventListener('change', update);
  update();
})();

// Kupon yönetim fonksiyonları
function editCoupon(id, code, discountType, discountValue, minAmount, maxUses, expiryDate) {
    // Güvenlik kontrolü: Sadece bu firmaya ait kuponları düzenlemeye izin ver
    const allowedCouponIds = [<?php echo implode(',', array_map(function($coupon) { return "'" . $coupon['id'] . "'"; }, $coupons)); ?>];
    
    if (!allowedCouponIds.includes(id)) {
        alert('Bu kuponu düzenleme yetkiniz yok.');
        return;
    }
    
   
    document.getElementById('coupon_id').value = id;
    document.getElementById('code').value = code;
    document.getElementById('discount_type').value = discountType;
    document.getElementById('discount_value').value = discountValue;
    document.getElementById('min_amount').value = minAmount;
    document.getElementById('max_uses').value = maxUses;
    document.getElementById('expiry_date').value = expiryDate;
    
   
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Kuponu Güncelle';
    document.getElementById('submitBtn').name = 'edit_coupon';
    document.getElementById('cancelBtn').style.display = 'inline-block';
            
    document.getElementById('couponForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function cancelEdit() {

    document.getElementById('coupon_id').value = '';
    document.getElementById('code').value = '';
    document.getElementById('discount_type').value = '';
    document.getElementById('discount_value').value = '';
    document.getElementById('min_amount').value = '';
    document.getElementById('max_uses').value = '';
    document.getElementById('expiry_date').value = '';
    

    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-plus"></i> Kupon Oluştur';
    document.getElementById('submitBtn').name = 'add_coupon';
    document.getElementById('cancelBtn').style.display = 'none';
        }
    </script>
