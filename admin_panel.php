<?php
require_once 'config.php';

requireRole(['admin']);

$db = getDB();
$message = '';

// Bir kuponu düzenleyip düzenlemediğimizi kontrol et
$editing_coupon = null;
if (isset($_GET['edit_coupon'])) {
    $edit_coupon_id = sanitize($_GET['edit_coupon']);
    $stmt = $db->prepare("SELECT * FROM coupons WHERE id = ? AND company_id IS NULL");
    $stmt->execute([$edit_coupon_id]);
    $editing_coupon = $stmt->fetch();
}

// Form gönderimlerini işle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_company'])) {
        $company_name = sanitize($_POST['company_name']);
        $logo_path = '';
        $has_upload_error = false;
        
        // Logo dosyası yükleme işlemi (dosya yükleme isteğe bağlı)
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['company_logo'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $file_size = $file['size'];
            
            // Dosya boyutu kontrolü (2MB = 2097152 bytes)
            if ($file_size > 2097152) {
                $message = '<div class="alert alert-error">Logo dosyası 2MB\'den büyük olamaz.</div>';
                $has_upload_error = true;
            }
            // Sadece PNG dosyalarına izin ver
            elseif ($file_ext !== 'png') {
                $message = '<div class="alert alert-error">Sadece PNG formatında logo yükleyebilirsiniz.</div>';
                $has_upload_error = true;
            } else {
                // Yükleme dizinini kontrol et ve oluştur
                $upload_dir = 'images/logos/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Benzersiz dosya adı oluştur
                $file_name = uniqid('company_') . '.png';
                $target_path = $upload_dir . $file_name;
                
                // Dosyayı taşı
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $logo_path = $target_path;
                } else {
                    $message = '<div class="alert alert-error">Logo yüklenirken bir hata oluştu.</div>';
                    $has_upload_error = true;
                }
            }
        }
        
        // Firma ekleme işlemi
        if (empty($company_name)) {
            $message = '<div class="alert alert-error">Firma adı gereklidir.</div>';
        } elseif (!$has_upload_error) {
            $company_id = generateUUID();
            $stmt = $db->prepare("
                INSERT INTO bus_companies (id, name, logo_path) 
                VALUES (?, ?, ?)
            ");
            
            if ($stmt->execute([$company_id, $company_name, $logo_path])) {
                $message = '<div class="alert alert-success">Firma başarıyla eklendi.</div>';
            } else {
                $message = '<div class="alert alert-error">Firma eklenirken bir hata oluştu.</div>';
            }
        }
    }
    
    if (isset($_POST['edit_company'])) {
        $company_id = sanitize($_POST['company_id']);
        $company_name = sanitize($_POST['company_name']);
        $current_logo = sanitize($_POST['current_logo']);
        $logo_path = $current_logo;
        $has_upload_error = false;
        
        // Yeni logo dosyası yükleme işlemi
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['company_logo'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $file_size = $file['size'];
            
            // Dosya boyutu kontrolü (2MB = 2097152 bytes)
            if ($file_size > 2097152) {
                $message = '<div class="alert alert-error">Logo dosyası 2MB\'den büyük olamaz.</div>';
                $has_upload_error = true;
            }
            // Sadece PNG dosyalarına izin ver
            elseif ($file_ext !== 'png') {
                $message = '<div class="alert alert-error">Sadece PNG formatında logo yükleyebilirsiniz.</div>';
                $has_upload_error = true;
            } else {
                // Yükleme dizinini kontrol et ve oluştur
                $upload_dir = 'images/logos/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Benzersiz dosya adı oluştur
                $file_name = uniqid('company_') . '.png';
                $target_path = $upload_dir . $file_name;
                
                // Dosyayı taşı
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    // Eski logoyu sil (eğer varsa ve default değilse)
                    if (!empty($current_logo) && file_exists($current_logo) && strpos($current_logo, 'default') === false) {
                        unlink($current_logo);
                    }
                    $logo_path = $target_path;
                } else {
                    $message = '<div class="alert alert-error">Logo yüklenirken bir hata oluştu.</div>';
                    $has_upload_error = true;
                }
            }
        }
        
        // Firma güncelleme işlemi
        if (empty($company_name)) {
            $message = '<div class="alert alert-error">Firma adı gereklidir.</div>';
        } elseif (!$has_upload_error) {
            $stmt = $db->prepare("
                UPDATE bus_companies 
                SET name = ?, logo_path = ? 
                WHERE id = ?
            ");
            
            if ($stmt->execute([$company_name, $logo_path, $company_id])) {
                $message = '<div class="alert alert-success">Firma başarıyla güncellendi.</div>';
            } else {
                $message = '<div class="alert alert-error">Firma güncellenirken bir hata oluştu.</div>';
            }
        }
    }
    
    if (isset($_POST['delete_company'])) {
        $company_id = sanitize($_POST['company_id']);
        
        // Firma aktif seferleri veya biletleri var mı kontrol et
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM trips t
            LEFT JOIN tickets tk ON t.id = tk.trip_id
            WHERE t.company_id = ? AND (t.id IS NOT NULL OR tk.status = 'ACTIVE')
        ");
        $stmt->execute([$company_id]);
        $active_data = $stmt->fetch()['count'];
        
        if ($active_data > 0) {
            $message = '<div class="alert alert-error">Bu firmanın aktif seferleri veya biletleri bulunduğu için silinemez.</div>';
        } else {
            $db->beginTransaction();
            try {
                // Önce firma admin kullanıcılarını sil
                $stmt = $db->prepare("DELETE FROM users WHERE company_id = ? AND role = 'company'");
                $stmt->execute([$company_id]);
                
                // Firmayı sil
                $stmt = $db->prepare("DELETE FROM bus_companies WHERE id = ?");
                $stmt->execute([$company_id]);
                
                $db->commit();
                $message = '<div class="alert alert-success">Firma başarıyla silindi.</div>';
            } catch (Exception $e) {
                $db->rollBack();
                $message = '<div class="alert alert-error">Firma silinirken bir hata oluştu.</div>';
            }
        }
    }
    
    if (isset($_POST['add_company_admin'])) {
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $company_id = sanitize($_POST['company_id']);
        
        if (empty($full_name) || empty($email) || empty($password) || empty($company_id)) {
            $message = '<div class="alert alert-error">Tüm alanları doldurun.</div>';
        } else {
            // E-posta var mı kontrol et
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $message = '<div class="alert alert-error">Bu e-posta adresi zaten kullanılıyor.</div>';
            } else {
                $user_id = generateUUID();
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("
                    INSERT INTO users (id, full_name, email, password, role, company_id, balance) 
                    VALUES (?, ?, ?, ?, 'company', ?, 0)
                ");
                
                if ($stmt->execute([$user_id, $full_name, $email, $hashed_password, $company_id])) {
                    $message = '<div class="alert alert-success">Firma admin başarıyla eklendi.</div>';
                } else {
                    $message = '<div class="alert alert-error">Firma admin eklenirken bir hata oluştu.</div>';
                }
            }
        }
    }
    
    if (isset($_POST['edit_company_admin'])) {
        $user_id = sanitize($_POST['user_id']);
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $company_id = sanitize($_POST['company_id']);
        $password = $_POST['password'];
        
        if (empty($full_name) || empty($email) || empty($company_id)) {
            $message = '<div class="alert alert-error">Tüm alanları doldurun.</div>';
        } else {
            // Diğer kullanıcılar için e-posta var mı kontrol et
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $message = '<div class="alert alert-error">Bu e-posta adresi zaten kullanılıyor.</div>';
            } else {
                if (!empty($password)) {
                    // Şifre ile güncelle
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET full_name = ?, email = ?, company_id = ?, password = ? 
                        WHERE id = ? AND role = 'company'
                    ");
                    $stmt->execute([$full_name, $email, $company_id, $hashed_password, $user_id]);
                } else {
                    // Şifre olmadan güncelle
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET full_name = ?, email = ?, company_id = ? 
                        WHERE id = ? AND role = 'company'
                    ");
                    $stmt->execute([$full_name, $email, $company_id, $user_id]);
                }
                
                if ($stmt->rowCount() > 0) {
                    $message = '<div class="alert alert-success">Firma admin başarıyla güncellendi.</div>';
                } else {
                    $message = '<div class="alert alert-error">Firma admin güncellenirken bir hata oluştu.</div>';
                }
            }
        }
    }
    
    if (isset($_POST['delete_company_admin'])) {
        $user_id = sanitize($_POST['user_id']);
        
        // Bunun firmanın tek admini olup olmadığını kontrol et
        $stmt = $db->prepare("
            SELECT company_id, 
                   (SELECT COUNT(*) FROM users WHERE company_id = u.company_id AND role = 'company') as admin_count
            FROM users u 
            WHERE u.id = ? AND u.role = 'company'
        ");
        $stmt->execute([$user_id]);
        $admin_info = $stmt->fetch();
        
        if ($admin_info && $admin_info['admin_count'] <= 1) {
            $message = '<div class="alert alert-error">Bu firmanın tek admini olduğu için silinemez.</div>';
        } else {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'company'");
            if ($stmt->execute([$user_id])) {
                $message = '<div class="alert alert-success">Firma admin başarıyla silindi.</div>';
            } else {
                $message = '<div class="alert alert-error">Firma admin silinirken bir hata oluştu.</div>';
            }
        }
    }
    
    if (isset($_POST['add_balance'])) {
        $email = sanitize($_POST['user_email']);
        $amount = floatval($_POST['amount']);
        
        if (empty($email) || $amount <= 0) {
            $message = '<div class="alert alert-error">Lütfen geçerli bir e-posta ve miktar girin.</div>';
        } else {
            // Kullanıcının var olup olmadığını kontrol et
            $stmt = $db->prepare("SELECT id, full_name, balance FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $message = '<div class="alert alert-error">Bu e-posta adresine kayıtlı kullanıcı bulunamadı.</div>';
            } else {
                // Bakiye ekle
                $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE email = ?");
                if ($stmt->execute([$amount, $email])) {
                    $new_balance = $user['balance'] + $amount;
                    $message = '<div class="alert alert-success">' . htmlspecialchars($user['full_name']) . ' kullanıcısına ' . formatCurrency($amount) . ' bakiye eklendi. Yeni bakiye: ' . formatCurrency($new_balance) . '</div>';
                } else {
                    $message = '<div class="alert alert-error">Bakiye eklenirken bir hata oluştu.</div>';
                }
            }
        }
    }
    
    if (isset($_POST['search_ticket'])) {
        $ticket_id = sanitize($_POST['ticket_id']);
        
        if (empty($ticket_id)) {
            $message = '<div class="alert alert-error">Lütfen bilet numarası girin.</div>';
        } else {
            // Bilet detayları sayfasına yönlendir
            redirect('admin_ticket_details.php?id=' . urlencode($ticket_id));
        }
    }
    
    // Global kupon yönetimini işle
    if (isset($_POST['add_global_coupon'])) {
        $code = sanitize($_POST['coupon_code']);
        $discount_type = sanitize($_POST['discount_type']);
        $discount_value = floatval($_POST['discount_value']);
        $min_amount = floatval($_POST['min_amount']);
        $max_uses = intval($_POST['max_uses']);
        $expiry_date = sanitize($_POST['expiry_date']);
        
        if (empty($code) || empty($discount_type) || $discount_value <= 0 || $max_uses <= 0 || empty($expiry_date)) {
            $message = '<div class="alert alert-error">Kupon bilgilerini doğru şekilde doldurun.</div>';
        } else {
            // Kupon kodunun zaten var olup olmadığını kontrol et
            $stmt = $db->prepare("SELECT id FROM coupons WHERE code = ?");
            $stmt->execute([$code]);
            if ($stmt->fetch()) {
                $message = '<div class="alert alert-error">Bu kupon kodu zaten kullanılıyor.</div>';
            } else {
                $coupon_id = generateUUID();
                $stmt = $db->prepare("
                    INSERT INTO coupons (id, company_id, code, discount_type, discount_value, min_amount, max_uses, expiry_date, created_at) 
                    VALUES (?, NULL, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                ");
                
                if ($stmt->execute([$coupon_id, $code, $discount_type, $discount_value, $min_amount, $max_uses, $expiry_date])) {
                    $message = '<div class="alert alert-success">Global kupon başarıyla oluşturuldu.</div>';
                } else {
                    $message = '<div class="alert alert-error">Kupon oluşturulurken bir hata oluştu.</div>';
                }
            }
        }
    }
    
    if (isset($_POST['edit_global_coupon'])) {
        $coupon_id = sanitize($_POST['coupon_id']);
        $code = sanitize($_POST['coupon_code']);
        $discount_type = sanitize($_POST['discount_type']);
        $discount_value = floatval($_POST['discount_value']);
        $min_amount = floatval($_POST['min_amount']);
        $max_uses = intval($_POST['max_uses']);
        $expiry_date = sanitize($_POST['expiry_date']);
        
        if (empty($code) || empty($discount_type) || $discount_value <= 0 || $max_uses <= 0 || empty($expiry_date)) {
            $message = '<div class="alert alert-error">Kupon bilgilerini doğru şekilde doldurun.</div>';
        } else {
            // Kuponun global olup olmadığını kontrol et
            $stmt = $db->prepare("SELECT id FROM coupons WHERE id = ? AND company_id IS NULL");
            $stmt->execute([$coupon_id]);
            if (!$stmt->fetch()) {
                $message = '<div class="alert alert-error">Bu kuponu düzenleme yetkiniz yok.</div>';
            } else {
                // Kupon kodunun zaten var olup olmadığını kontrol et (mevcut kupon hariç)
                $stmt = $db->prepare("SELECT id FROM coupons WHERE code = ? AND id != ?");
                $stmt->execute([$code, $coupon_id]);
                if ($stmt->fetch()) {
                    $message = '<div class="alert alert-error">Bu kupon kodu zaten kullanılıyor.</div>';
                } else {
                    $stmt = $db->prepare("
                        UPDATE coupons 
                        SET code = ?, discount_type = ?, discount_value = ?, min_amount = ?, max_uses = ?, expiry_date = ?
                        WHERE id = ? AND company_id IS NULL
                    ");
                    
                    if ($stmt->execute([$code, $discount_type, $discount_value, $min_amount, $max_uses, $expiry_date, $coupon_id])) {
                        $message = '<div class="alert alert-success">Global kupon başarıyla güncellendi.</div>';
                        // Düzenleme parametresini temizlemek için yönlendir
                        redirect('admin_panel.php');
                    } else {
                        $message = '<div class="alert alert-error">Kupon güncellenirken bir hata oluştu.</div>';
                    }
                }
            }
        }
    }
    
    if (isset($_POST['delete_global_coupon'])) {
        $coupon_id = sanitize($_POST['coupon_id']);
        
        // Kuponun global olup olmadığını kontrol et 
        $stmt = $db->prepare("SELECT id FROM coupons WHERE id = ? AND company_id IS NULL");
        $stmt->execute([$coupon_id]);
        if (!$stmt->fetch()) {
            $message = '<div class="alert alert-error">Bu kuponu silme yetkiniz yok.</div>';
        } else {
            $stmt = $db->prepare("DELETE FROM coupons WHERE id = ? AND company_id IS NULL");
            if ($stmt->execute([$coupon_id])) {
                $message = '<div class="alert alert-success">Global kupon başarıyla silindi.</div>';
            } else {
                $message = '<div class="alert alert-error">Kupon silinirken bir hata oluştu.</div>';
            }
        }
    }
    
    if (isset($_POST['cancel_edit_coupon'])) {
        redirect('admin_panel.php');
    }
}

// İstatistikleri al
$stats = [];

$stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
$stmt->execute();
$stats['users'] = $stmt->fetch()['count'];

$stmt = $db->prepare("SELECT COUNT(*) as count FROM bus_companies");
$stmt->execute();
$stats['companies'] = $stmt->fetch()['count'];

$stmt = $db->prepare("SELECT COUNT(*) as count FROM trips");
$stmt->execute();
$stats['trips'] = $stmt->fetch()['count'];

$stmt = $db->prepare("SELECT COUNT(*) as count FROM tickets WHERE status = 'ACTIVE'");
$stmt->execute();
$stats['active_tickets'] = $stmt->fetch()['count'];


$stmt = $db->prepare("SELECT * FROM bus_companies ORDER BY name");
$stmt->execute();
$companies = $stmt->fetchAll();

// Firma adminlerini al
$stmt = $db->prepare("
    SELECT u.*, bc.name as company_name 
    FROM users u 
    LEFT JOIN bus_companies bc ON u.company_id = bc.id 
    WHERE u.role = 'company' 
    ORDER BY u.created_at DESC
");
$stmt->execute();
$company_admins = $stmt->fetchAll();

// Bakiye yönetimi için tüm kullanıcıları al
$stmt = $db->prepare("
    SELECT id, full_name, email, role, balance, created_at 
    FROM users 
    WHERE role != 'admin'
    ORDER BY created_at DESC
");
$stmt->execute();
$all_users = $stmt->fetchAll();

// Global kuponları al 
$stmt = $db->prepare("
    SELECT c.*, 
           COUNT(uc.id) as used_count
    FROM coupons c 
    LEFT JOIN user_coupons uc ON c.id = uc.coupon_id 
    WHERE c.company_id IS NULL
    GROUP BY c.id 
    ORDER BY c.created_at DESC
");
try {
    $stmt->execute();
    $global_coupons = $stmt->fetchAll();
} catch (PDOException $e) {
    // Eğer kuponlar tablosu yoksa veya eski yapıya sahipse, düzeltmeye çalış
    if (strpos($e->getMessage(), 'no such column') !== false || 
        strpos($e->getMessage(), 'NOT NULL constraint failed') !== false) {
        
        // Mevcut tablo yapısını kontrol et
        $stmt = $db->query("PRAGMA table_info(coupons)");
        $columns = $stmt->fetchAll();
        
        $has_company_id = false;
        $company_id_not_null = false;
        
        foreach ($columns as $column) {
            if ($column['name'] === 'company_id') {
                $has_company_id = true;
                $company_id_not_null = $column['notnull'] == 1;
                break;
            }
        }
        
        if ($has_company_id && $company_id_not_null) {
            // Mevcut verileri yedekle
            $stmt = $db->query("SELECT * FROM coupons");
            $existing_coupons = $stmt->fetchAll();
            
            // Tabloyu sil ve nullable company_id ile yeniden oluştur
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
            
            // Mevcut verileri geri yükle
            if (!empty($existing_coupons)) {
                foreach ($existing_coupons as $coupon) {
                    $stmt = $db->prepare("
                        INSERT INTO coupons (id, company_id, code, discount_type, discount_value, min_amount, max_uses, expiry_date, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $coupon['id'],
                        $coupon['company_id'] ?? null,
                        $coupon['code'],
                        $coupon['discount_type'] ?? 'percentage',
                        $coupon['discount_value'] ?? 0,
                        $coupon['min_amount'] ?? 0,
                        $coupon['max_uses'] ?? 1,
                        $coupon['expiry_date'] ?? '2024-12-31 23:59:59',
                        $coupon['created_at'] ?? date('Y-m-d H:i:s')
                    ]);
                }
            }
            
            // Sorguyu tekrar dene
            $stmt->execute();
            $global_coupons = $stmt->fetchAll();
        } else {
            $global_coupons = [];
        }
    } else {
        $global_coupons = [];
    }
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="css/style.css?v=3.5">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .admin-header {
            background: #2c3e50;
            color: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        .admin-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-nav h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        .admin-nav-actions a {
            color: white;
            text-decoration: none;
            margin-left: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .admin-nav-actions a:hover {
            background-color: rgba(255,255,255,0.2);
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
        .admin-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }
        .admin-section {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .admin-section h3 {
            margin-bottom: 1.5rem;
            color: #333;
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 0.5rem;
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
        .form-group input[type="file"] {
            padding: 0.5rem;
            border: 2px dashed #e22027;
            background: rgba(226, 32, 39, 0.05);
            cursor: pointer;
        }
        .form-group input[type="file"]:hover {
            background: rgba(226, 32, 39, 0.1);
            border-color: #c41e3a;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .data-table th,
        .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #181818 !important;
        }
        .data-table td {
            color: #181818 !important;
        }
        .data-table td strong {
            color: #181818 !important;
        }
        .modern-table th {
            background: #f8f9fa !important;
            color: #181818 !important;
            font-weight: 600;
            padding: 1rem !important;
            border-bottom: 2px solid #e22027 !important;
        }
        .modern-table td {
            color: #181818 !important;
            padding: 1rem !important;
            border-bottom: 1px solid #e9ecef !important;
        }
        .modern-table td strong {
            color: #181818 !important;
            font-weight: 600 !important;
        }
        .modern-table tbody tr:hover {
            background: #f8f9fa !important;
        }
        .modern-btn {
            color: #ffffff !important;
            background: #e22027 !important;
            border: none !important;
            padding: 0.5rem 1rem !important;
            border-radius: 5px !important;
            font-weight: 500 !important;
        }
        .modern-btn:hover {
            background: #c41e3a !important;
            color: #ffffff !important;
        }
        .modern-btn-danger {
            background: #dc3545 !important;
        }
        .modern-btn-danger:hover {
            background: #c82333 !important;
        }
        .modern-btn-secondary {
            background: #6c757d !important;
        }
        .modern-btn-secondary:hover {
            background: #5a6268 !important;
        }
        body {
            min-height: 100vh !important;
            display: flex !important;
            flex-direction: column !important;
            background: #181818 !important;
        }
        .modern-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)) !important;
            gap: 1.5rem !important;
            width: 100% !important;
        }
        .modern-card .modern-grid {
            grid-template-columns: 1fr 1fr auto !important;
            align-items: end !important;
            gap: 1rem !important;
        }
        .modern-card .modern-grid .form-group:last-child {
            display: flex !important;
            align-items: end !important;
        }
        .modern-table-container {
            margin-top: 2rem !important;
            width: 100% !important;
        }
        .modern-table {
            width: 100% !important;
            border-collapse: collapse !important;
        }
        .status-active { color: #28a745; font-weight: bold; }
        .status-expired { color: #dc3545; font-weight: bold; }
        .main-content {
            background: #181818 !important;
            padding: 2rem 0 !important;
            flex: 1 !important;
        }
        .container {
            max-width: 1400px !important;
            margin: 0 auto !important;
            padding: 0 20px !important;
        }
        .editing-notice {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .editing-notice strong {
            color: #856404;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <header class="header" style="background: rgba(24, 24, 24, 0.95); color: #ffffff; box-shadow: 0 2px 20px rgba(0,0,0,0.3); position: sticky; top: 0; z-index: 1000; backdrop-filter: blur(10px); border-bottom: 2px solid #e22027;">
        <nav class="navbar" style="background: rgba(24, 24, 24, 0.95); padding: 1rem 0; color: #ffffff; box-shadow: 0 2px 20px rgba(0,0,0,0.3); width: 100%; margin: 0; border-bottom: 2px solid #e22027; backdrop-filter: blur(10px);">
            <div class="nav-container" style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 20px;">
                <div class="nav-logo">
                    <a href="index.php" style="color: #ffffff; text-decoration: none; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; gap: 10px;">
                        <img src="images/logos/dvicebilet-logo.svg" alt="DVICEBILET" style="height: 65px; filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.3)); border-radius: 5px;">
                    </a>
                </div>
                
                <div class="nav-menu" style="display: flex; gap: 2rem; align-items: center;">
                    <?php renderNavbar('admin_panel'); ?>
                </div>
            </div>
        </nav>
    </header>

    <div class="main-content">
        <div class="container">
        <?php echo $message; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['users']; ?></div>
                <div class="stat-label">Toplam Kullanıcı</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['companies']; ?></div>
                <div class="stat-label">Otobüs Firması</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['trips']; ?></div>
                <div class="stat-label">Toplam Sefer</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['active_tickets']; ?></div>
                <div class="stat-label">Aktif Bilet</div>
            </div>
        </div>
<!-- Admin Sections -->
        <div class="modern-grid">
            <!-- Company Management -->
            <div class="modern-card">
                <h3><i class="fas fa-building"></i> Firma Yönetimi</h3>
                
                <form method="POST" class="modern-form" enctype="multipart/form-data">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1rem;">
                        <div class="form-group">
                            <label for="company_name">Firma Adı *</label>
                            <input type="text" id="company_name" name="company_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="company_logo">
                                <i class="fas fa-image"></i> Logo Dosyası (PNG)
                            </label>
                            <input type="file" id="company_logo" name="company_logo" accept=".png">
                            <small style="color: #666; display: block; margin-top: 0.5rem;">
                                <i class="fas fa-info-circle"></i> Maksimum: 2MB, Sadece PNG formatı
                            </small>
                            <div id="add_logo_preview" style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; text-align: center; display: none;">
                                <img id="add_logo_preview_img" src="" alt="Logo Önizleme" style="max-width: 150px; max-height: 100px; object-fit: contain;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_company" class="modern-btn" style="width: 100%;">
                            <i class="fas fa-plus"></i> Firma Ekle
                        </button>
                    </div>
                </form>
                
                <div class="modern-table-container">
                    <h4 style="color: #181818 !important; font-weight: 600 !important;"><i class="fas fa-list"></i> Mevcut Firmalar</h4>
                    <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Firma Adı</th>
                            <th>Logo</th>
                            <th>Kayıt Tarihi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($companies as $company): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($company['name']); ?></strong></td>
                                <td>
                                    <?php if ($company['logo_path']): ?>
                                        <img src="<?php echo htmlspecialchars($company['logo_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($company['name']); ?>" 
                                             style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                    <?php else: ?>
                                        <span style="color: #a0aec0;">Logo yok</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d.m.Y', strtotime($company['created_at'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button onclick="editCompany('<?php echo $company['id']; ?>', '<?php echo htmlspecialchars($company['name']); ?>', '<?php echo htmlspecialchars($company['logo_path']); ?>')" 
                                                class="modern-btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                                            <i class="fas fa-edit"></i> Düzenle
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="company_id" value="<?php echo $company['id']; ?>">
                                            <button type="submit" name="delete_company" 
                                                    class="modern-btn modern-btn-danger" 
                                                    style="padding: 0.4rem 0.8rem; font-size: 0.8rem;"
                                                    onclick="return confirm('Bu firmayı silmek istediğinizden emin misiniz?')">
                                                <i class="fas fa-trash"></i> Sil
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Company Admin Management -->
            <div class="modern-card">
                <h3><i class="fas fa-user-cog"></i> Firma Admin Yönetimi</h3>
                
                <form method="POST" class="modern-form">
                    <div class="modern-grid">
                        <div class="form-group">
                            <label for="full_name">Ad Soyad *</label>
                            <input type="text" id="full_name" name="full_name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">E-posta *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Şifre *</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="company_id">Firma *</label>
                            <select id="company_id" name="company_id" required>
                                <option value="">Firma Seçin</option>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?php echo $company['id']; ?>">
                                        <?php echo $company['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="add_company_admin" class="modern-btn">
                                <i class="fas fa-user-plus"></i> Firma Admin Ekle
                            </button>
                        </div>
                    </div>
                </form>
                
                <div class="modern-table-container">
                    <h4 style="color:black;"><i class="fas fa-list"></i> Mevcut Firma Adminleri</h4>
                    <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Ad Soyad</th>
                            <th>E-posta</th>
                            <th>Firma</th>
                            <th>Kayıt Tarihi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($company_admins as $admin): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($admin['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td><?php echo htmlspecialchars($admin['company_name'] ?: 'Firma yok'); ?></td>
                                <td><?php echo date('d.m.Y', strtotime($admin['created_at'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button onclick="editCompanyAdmin('<?php echo $admin['id']; ?>', '<?php echo htmlspecialchars($admin['full_name']); ?>', '<?php echo htmlspecialchars($admin['email']); ?>', '<?php echo $admin['company_id']; ?>')" 
                                                class="modern-btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                                            <i class="fas fa-edit"></i> Düzenle
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $admin['id']; ?>">
                                            <button type="submit" name="delete_company_admin" 
                                                    class="modern-btn modern-btn-danger" 
                                                    style="padding: 0.4rem 0.8rem; font-size: 0.8rem;"
                                                    onclick="return confirm('Bu firma adminini silmek istediğinizden emin misiniz?')">
                                                <i class="fas fa-trash"></i> Sil
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

      
        <!-- Ticket Search Section -->
        <div class="modern-card" style="grid-column: 1 / -1; margin-top: 2rem;">
            <h3><i class="fas fa-search"></i> Bilet Sorgulama</h3>
            
            <form method="POST" class="modern-form">
                <div class="modern-grid">
                    <div class="form-group">
                        <label for="ticket_id">Bilet Numarası *</label>
                        <input type="text" id="ticket_id" name="ticket_id" required placeholder="Bilet numarasını girin">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="search_ticket" class="modern-btn">
                            <i class="fas fa-search"></i> Bilet Ara
                        </button>
                    </div>
                </div>
            </form>
            
            <div style="background: rgba(226, 32, 39, 0.1); padding: 1rem; border-radius: 10px; margin-top: 1rem;">
                <h4 style="color: #181818; font-size: 1rem; margin-bottom: 0.5rem;"><i class="fas fa-info-circle"></i> Nasıl Kullanılır?</h4>
                <p style="color: #666; font-size: 0.9rem; margin: 0;">Bilet numarasını girerek biletin detaylarını görüntüleyebilirsiniz. Bilet numarası UUID formatındadır.</p>
            </div>
        </div>

        <!-- Balance Management Section -->
        <div class="modern-card" style="grid-column: 1 / -1; margin-top: 2rem;">
            <h3><i class="fas fa-wallet"></i> Bakiye Yönetimi</h3>
            
            <form method="POST" class="modern-form">
                <div class="modern-grid">
                    <div class="form-group">
                        <label for="user_email">Kullanıcı E-posta *</label>
                        <input type="email" id="user_email" name="user_email" required placeholder="kullanici@dvice.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="amount">Eklenecek Miktar (TL) *</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0.01" required placeholder="100.00">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="add_balance" class="modern-btn">
                            <i class="fas fa-plus-circle"></i> Bakiye Ekle
                        </button>
                    </div>
                </div>
            </form>
            
            <div class="modern-table-container">
                <h4 style="color: #181818 !important; font-weight: 600 !important;"><i class="fas fa-users"></i> Tüm Kullanıcılar</h4>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Ad Soyad</th>
                            <th>E-posta</th>
                            <th>Rol</th>
                            <th>Bakiye</th>
                            <th>Kayıt Tarihi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $user): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php 
                                    $role_labels = [
                                        'user' => 'Yolcu',
                                        'company' => 'Firma Admin'
                                    ];
                                    echo $role_labels[$user['role']] ?? $user['role'];
                                    ?>
                                </td>
                                <td><strong style="color: #28a745;"><?php echo formatBalance($user['balance']); ?></strong></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button onclick="addBalanceToUser('<?php echo htmlspecialchars($user['email']); ?>', '<?php echo htmlspecialchars($user['full_name']); ?>')" 
                                            class="modern-btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                                        <i class="fas fa-plus-circle"></i> Bakiye Ekle
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Global Coupon Management Section -->
        <div class="modern-card" style="grid-column: 1 / -1; margin-top: 2rem;">
            <h3><i class="fas fa-globe"></i> Global Kupon Yönetimi</h3>
            
            <?php if ($editing_coupon): ?>
                <div class="editing-notice">
                    <span style="color:black;"><i class="fas fa-edit"></i> Düzenleme Modu: <strong>"<?php echo htmlspecialchars($editing_coupon['code']); ?>"</strong> kuponu düzenleniyor</span>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="cancel_edit_coupon" class="modern-btn modern-btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                            <i class="fas fa-times"></i> İptal
                        </button>
                    </form>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="modern-form">
                <?php if ($editing_coupon): ?>
                    <input type="hidden" name="coupon_id" value="<?php echo $editing_coupon['id']; ?>">
                <?php endif; ?>
                
                <div class="modern-grid">
                    <div class="form-group">
                        <label for="coupon_code">Kupon Kodu *</label>
                        <input type="text" id="coupon_code" name="coupon_code" required placeholder="Örn: GLOBAL2024" 
                               value="<?php echo $editing_coupon ? htmlspecialchars($editing_coupon['code']) : ''; ?>"
                               style="text-transform: uppercase;">
                    </div>
                    
                    <div class="form-group">
                        <label for="discount_type">İndirim Türü *</label>
                        <select id="discount_type" name="discount_type" required>
                            <option value="">Seçiniz</option>
                            <option value="percentage" <?php echo ($editing_coupon && $editing_coupon['discount_type'] === 'percentage') ? 'selected' : ''; ?>>Yüzde (%)</option>
                            <option value="fixed" <?php echo ($editing_coupon && $editing_coupon['discount_type'] === 'fixed') ? 'selected' : ''; ?>>Sabit Tutar (TL)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="discount_value">İndirim Değeri *</label>
                        <input type="number" id="discount_value" name="discount_value" step="0.01" min="0.01" required placeholder="10"
                               value="<?php echo $editing_coupon ? $editing_coupon['discount_value'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="min_amount">Minimum Tutar (TL)</label>
                        <input type="number" id="min_amount" name="min_amount" step="0.01" min="0" placeholder="50"
                               value="<?php echo $editing_coupon ? $editing_coupon['min_amount'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="max_uses">Maksimum Kullanım *</label>
                        <input type="number" id="max_uses" name="max_uses" min="1" required placeholder="1000"
                               value="<?php echo $editing_coupon ? $editing_coupon['max_uses'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="expiry_date">Son Kullanma Tarihi *</label>
                        <input type="date" id="expiry_date" name="expiry_date" required
                               value="<?php echo $editing_coupon ? date('Y-m-d', strtotime($editing_coupon['expiry_date'])) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="<?php echo $editing_coupon ? 'edit_global_coupon' : 'add_global_coupon'; ?>" class="modern-btn">
                            <i class="fas fa-<?php echo $editing_coupon ? 'save' : 'plus'; ?>"></i> 
                            <?php echo $editing_coupon ? 'Kuponu Güncelle' : 'Global Kupon Oluştur'; ?>
                        </button>
                    </div>
                </div>
            </form>
            
            <div class="modern-table-container" style="margin-top: 2rem;">
                <h4 style="color: #181818 !important; font-weight: 600 !important;"><i class="fas fa-globe"></i> Global Kuponlar (Tüm Firmalar İçin)</h4>
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
                        <?php foreach ($global_coupons as $coupon): ?>
                            <?php 
                            $is_expired = strtotime($coupon['expiry_date']) < time();
                            $is_maxed_out = $coupon['used_count'] >= $coupon['max_uses'];
                            $status = $is_expired ? 'Süresi Dolmuş' : ($is_maxed_out ? 'Limit Dolmuş' : 'Aktif');
                            $status_class = $is_expired ? 'status-expired' : ($is_maxed_out ? 'status-canceled' : 'status-active');
                            $is_editing = $editing_coupon && $editing_coupon['id'] === $coupon['id'];
                            ?>
                            <tr style="<?php echo $is_editing ? 'background: #fff3cd !important;' : ''; ?>">
                                <td><strong><?php echo htmlspecialchars($coupon['code']); ?></strong></td>
                                <td>
                                    <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                        %<?php echo $coupon['discount_value']; ?>
                                    <?php else: ?>
                                        <?php echo formatCurrency($coupon['discount_value']); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $coupon['min_amount'] > 0 ? formatCurrency($coupon['min_amount']) : '-'; ?></td>
                                <td><?php echo $coupon['used_count']; ?> / <?php echo $coupon['max_uses']; ?></td>
                                <td><?php echo date('d.m.Y', strtotime($coupon['expiry_date'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $status; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <?php if (!$is_editing): ?>
                                            <a href="?edit_coupon=<?php echo $coupon['id']; ?>" class="modern-btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; text-decoration: none;">
                                                <i class="fas fa-edit"></i> Düzenle
                                            </a>
                                        <?php else: ?>
                                            <span class="modern-btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: #ffc107 !important; cursor: default;">
                                                <i class="fas fa-arrow-up"></i> Düzenleniyor
                                            </span>
                                        <?php endif; ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                            <button type="submit" name="delete_global_coupon" class="modern-btn modern-btn-danger" 
                                                    style="padding: 0.4rem 0.8rem; font-size: 0.8rem;"
                                                    onclick="return confirm('Bu global kuponu silmek istediğinizden emin misiniz?')">
                                                <i class="fas fa-trash"></i> Sil
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($global_coupons)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem; color: #666;">
                                    <i class="fas fa-globe" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                    Henüz global kupon oluşturmadınız.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    </div>

    <!-- Modal düzenleme -->
    <div id="editCompanyModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 10px; width: 90%; max-width: 500px;">
            <h3 style="margin-bottom: 1.5rem; color: #333;">Firma Düzenle</h3>
            <form method="POST" class="modern-form" enctype="multipart/form-data">
                <input type="hidden" id="edit_company_id" name="company_id">
                <input type="hidden" id="edit_current_logo" name="current_logo">
                <div class="form-group">
                    <label for="edit_company_name">Firma Adı *</label>
                    <input type="text" id="edit_company_name" name="company_name" required>
                </div>
                <div class="form-group">
                    <label>Mevcut Logo</label>
                    <div id="current_logo_preview" style="margin-bottom: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; text-align: center;">
                        <img id="current_logo_img" src="" alt="Mevcut Logo" style="max-width: 150px; max-height: 100px; object-fit: contain; display: none;">
                        <p id="no_logo_text" style="color: #666; margin: 0;">Logo yok</p>
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit_company_logo">Yeni Logo Dosyası (Sadece PNG)</label>
                    <input type="file" id="edit_company_logo" name="company_logo" accept=".png" style="padding: 0.5rem; border: 2px dashed #e22027; background: rgba(226, 32, 39, 0.05); width: 100%;">
                    <small style="color: #666; display: block; margin-top: 0.5rem;">Yeni logo yüklemezseniz mevcut logo korunur. Maksimum: 2MB, Sadece PNG</small>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="button" onclick="closeEditCompanyModal()" class="modern-btn" style="background: #6c757d;">
                        İptal
                    </button>
                    <button type="submit" name="edit_company" class="modern-btn">
                        <i class="fas fa-save"></i> Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Firma modal düzenleme -->
    <div id="editCompanyAdminModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 10px; width: 90%; max-width: 500px;">
            <h3 style="margin-bottom: 1.5rem; color: #333;">Firma Admin Düzenle</h3>
            <form method="POST" class="modern-form">
                <input type="hidden" id="edit_user_id" name="user_id">
                <div class="form-group">
                    <label for="edit_full_name">Ad Soyad *</label>
                    <input type="text" id="edit_full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_email">E-posta *</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="edit_company_id_select">Firma *</label>
                    <select id="edit_company_id_select" name="company_id" required>
                        <option value="">Firma Seçin</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company['id']; ?>">
                                <?php echo htmlspecialchars($company['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_password">Yeni Şifre (Boş bırakırsanız değişmez)</label>
                    <input type="password" id="edit_password" name="password">
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="button" onclick="closeEditCompanyAdminModal()" class="modern-btn" style="background: #6c757d;">
                        İptal
                    </button>
                    <button type="submit" name="edit_company_admin" class="modern-btn">
                        <i class="fas fa-save"></i> Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editCompany(id, name, logoPath) {
            document.getElementById('edit_company_id').value = id;
            document.getElementById('edit_company_name').value = name;
            document.getElementById('edit_current_logo').value = logoPath;
            
            // Mevcut logoyu göster
            const logoImg = document.getElementById('current_logo_img');
            const noLogoText = document.getElementById('no_logo_text');
            
            if (logoPath && logoPath !== '') {
                logoImg.src = logoPath;
                logoImg.style.display = 'block';
                noLogoText.style.display = 'none';
            } else {
                logoImg.style.display = 'none';
                noLogoText.style.display = 'block';
            }
            
            document.getElementById('editCompanyModal').style.display = 'block';
        }

        function closeEditCompanyModal() {
            document.getElementById('editCompanyModal').style.display = 'none';
        }

        function editCompanyAdmin(id, fullName, email, companyId) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_full_name').value = fullName;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_company_id_select').value = companyId;
            document.getElementById('edit_password').value = '';
            document.getElementById('editCompanyAdminModal').style.display = 'block';
        }

        function closeEditCompanyAdminModal() {
            document.getElementById('editCompanyAdminModal').style.display = 'none';
        }

        function addBalanceToUser(email, name) {
            document.getElementById('user_email').value = email;
            document.getElementById('amount').focus();
            
           
            const balanceSection = document.querySelector('h3');
            const allH3s = document.querySelectorAll('h3');
            let targetH3 = null;
            
          
            allH3s.forEach(h3 => {
                if (h3.innerHTML.includes('fa-wallet')) {
                    targetH3 = h3;
                }
            });
            
            if (targetH3) {
                targetH3.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }

        // Logo dosyası seçildiğinde önizleme göster
        document.addEventListener('DOMContentLoaded', function() {
            // Firma ekleme formu için logo önizleme
            const addLogoInput = document.getElementById('company_logo');
            if (addLogoInput) {
                addLogoInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    const previewDiv = document.getElementById('add_logo_preview');
                    const previewImg = document.getElementById('add_logo_preview_img');
                    
                    if (file) {
                        // Dosya boyutu kontrolü
                        if (file.size > 2097152) {
                            alert('Logo dosyası 2MB\'den büyük olamaz.');
                            e.target.value = '';
                            previewDiv.style.display = 'none';
                            return;
                        }
                        // Dosya türü kontrolü
                        if (!file.type.match('image/png')) {
                            alert('Sadece PNG formatında logo yükleyebilirsiniz.');
                            e.target.value = '';
                            previewDiv.style.display = 'none';
                            return;
                        }
                        
                        // Önizleme göster
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            previewImg.src = event.target.result;
                            previewDiv.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        previewDiv.style.display = 'none';
                    }
                });
            }
            
            // Düzenleme formu için logo önizleme
            const editLogoInput = document.getElementById('edit_company_logo');
            if (editLogoInput) {
                editLogoInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        // Dosya boyutu kontrolü
                        if (file.size > 2097152) {
                            alert('Logo dosyası 2MB\'den büyük olamaz.');
                            e.target.value = '';
                            return;
                        }
                        // Dosya türü kontrolü
                        if (!file.type.match('image/png')) {
                            alert('Sadece PNG formatında logo yükleyebilirsiniz.');
                            e.target.value = '';
                            return;
                        }
                        
                        // Önizleme göster
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            const logoImg = document.getElementById('current_logo_img');
                            const noLogoText = document.getElementById('no_logo_text');
                            logoImg.src = event.target.result;
                            logoImg.style.display = 'block';
                            noLogoText.style.display = 'none';
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        });

        // Dışına tıklandığında modalleri kapat
        window.onclick = function(event) {
            const companyModal = document.getElementById('editCompanyModal');
            const adminModal = document.getElementById('editCompanyAdminModal');
            
            if (event.target === companyModal) {
                closeEditCompanyModal();
            }
            if (event.target === adminModal) {
                closeEditCompanyAdminModal();
            }
        }

        // Düzenleme yapılıyorsa kupon formuna kaydır
        <?php if ($editing_coupon): ?>
            window.addEventListener('DOMContentLoaded', function() {
                const couponSection = document.querySelector('h3');
                const allH3s = document.querySelectorAll('h3');
                let targetH3 = null;
                
             
                allH3s.forEach(h3 => {
                    if (h3.innerHTML.includes('fa-globe')) {
                        targetH3 = h3;
                    }
                });
                
                if (targetH3) {
                    targetH3.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        <?php endif; ?>
    </script>

    <?php renderFooter(); ?>

</body>
</html>