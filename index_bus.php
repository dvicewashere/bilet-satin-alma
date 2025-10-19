<?php
require_once 'config.php';

// Veritabanı bağlantısını başlat
$db = getDB();

$error = '';
$success = '';

// Sefer arama işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_trips'])) {
    $departure_city = sanitize($_POST['departure_city']);
    $destination_city = sanitize($_POST['destination_city']);
    $departure_date = sanitize($_POST['departure_date']);
    
    if (empty($departure_city) || empty($destination_city) || empty($departure_date)) {
        $error = 'Lütfen tüm alanları doldurun.';
    } else {
        $_SESSION['search_params'] = [
            'departure_city' => $departure_city,
            'destination_city' => $destination_city,
            'departure_date' => $departure_date
        ];
        redirect('search_results.php');
    }
}

// Arama sonuçları varsa göster
$search_results = [];
if (isset($_SESSION['search_params'])) {
    $db = getDB();
    $search = $_SESSION['search_params'];
    
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
}

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
    <title><?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="css/style.css?v=3.2">
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
                    <?php if (isLoggedIn()): ?>
                        <?php renderNavbar('index'); ?>
                    <?php else: ?>
                        <a href="index.php" class="nav-link active" style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500; position: relative; background: #e22027;">Ana Sayfa</a>
                        <a href="login_bus.php" class="nav-link" style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500; position: relative;">Giriş</a>
                        <a href="register_bus.php" class="nav-link" style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500; position: relative;">Kayıt Ol</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero" style="background: linear-gradient(135deg, #181818 0%, #2c2c2c 100%); padding: 4rem 0; margin-bottom: -2rem; position: relative; z-index: 1;">
        <div class="hero-content" style="text-align: center; max-width: 800px; margin: 0 auto; padding: 0 2rem;">
            <h1 style="color: #ffffff; font-size: 3.5rem; font-weight: bold; margin-bottom: 1rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">Otobüs Bileti Satın Al</h1>
            <p style="color: #ffffff; font-size: 1.3rem; margin-bottom: 2rem; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">Güvenli, hızlı ve ekonomik seyahat için biletinizi hemen alın</p>
        </div>
    </section>

    <!-- Loading Animation -->
    <div id="search-loading" class="loading-container" style="display: none;">
        <div class="loading-bus">
            <div class="bus-body"></div>
            <div class="bus-wheels">
                <div class="wheel"></div>
                <div class="wheel"></div>
            </div>
            <div class="loading-text">Seferler yükleniyor...</div>
        </div>
    </div>

    <!-- Search Form -->
    <section class="search-section" style="padding: 2rem 0; background: #181818;">
        <div class="container">
            <div class="modern-card" style="background: rgba(255, 255, 255, 0.95); border-radius: 25px; padding: 3rem; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); position: relative; overflow: hidden;">
                <div style="text-align: center; margin-bottom: 2rem;">
                    <h2 style="color: #181818; font-size: 2rem; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 1rem;">
                        <i class="fas fa-search" style="color: #e22027; font-size: 1.5rem;"></i>
                        Sefer Ara
                    </h2>
                    <div style="width: 60px; height: 4px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 2px; margin: 0 auto;"></div>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error" style="background: rgba(220, 53, 69, 0.1); color: #dc3545; border: 2px solid #dc3545; padding: 1rem; border-radius: 15px; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="searchForm" class="modern-form">
                    <div class="modern-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
                        <div class="form-group">
                            <label style="color: #181818; font-weight: 600; margin-bottom: 0.5rem; display: block;">Nereden</label>
                            <select name="departure_city" required style="width: 100%; padding: 1rem; border: 2px solid #e0e0e0; border-radius: 15px; font-size: 1rem; background: rgba(255, 255, 255, 0.9); color: #181818; transition: all 0.3s ease;">
                                <option value="">Kalkış şehri seçin</option>
                                <?php foreach ($all_cities as $city): ?>
                                    <option value="<?php echo $city; ?>"><?php echo $city; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label style="color: #181818; font-weight: 600; margin-bottom: 0.5rem; display: block;">Nereye</label>
                            <select name="destination_city" required style="width: 100%; padding: 1rem; border: 2px solid #e0e0e0; border-radius: 15px; font-size: 1rem; background: rgba(255, 255, 255, 0.9); color: #181818; transition: all 0.3s ease;">
                                <option value="">Varış şehri seçin</option>
                                <?php foreach ($all_cities as $city): ?>
                                    <option value="<?php echo $city; ?>"><?php echo $city; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label style="color: #181818; font-weight: 600; margin-bottom: 0.5rem; display: block;">Tarih</label>
                            <input type="date" name="departure_date" required 
                                   min="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 1rem; border: 2px solid #e0e0e0; border-radius: 15px; font-size: 1rem; background: rgba(255, 255, 255, 0.9); color: #181818; transition: all 0.3s ease;">
                        </div>
                        
                        <div class="form-group" style="display: flex; align-items: end;">
                            <button type="submit" name="search_trips" class="modern-btn" style="width: 100%; padding: 1.2rem; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); color: #ffffff; text-decoration: none; border-radius: 25px; font-weight: 600; text-align: center; border: none; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(226, 32, 39, 0.3); font-size: 1.1rem;">
                                <i class="fas fa-search" style="margin-right: 8px;"></i>
                                Sefer Ara
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Search Results -->
    <?php if (!empty($search_results)): ?>
        <section class="search-results" style="padding: 2rem 0; background: #181818;">
            <div class="container">
                <div class="modern-card" style="background: rgba(255, 255, 255, 0.95); border-radius: 25px; padding: 3rem; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); position: relative; overflow: hidden;">
                    <div style="text-align: center; margin-bottom: 2rem;">
                        <h2 style="color: #181818; font-size: 2rem; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 1rem;">
                            <i class="fas fa-list" style="color: #e22027; font-size: 1.5rem;"></i>
                            Arama Sonuçları
                        </h2>
                        <div style="width: 60px; height: 4px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 2px; margin: 0 auto;"></div>
                    </div>
                    
                    <div class="modern-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                        <?php foreach ($search_results as $trip): ?>
                            <div class="modern-grid-item" style="background: rgba(255, 255, 255, 0.9); border-radius: 20px; padding: 2rem; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); position: relative; overflow: hidden;">
                                <div class="trip-info">
                                    <div class="company-info" style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #e22027;">
                                        <img src="<?php echo $trip['logo_path'] ?: 'images/default-company.png'; ?>" 
                                             alt="<?php echo $trip['company_name']; ?>" class="company-logo" style="width: 50px; height: 50px; border-radius: 10px; object-fit: cover;">
                                        <span class="company-name" style="font-size: 1.2rem; font-weight: bold; color: #181818;"><?php echo $trip['company_name']; ?></span>
                                    </div>
                            
                                    <div class="route-info" style="display: flex; justify-content: space-between; align-items: center; margin: 1.5rem 0; padding: 1rem; background: rgba(226, 32, 39, 0.1); border-radius: 15px;">
                                        <div class="departure" style="text-align: center;">
                                            <strong style="display: block; font-size: 1.1rem; color: #181818; margin-bottom: 0.5rem;"><?php echo $trip['departure_city']; ?></strong>
                                            <span style="font-size: 1.2rem; color: #e22027; font-weight: bold;"><?php echo date('H:i', strtotime($trip['departure_time'])); ?></span>
                                        </div>
                                        
                                        <div class="route-arrow" style="font-size: 1.5rem; color: #e22027;">
                                            <i class="fas fa-arrow-right"></i>
                                        </div>
                                        
                                        <div class="arrival" style="text-align: center;">
                                            <strong style="display: block; font-size: 1.1rem; color: #181818; margin-bottom: 0.5rem;"><?php echo $trip['destination_city']; ?></strong>
                                            <span style="font-size: 1.2rem; color: #e22027; font-weight: bold;"><?php echo date('H:i', strtotime($trip['arrival_time'])); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="trip-details" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin: 1rem 0;">
                                        <div class="detail-item" style="display: flex; align-items: center; gap: 0.5rem; color: #181818;">
                                            <i class="fas fa-clock" style="color: #e22027;"></i>
                                            <span>
                                                <?php 
                                                $departure = new DateTime($trip['departure_time']);
                                                $arrival = new DateTime($trip['arrival_time']);
                                                $diff = $departure->diff($arrival);
                                                echo $diff->format('%h saat %i dakika');
                                                ?>
                                            </span>
                                        </div>
                                        <div class="detail-item" style="display: flex; align-items: center; gap: 0.5rem; color: #181818;">
                                            <i class="fas fa-chair" style="color: #e22027;"></i>
                                            <span><?php echo ($trip['capacity'] - $trip['booked_seats']); ?> koltuk kaldı</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="trip-price" style="text-align: center; margin: 1.5rem 0;">
                                    <div class="price" style="font-size: 2rem; font-weight: bold; color: #e22027; margin-bottom: 1rem;"><?php echo formatCurrency($trip['price']); ?></div>
                                    <a href="trip_details.php?id=<?php echo $trip['id']; ?>" class="btn btn-primary" style="display: inline-block; padding: 1rem 2rem; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); color: #ffffff; text-decoration: none; border-radius: 25px; font-weight: 600; text-align: center; border: none; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(226, 32, 39, 0.3);">
                                        <i class="fas fa-eye" style="margin-right: 8px;"></i>
                                        Detaylar
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Features -->
    <section class="features" style="padding: 2rem 0; background: #181818;">
        <div class="container">
            <div class="modern-card" style="background: rgba(255, 255, 255, 0.95); border-radius: 25px; padding: 3rem; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); position: relative; overflow: hidden;">
                <div style="text-align: center; margin-bottom: 2rem;">
                    <h2 style="color: #181818; font-size: 2rem; font-weight: bold; display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 1rem;">
                        <i class="fas fa-star" style="color: #e22027; font-size: 1.5rem;"></i>
                        Neden Bizi Seçmelisiniz?
                    </h2>
                    <div style="width: 60px; height: 4px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 2px; margin: 0 auto;"></div>
                </div>
                
                <div class="modern-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                    <div class="modern-grid-item" style="background: rgba(255, 255, 255, 0.9); border-radius: 20px; padding: 2rem; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); text-align: center; position: relative; overflow: hidden;">
                        <h4 style="color: #181818; font-size: 1.3rem; font-weight: bold; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; gap: 10px;">
                            <i class="fas fa-shield-alt" style="color: #e22027; font-size: 1.2rem;"></i>
                            Güvenli Ödeme
                        </h4>
                        <p style="color: #666; font-size: 1rem; line-height: 1.6;">Tüm işlemleriniz SSL sertifikası ile korunmaktadır</p>
                    </div>
                    <div class="modern-grid-item" style="background: rgba(255, 255, 255, 0.9); border-radius: 20px; padding: 2rem; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); text-align: center; position: relative; overflow: hidden;">
                        <h4 style="color: #181818; font-size: 1.3rem; font-weight: bold; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; gap: 10px;">
                            <i class="fas fa-mobile-alt" style="color: #e22027; font-size: 1.2rem;"></i>
                            Mobil Uyumlu
                        </h4>
                        <p style="color: #666; font-size: 1rem; line-height: 1.6;">Her cihazdan kolayca bilet satın alabilirsiniz</p>
                    </div>
                    <div class="modern-grid-item" style="background: rgba(255, 255, 255, 0.9); border-radius: 20px; padding: 2rem; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); text-align: center; position: relative; overflow: hidden;">
                        <h4 style="color: #181818; font-size: 1.3rem; font-weight: bold; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; gap: 10px;">
                            <i class="fas fa-headset" style="color: #e22027; font-size: 1.2rem;"></i>
                            7/24 Destek
                        </h4>
                        <p style="color: #666; font-size: 1rem; line-height: 1.6;">Müşteri hizmetlerimiz her zaman yanınızda</p>
                    </div>
                    <div class="modern-grid-item" style="background: rgba(255, 255, 255, 0.9); border-radius: 20px; padding: 2rem; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); text-align: center; position: relative; overflow: hidden;">
                        <h4 style="color: #181818; font-size: 1.3rem; font-weight: bold; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; gap: 10px;">
                            <i class="fas fa-undo" style="color: #e22027; font-size: 1.2rem;"></i>
                            Kolay İade
                        </h4>
                        <p style="color: #666; font-size: 1rem; line-height: 1.6;">Son 1 saate kadar biletinizi iptal edebilirsiniz</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php renderFooter(); ?>

</body>
</html>
