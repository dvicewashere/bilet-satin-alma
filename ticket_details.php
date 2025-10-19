<?php
require_once 'config.php';

requireRole(['user', 'company', 'admin']);

$ticket_id = isset($_GET['id']) ? sanitize($_GET['id']) : '';

if (empty($ticket_id)) {
    redirect('my_tickets.php');
}

$db = getDB();

// Bilet detaylarını al - bilet sahibi veya firma yöneticisinin erişimine izin ver
if ($_SESSION['user_role'] === 'company') {
    // Firma yöneticisi kendi firmasının biletlerini görüntüleyebilir
    $stmt = $db->prepare("
        SELECT t.*, tr.*, bc.name as company_name, bc.logo_path, u.full_name, u.email
        FROM tickets t
        JOIN trips tr ON t.trip_id = tr.id
        JOIN bus_companies bc ON tr.company_id = bc.id
        JOIN users u ON t.user_id = u.id
        WHERE t.id = ? AND tr.company_id = ?
    ");
    $stmt->execute([$ticket_id, $_SESSION['company_id']]);
} else {
    // Normal kullanıcı kendi biletlerini görüntüleyebilir
    $stmt = $db->prepare("
        SELECT t.*, tr.*, bc.name as company_name, bc.logo_path, u.full_name, u.email
        FROM tickets t
        JOIN trips tr ON t.trip_id = tr.id
        JOIN bus_companies bc ON tr.company_id = bc.id
        JOIN users u ON t.user_id = u.id
        WHERE t.id = ? AND t.user_id = ?
    ");
    $stmt->execute([$ticket_id, $_SESSION['user_id']]);
}
$ticket = $stmt->fetch();

if (!$ticket) {
    if ($_SESSION['user_role'] === 'company') {
        redirect('company_panel.php');
    } else {
        redirect('my_tickets.php');
    }
}

// Bu bilet için rezerve edilmiş koltukları al
$stmt = $db->prepare("
    SELECT seat_number FROM booked_seats WHERE ticket_id = ? ORDER BY seat_number
");
$stmt->execute([$ticket_id]);
$seats = $stmt->fetchAll(PDO::FETCH_COLUMN);

$error = '';
$success = '';

// Bilet iptalini işle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_ticket'])) {
    if (!canCancelTicket($ticket_id)) {
        $error = 'Kalkış saatinden 1 saatten az bir süre kaldığı için bilet iptal edilemez.';
    } else {
        $db->beginTransaction();
        
        try {
            // Bileti iptal et
            $stmt = $db->prepare("UPDATE tickets SET status = 'CANCELED' WHERE id = ?");
            $stmt->execute([$ticket_id]);
            
            // Kullanıcı bakiyesine iade et
            $stmt = $db->prepare("
                UPDATE users SET balance = balance + ? WHERE id = ?
            ");
            $stmt->execute([$ticket['total_price'], $_SESSION['user_id']]);
            
            $db->commit();
            $success = 'Bilet başarıyla iptal edildi. Ücret hesabınıza iade edildi.';
            
            // Bilet verilerini yenile
            $stmt = $db->prepare("
                SELECT t.*, tr.*, bc.name as company_name, bc.logo_path, u.full_name, u.email
                FROM tickets t
                JOIN trips tr ON t.trip_id = tr.id
                JOIN bus_companies bc ON tr.company_id = bc.id
                JOIN users u ON t.user_id = u.id
                WHERE t.id = ? AND t.user_id = ?
            ");
            $stmt->execute([$ticket_id, $_SESSION['user_id']]);
            $ticket = $stmt->fetch();
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Bilet iptal edilirken bir hata oluştu.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet Detayları - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="css/style.css?v=3.2">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header" style="background: linear-gradient(135deg, #181818 0%, #2c2c2c 100%); border-bottom: 2px solid #e22027; position: sticky; top: 0; z-index: 1000;">
        <nav class="navbar" style="padding: 1rem 0;">
            <div class="nav-container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center;">
            <div class="nav-logo">
                    <a href="index.php" style="color: #ffffff; text-decoration: none; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; gap: 10px;">
                        <img src="images/logos/dvicebilet-logo.svg" alt="DVICEBILET" style="height: 65px; filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.3)); border-radius: 5px;">
                    </a>
                </div>
                
                <div class="nav-menu" style="display: flex; gap: 2rem; align-items: center;">
                    <?php renderNavbar('ticket_details'); ?>
                </div>
            </div>
        </nav>
    </header>

    <!-- Ticket Details -->
    <section class="ticket-details-section">
        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <div class="ticket-card-print">
                <div class="ticket-header">
                    <div class="company-logo-top">
                        <img src="<?php echo $ticket['logo_path'] ?: 'images/default-company.png'; ?>" 
                             alt="<?php echo $ticket['company_name']; ?>">
                    </div>
                    <div class="ticket-info">
                        <div class="ticket-title-row">
                            <h1>OTOBÜS BİLETİ</h1>
                            <p class="status status-<?php echo strtolower($ticket['status']); ?>">
                                <?php 
                                switch($ticket['status']) {
                                    case 'ACTIVE': echo 'AKTİF'; break;
                                    case 'CANCELED': echo 'İPTAL EDİLDİ'; break;
                                    case 'EXPIRED': echo 'SÜRESİ DOLDU'; break;
                                }
                                ?>
                            </p>
                        </div>
                        <p class="ticket-number">Bilet No: <?php echo $ticket_id; ?></p>
                    </div>
                </div>
                
                <div class="ticket-content">
                    <div class="route-info">
                        <div class="departure">
                            <h3>KALKIŞ</h3>
                            <p class="city"><?php echo $ticket['departure_city']; ?></p>
                            <p class="time"><?php echo date('H:i', strtotime($ticket['departure_time'])); ?></p>
                            <p class="date"><?php echo date('d.m.Y', strtotime($ticket['departure_time'])); ?></p>
                        </div>
                        
                        <div class="route-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                        
                        <div class="arrival">
                            <h3>VARIŞ</h3>
                            <p class="city"><?php echo $ticket['destination_city']; ?></p>
                            <p class="time"><?php echo date('H:i', strtotime($ticket['arrival_time'])); ?></p>
                            <p class="date"><?php echo date('d.m.Y', strtotime($ticket['arrival_time'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="passenger-info">
                        <h3>YOLCU BİLGİLERİ</h3>
                        <p><strong>Ad Soyad:</strong> <?php echo htmlspecialchars($ticket['full_name']); ?></p>
                        <p><strong>E-posta:</strong> <?php echo htmlspecialchars($ticket['email']); ?></p>
                        <p><strong>Firma:</strong> <?php echo htmlspecialchars($ticket['company_name']); ?></p>
                    </div>
                    
                    <div class="seat-info">
                        <h3>KOLTUK BİLGİLERİ</h3>
                        <div class="seats">
                            <?php foreach ($seats as $seat): ?>
                                <span class="seat-number"><?php echo $seat; ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="price-info">
                        <h3>FİYAT BİLGİLERİ</h3>
                        <p><strong>Toplam Tutar:</strong> <?php echo formatCurrency($ticket['total_price']); ?></p>
                        <?php if ($ticket['coupon_used']): ?>
                            <p><strong>Kullanılan Kupon:</strong> <?php echo $ticket['coupon_used']; ?></p>
                        <?php endif; ?>
                        <p><strong>Satın Alma Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?></p>
                    </div>
                </div>
                
                <div class="ticket-footer">
                    <p>Bu bilet geçerlidir. Seyahat gününde yanınızda bulundurunuz.</p>
                    <p>İptal için son 1 saat içinde olmanız gerekmektedir.</p>
                </div>
            </div>
            
            <div class="ticket-actions">
                <a href="download_ticket.php?id=<?php echo $ticket_id; ?>" class="btn btn-primary">
                    <i class="fas fa-download"></i> PDF İndir
                </a>
                
                <?php if ($ticket['status'] === 'ACTIVE' && canCancelTicket($ticket_id)): ?>
                    <form method="POST" style="display: inline;" 
                          onsubmit="return confirm('Bu biletini iptal etmek istediğinizden emin misiniz?')">
                        <button type="submit" name="cancel_ticket" class="btn btn-danger">
                            <i class="fas fa-times"></i> Bilet İptal Et
                        </button>
                    </form>
                <?php endif; ?>
                
                <a href="my_tickets.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Biletlerime Dön
                </a>
            </div>
        </div>
    </section>

    <?php renderFooter(); ?>

</body>
</html>
