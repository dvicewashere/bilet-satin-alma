<?php
require_once 'config.php';

requireRole(['user']);

// Firma kullanıcıları bu sayfaya erişmemeli
if (getUserRole() === 'company') {
    redirect('company_panel.php');
}

$db = getDB();

// Kullanıcının biletlerini veya firma yöneticisinin firma biletlerini al
$user_role = getUserRole();
if ($user_role === 'company') {
    // Firma yöneticisi firmasının tüm biletlerini görür
    $stmt = $db->prepare("
        SELECT t.*, tr.departure_city, tr.destination_city, tr.departure_time, tr.arrival_time, 
               bc.name as company_name, bc.logo_path, COALESCE(COUNT(bs.id), 0) as seat_count,
               u.name as passenger_name, u.email as passenger_email
        FROM tickets t
        JOIN trips tr ON t.trip_id = tr.id
        JOIN bus_companies bc ON tr.company_id = bc.id
        JOIN users u ON t.user_id = u.id
        LEFT JOIN booked_seats bs ON t.id = bs.ticket_id
        WHERE bc.id = (SELECT company_id FROM users WHERE id = ?)
        GROUP BY t.id
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
} else {
    // Normal kullanıcı sadece kendi biletlerini görür
    $stmt = $db->prepare("
        SELECT t.*, tr.departure_city, tr.destination_city, tr.departure_time, tr.arrival_time,
               bc.name as company_name, bc.logo_path,
               COALESCE(COUNT(bs.id), 0) as seat_count
        FROM tickets t
        JOIN trips tr ON t.trip_id = tr.id
        JOIN bus_companies bc ON tr.company_id = bc.id
        LEFT JOIN booked_seats bs ON t.id = bs.ticket_id
        WHERE t.user_id = ?
        GROUP BY t.id
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
}
$tickets = $stmt->fetchAll();

// Kullanıcı bakiyesini al
$stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biletlerim - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="css/style.css?v=4.9">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body style="background: #181818; min-height: 100vh; position: relative; overflow-x: hidden;">
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
                    <?php renderNavbar('my_tickets'); ?>
                </div>
            </div>
        </nav>
    </header>

    <!-- My Tickets Section -->
    <section class="my-tickets-section" style="padding: 2rem 0; background: #181818;">
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px; position: relative; z-index: 1;">
            <div class="page-header" style="background: rgba(255, 255, 255, 0.95); border-radius: 25px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); position: relative; overflow: hidden;">
                <div style="text-align: center; margin-bottom: 1rem;">
                    <h1 style="color: #181818; font-size: 2.5rem; font-weight: bold; margin-bottom: 1rem;">Biletlerim</h1>
                    <div style="width: 60px; height: 4px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 2px; margin: 0 auto;"></div>
                </div>
                <div class="user-balance" style="text-align: center; padding: 1rem; background: rgba(226, 32, 39, 0.1); border-radius: 15px; border: 2px solid #e22027;">
                    <i class="fas fa-wallet" style="color: #e22027; font-size: 1.2rem; margin-right: 0.5rem;"></i>
                    <span style="color: #181818; font-size: 1.1rem; font-weight: bold;">Bakiyem: <?php echo formatBalance($user['balance']); ?></span>
                </div>
            </div>
            
            <?php if (empty($tickets)): ?>
                <div class="empty-state" style="background: rgba(255, 255, 255, 0.95); border-radius: 25px; padding: 3rem; text-align: center; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); position: relative; overflow: hidden;">
                    <i class="fas fa-ticket-alt" style="font-size: 4rem; color: #e22027; margin-bottom: 1rem;"></i>
                    <h3 style="color: #181818; font-size: 1.8rem; font-weight: bold; margin-bottom: 1rem;">Henüz biletiniz yok</h3>
                    <p style="color: #666; font-size: 1.1rem; margin-bottom: 2rem;">İlk biletinizi satın almak için arama yapın.</p>
                    <a href="index.php" class="modern-btn" style="display: inline-block; padding: 1.2rem 2rem; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); color: #ffffff; text-decoration: none; border-radius: 25px; font-weight: 600; text-align: center; border: none; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(226, 32, 39, 0.3); font-size: 1.1rem;">
                        <i class="fas fa-search" style="margin-right: 8px; color: #ffffff !important; font-size: 1.2rem;"></i>
                        Bilet Ara
                    </a>
                </div>
            <?php else: ?>
                <div class="modern-grid">
                    <?php foreach ($tickets as $ticket): ?>
                        <div class="modern-grid-item">
                            <div class="ticket-company-header">
                                <div class="company-info-new" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                    <img src="<?php echo $ticket['logo_path'] ?: 'images/default-company.png'; ?>" 
                                         alt="<?php echo $ticket['company_name']; ?>" class="company-logo-new">
                                    <span class="company-name-new"><?php echo $ticket['company_name']; ?></span>
                                    <?php if ($ticket['status'] === 'CANCELED'): ?>
                                        <span class="status-badge-header status-canceled-header">İPTAL</span>
                                    <?php elseif ($ticket['status'] === 'ACTIVE'): ?>
                                        <span class="status-badge-header status-active-header">AKTIF</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="ticket-route-new">
                                <div class="departure-new">
                                    <strong><?php echo $ticket['departure_city']; ?></strong>
                                    <span class="time-new"><?php echo date('H:i', strtotime($ticket['departure_time'])); ?></span>
                                </div>
                                
                                <div class="route-arrow-new">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                                
                                <div class="arrival-new">
                                    <strong><?php echo $ticket['destination_city']; ?></strong>
                                    <span class="time-new"><?php echo date('H:i', strtotime($ticket['arrival_time'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="ticket-details-new">
                                <div class="detail-item-new">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo date('d.m.Y', strtotime($ticket['departure_time'])); ?></span>
                                </div>
                                
                                <div class="detail-item-new">
                                    <i class="fas fa-chair"></i>
                                    <span>
                                        <?php 
                                     
                                        if ($ticket['seat_count'] == 0) {
                                            // Koltuk sayısını hesaplamak için sefer fiyatını al
                                            $price_stmt = $db->prepare("SELECT price FROM trips WHERE id = ?");
                                            $price_stmt->execute([$ticket['trip_id']]);
                                            $trip_price = $price_stmt->fetch();
                                            
                                            if ($trip_price && $trip_price['price'] > 0) {
                                                $calculated_seats = round($ticket['total_price'] / $trip_price['price']);
                                                echo $calculated_seats . ' koltuk';
                                            } else {
                                                echo '1 koltuk'; 
                                            }
                                        } else {
                                            echo $ticket['seat_count'] . ' koltuk';
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <div class="detail-item-new">
                                    <i class="fas fa-lira-sign"></i>
                                    <span><?php echo formatCurrency($ticket['total_price']); ?></span>
                                </div>
                                
                                <?php if ($ticket['coupon_used']): ?>
                                    <div class="detail-item-new">
                                        <i class="fas fa-tag"></i>
                                        <span>Kupon: <?php echo $ticket['coupon_used']; ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="ticket-actions-new">
                                <a href="ticket_details.php?id=<?php echo $ticket['id']; ?>" 
                                   class="btn btn-outline btn-sm">
                                    <i class="fas fa-eye"></i> Detaylar
                                </a>
                                
                                <?php if ($ticket['status'] === 'ACTIVE'): ?>
                                    <a href="download_ticket.php?id=<?php echo $ticket['id']; ?>" 
                                       class="btn btn-outline btn-sm">
                                        <i class="fas fa-download"></i> PDF İndir
                                    </a>
                                    
                                    <?php 
                                    // İptalin izin verilip verilmediğini kontrol et (1 saat kuralı)
                                    $departureDateTime = new DateTime($ticket['departure_time']);
                                    $currentDateTime = new DateTime();
                                    $timeDifference = $departureDateTime->getTimestamp() - $currentDateTime->getTimestamp();
                                    $canCancel = $timeDifference > 3600; // 3600 saniye = 1 saat
                                    ?>
                                    
                                    <?php if ($canCancel): ?>
                                        <form method="POST" action="cancel_ticket.php" style="display: inline;"
                                              onsubmit="return confirm('Bu biletini iptal etmek istediğinizden emin misiniz?')">
                                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                            <button type="submit" class="btn btn-outline btn-sm">
                                                <i class="fas fa-times"></i> İptal Et
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-disabled btn-sm" disabled onclick="alert('Kalkış saatinden 1 saatten az bir süre kaldığı için bilet iptal edilemez.')">
                                            <i class="fas fa-clock"></i> İptal Edilemez
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php renderFooter(); ?>

</body>
</html>
