<?php
require_once 'config.php';

requireRole(['admin']);

$ticket_id = isset($_GET['id']) ? sanitize($_GET['id']) : '';

if (empty($ticket_id)) {
    redirect('admin_panel.php');
}

$db = getDB();

// Tüm ilgili bilgilerle bilet detaylarını al
$stmt = $db->prepare("
    SELECT 
        t.*,
        u.full_name as user_name,
        u.email as user_email,
        tr.departure_city,
        tr.destination_city,
        tr.departure_time,
        tr.arrival_time,
        tr.price as trip_price,
        bc.name as company_name,
        bc.logo_path as company_logo
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    JOIN trips tr ON t.trip_id = tr.id
    JOIN bus_companies bc ON tr.company_id = bc.id
    WHERE t.id = ?
");

$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    $_SESSION['error'] = 'Bilet bulunamadı.';
    redirect('admin_panel.php');
}

// Bu bilet için rezerve edilmiş koltukları al
$stmt = $db->prepare("
    SELECT seat_number 
    FROM booked_seats 
    WHERE ticket_id = ?
    ORDER BY seat_number
");
$stmt->execute([$ticket_id]);
$booked_seats = $stmt->fetchAll(PDO::FETCH_COLUMN);

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet Detayları - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="css/style.css?v=4.9">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #181818 !important;
            min-height: 100vh !important;
        }
        .main-content {
            background: #181818 !important;
            padding: 2rem 0 !important;
        }
        .container {
            max-width: 1200px !important;
            margin: 0 auto !important;
            padding: 0 20px !important;
        }
        .ticket-details-card {
            background: rgba(255, 255, 255, 0.95) !important;
            border-radius: 25px !important;
            padding: 3rem !important;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3) !important;
            backdrop-filter: blur(10px) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            margin-bottom: 2rem !important;
        }
        .ticket-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e22027;
        }
        .ticket-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .info-section {
            background: rgba(226, 32, 39, 0.05);
            padding: 1.5rem;
            border-radius: 15px;
            border-left: 4px solid #e22027;
        }
        .info-section h4 {
            color: #181818;
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.3rem 0;
        }
        .info-label {
            color: #666;
            font-weight: 500;
        }
        .info-value {
            color: #181818;
            font-weight: 600;
        }
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-active {
            background: #28a745;
            color: white;
        }
        .status-canceled {
            background: #dc3545;
            color: white;
        }
        .status-expired {
            background: #6c757d;
            color: white;
        }
        .seats-section {
            background: rgba(226, 32, 39, 0.05);
            padding: 1.5rem;
            border-radius: 15px;
            margin-top: 1rem;
        }
        .seats-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .seat-item {
            background: #e22027;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .back-button {
            display: inline-block;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%);
            color: #ffffff;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            text-align: center;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(226, 32, 39, 0.3);
            font-size: 1rem;
            margin-bottom: 2rem;
        }
        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(226, 32, 39, 0.4);
        }
    </style>
</head>
<body>
    <!-- Header -->
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
            <a href="admin_panel.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Admin Paneline Dön
            </a>

            <div class="ticket-details-card">
                <div class="ticket-header">
                    <h1 style="color: #181818; font-size: 2.5rem; font-weight: bold; margin-bottom: 1rem;">
                        <i class="fas fa-ticket-alt" style="color: #e22027;"></i>
                        Bilet Detayları
                    </h1>
                    <div style="display: flex; justify-content: center; align-items: center; gap: 1rem;">
                        <span style="color: #666; font-size: 1.1rem;">Bilet No: <strong><?php echo htmlspecialchars($ticket['id']); ?></strong></span>
                        <span class="status-badge status-<?php echo strtolower($ticket['status']); ?>">
                            <?php 
                            $status_labels = [
                                'ACTIVE' => 'Aktif',
                                'CANCELED' => 'İptal',
                                'EXPIRED' => 'Süresi Dolmuş'
                            ];
                            echo $status_labels[$ticket['status']] ?? $ticket['status'];
                            ?>
                        </span>
                    </div>
                </div>

                <div class="ticket-info-grid">
                    <!-- Yolcu Bilgileri -->
                    <div class="info-section">
                        <h4><i class="fas fa-user"></i> Yolcu Bilgileri</h4>
                        <div class="info-item">
                            <span class="info-label">Ad Soyad:</span>
                            <span class="info-value"><?php echo htmlspecialchars($ticket['user_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">E-posta:</span>
                            <span class="info-value"><?php echo htmlspecialchars($ticket['user_email']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Satın Alma Tarihi:</span>
                            <span class="info-value"><?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?></span>
                        </div>
                    </div>

                    <!-- Seyahat Bilgileri -->
                    <div class="info-section">
                        <h4><i class="fas fa-bus"></i> Sefer Bilgileri</h4>
                        <div class="info-item">
                            <span class="info-label">Firma:</span>
                            <span class="info-value"><?php echo htmlspecialchars($ticket['company_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Güzergah:</span>
                            <span class="info-value"><?php echo htmlspecialchars($ticket['departure_city']); ?> → <?php echo htmlspecialchars($ticket['destination_city']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Kalkış:</span>
                            <span class="info-value"><?php echo date('d.m.Y H:i', strtotime($ticket['departure_time'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Varış:</span>
                            <span class="info-value"><?php echo date('d.m.Y H:i', strtotime($ticket['arrival_time'])); ?></span>
                        </div>
                    </div>

                    <!-- ödeme bilgileri -->
                    <div class="info-section">
                        <h4><i class="fas fa-credit-card"></i> Ödeme Bilgileri</h4>
                        <div class="info-item">
                            <span class="info-label">Sefer Ücreti:</span>
                            <span class="info-value"><?php echo formatCurrency($ticket['trip_price']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Toplam Tutar:</span>
                            <span class="info-value" style="color: #e22027; font-size: 1.2rem;"><?php echo formatCurrency($ticket['total_price']); ?></span>
                        </div>
                        <?php if ($ticket['coupon_used']): ?>
                        <div class="info-item">
                            <span class="info-label">Kullanılan Kupon:</span>
                            <span class="info-value"><?php echo htmlspecialchars($ticket['coupon_used']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- koltuk bilgileri-->
                <?php if (!empty($booked_seats)): ?>
                <div class="seats-section">
                    <h4 style="color: #000; margin-bottom: 1rem;"><i class="fas fa-chair"></i> Rezerve Edilen Koltuklar</h4>
                    <div class="seats-grid">
                        <?php foreach ($booked_seats as $seat): ?>
                            <div class="seat-item"><?php echo $seat; ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- firma logosu -->
                <?php if ($ticket['company_logo']): ?>
                <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e9ecef;">
                    <img src="<?php echo htmlspecialchars($ticket['company_logo']); ?>" 
                         alt="<?php echo htmlspecialchars($ticket['company_name']); ?>" 
                         style="max-width: 200px; max-height: 100px; object-fit: contain; border-radius: 10px; border: 2px solid #e22027;">
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php renderFooter(); ?>

</body>
</html>
