<?php
require_once 'config.php';

// Arama parametrelerinin var olup olmadığını kontrol et
if (!isset($_SESSION['search_params'])) {
    redirect('index.php');
}

$search = $_SESSION['search_params'];
$db = getDB();

// Arama sonuçlarını al
$stmt = $db->prepare("
    SELECT t.*, bc.name as company_name, bc.logo_path,
           COUNT(t.id) as total_tickets,
           (SELECT COUNT(*) FROM booked_seats bs 
            JOIN tickets tk ON bs.ticket_id = tk.id 
            WHERE tk.trip_id = t.id AND tk.status = 'ACTIVE') as booked_seats
    FROM trips t 
    JOIN bus_companies bc ON t.company_id = bc.id 
    WHERE t.departure_city LIKE ? AND t.destination_city LIKE ? 
    AND DATE(t.departure_time) = ?
    GROUP BY t.id
    ORDER BY t.departure_time ASC
");

$stmt->execute([
    '%' . $search['departure_city'] . '%',
    '%' . $search['destination_city'] . '%',
    $search['departure_date']
]);

$search_results = $stmt->fetchAll();

// Arama parametrelerini temizle
unset($_SESSION['search_params']);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arama Sonuçları - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="css/style.css?v=4.1">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
            <div class="nav-logo">
                <a href="index.php" style="color: #ffffff; text-decoration: none; font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; gap: 10px;">
                    <img src="images/logos/dvicebilet-logo.svg" alt="DVICEBILET" style="height: 65px; filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.3)); border-radius: 5px;">
                </a>
            </div>
                
                <div class="nav-menu" style="display: flex; gap: 2rem; align-items: center;">
                    <?php if (isLoggedIn()): ?>
                        <?php renderNavbar('search_results'); ?>
                    <?php else: ?>
                        <a href="index.php" class="nav-link" style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500; position: relative;">Ana Sayfa</a>
                        <a href="login_bus.php" class="nav-link" style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500; position: relative;">Giriş</a>
                        <a href="register_bus.php" class="nav-link" style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500; position: relative;">Kayıt Ol</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <!-- Search Results -->
    <section class="search-results-section">
        <div class="container">
            <div class="modern-card">
                <h2><i class="fas fa-search"></i> Arama Sonuçları</h2>
                <div class="modern-grid">
                    <div class="modern-grid-item">
                        <h4><i class="fas fa-route"></i> Güzergah</h4>
                        <p><?php echo htmlspecialchars($search['departure_city']); ?> → <?php echo htmlspecialchars($search['destination_city']); ?></p>
                    </div>
                    <div class="modern-grid-item">
                        <h4><i class="fas fa-calendar"></i> Tarih</h4>
                        <p><?php echo date('d.m.Y', strtotime($search['departure_date'])); ?></p>
                    </div>
                    <div class="modern-grid-item">
                        <h4><i class="fas fa-list"></i> Bulunan Sefer</h4>
                        <p><?php echo count($search_results); ?> adet</p>
                    </div>
                    <div class="modern-grid-item">
                        <a href="index.php" class="modern-btn">
                            <i class="fas fa-arrow-left"></i> Yeni Arama
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if (empty($search_results)): ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>Arama kriterlerinize uygun sefer bulunamadı</h3>
                    <p>Farklı tarih veya güzergah deneyebilirsiniz.</p>
                    <a href="index.php" class="modern-btn">Yeni Arama Yap</a>
                </div>
            <?php else: ?>
                <div class="modern-grid">
                    <?php foreach ($search_results as $trip): ?>
                        <div class="modern-grid-item">
                            <div class="trip-info">
                                <div class="company-info">
                                    <img src="<?php echo $trip['logo_path'] ?: 'images/default-company.png'; ?>" 
                                         alt="<?php echo $trip['company_name']; ?>" class="company-logo">
                                    <span class="company-name"><?php echo $trip['company_name']; ?></span>
                                </div>
                                
                                <div class="route-info">
                                    <div class="departure">
                                        <strong><?php echo $trip['departure_city']; ?></strong>
                                        <span><?php echo date('H:i', strtotime($trip['departure_time'])); ?></span>
                                        <small><?php echo date('d.m', strtotime($trip['departure_time'])); ?></small>
                                    </div>
                                    
                                    <div class="route-arrow">
                                    <i class="fas fa-arrow-right"></i>
                                    </div>
                                    
                                    <div class="arrival">
                                        <strong><?php echo $trip['destination_city']; ?></strong>
                                        <span><?php echo date('H:i', strtotime($trip['arrival_time'])); ?></span>
                                        <small><?php echo date('d.m', strtotime($trip['arrival_time'])); ?></small>
                                    </div>
                                </div>
                                

                            </div>
                            
                            <div class="trip-price">
                                <div class="price"><?php echo formatCurrency($trip['price']); ?></div>
                                <small>kişi başı</small>
                                <a href="trip_details.php?id=<?php echo $trip['id']; ?>" class="modern-btn">
                                    <i class="fas fa-eye"></i> Detaylar
                                </a>
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
