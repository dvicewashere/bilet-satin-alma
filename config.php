<?php //Dvice was here!
// Saat dilimi Türkiye'ye göre ayarlama
date_default_timezone_set('Europe/Istanbul');

// database ayarları
define('DB_PATH', __DIR__ . '/bus_tickets.db');

//program ayarları
define('APP_NAME', 'DviceBilet');
define('APP_URL', 'http://localhost/dvice');

// oturum yapılandırılması
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS

// oturum başlatma
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// database bağlama fonksiyonu
function getDB() {
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // tablo mevcutsa devam yoksa oluştur
        initDatabase($pdo);
        
        return $pdo;
    } catch (PDOException $e) {
        die('Veritabanı bağlantı hatası: ' . $e->getMessage());
    }
}

// veritabanı tablolarını başlatma
function initDatabase($pdo) {
    try {
        
        $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        $stmt->execute();
        $tableExists = $stmt->fetch();
        
        if (!$tableExists) {
            createTables($pdo);
        } else {
           
            migrateCurrencyData($pdo);
        }
    } catch (Exception $e) {
      
        createTables($pdo);
    }
}

function migrateCurrencyData($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE balance > 10000");
        $highBalanceCount = $stmt->fetch()['count'];
        
        if ($highBalanceCount > 0) {
            $stmt = $pdo->prepare("UPDATE users SET balance = balance / 100 WHERE balance > 10000");
            $stmt->execute();
        }
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM trips WHERE price > 1000");
        $highPriceCount = $stmt->fetch()['count'];
        
        if ($highPriceCount > 0) {
            $stmt = $pdo->prepare("UPDATE trips SET price = price / 100 WHERE price > 1000");
            $stmt->execute();
        }
        
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM tickets WHERE total_price > 1000");
        $highTicketPriceCount = $stmt->fetch()['count'];
        
        if ($highTicketPriceCount > 0) {
            $stmt = $pdo->prepare("UPDATE tickets SET total_price = total_price / 100 WHERE total_price > 1000");
            $stmt->execute();
        }
        
    } catch (Exception $e) {
        // hatayı günlüğe kaydet ve devam et
        error_log("Currency migration error: " . $e->getMessage());
    }
}

// Tüm tabloyu oluştur
function createTables($pdo) {
    // tablo oluşturma
    $sql_statements = [
        // kullanıcı tabloları
        "CREATE TABLE IF NOT EXISTS users (
            id TEXT PRIMARY KEY,
            full_name TEXT,
            email TEXT UNIQUE NOT NULL,
            role TEXT NOT NULL CHECK(role IN ('user', 'company', 'admin')),
            password TEXT NOT NULL,
            company_id TEXT,
            balance REAL DEFAULT 1000,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES bus_companies(id)
        )",
        
        // otobüs firmaları tablosu
        "CREATE TABLE IF NOT EXISTS bus_companies (
            id TEXT PRIMARY KEY,
            name TEXT UNIQUE NOT NULL,
            logo_path TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        
        // kupon tablosu
        "CREATE TABLE IF NOT EXISTS coupons (
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
        )",
        
        // kullanıcı kuponu tablosu
        "CREATE TABLE IF NOT EXISTS user_coupons (
            id TEXT PRIMARY KEY,
            coupon_id TEXT NOT NULL,
            user_id TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (coupon_id) REFERENCES coupons(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )",
        
        // seyahat tablosu
        "CREATE TABLE IF NOT EXISTS trips (
            id TEXT PRIMARY KEY,
            company_id TEXT NOT NULL,
            departure_city TEXT NOT NULL,
            destination_city TEXT NOT NULL,
            departure_time DATETIME NOT NULL,
            arrival_time DATETIME NOT NULL,
            price REAL NOT NULL,
            capacity INTEGER NOT NULL,
            seat_layout TEXT NOT NULL DEFAULT 'standard',
            created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES bus_companies(id)
        )",
        
        // bilet tablosu
        "CREATE TABLE IF NOT EXISTS tickets (
            id TEXT PRIMARY KEY,
            trip_id TEXT NOT NULL,
            user_id TEXT NOT NULL,
            status TEXT DEFAULT 'ACTIVE' NOT NULL CHECK(status IN ('ACTIVE', 'CANCELED', 'EXPIRED')),
            total_price REAL NOT NULL,
            coupon_used TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (trip_id) REFERENCES trips(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )",
        
        // rezerve koltuk tablosu
        "CREATE TABLE IF NOT EXISTS booked_seats (
            id TEXT PRIMARY KEY,
            ticket_id TEXT NOT NULL,
            seat_number INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ticket_id) REFERENCES tickets(id)
        )"
    ];
    
    foreach ($sql_statements as $sql) {
        try {
            $pdo->exec($sql);
        } catch (Exception $e) {
            // hatayı kaydet ve devam et
            error_log("SQL Error: " . $e->getMessage());
        }
    }
    
    insertSampleData($pdo);
}

function insertSampleData($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bus_companies");
        $stmt->execute();
        $companyCount = $stmt->fetch()['count'];
        
        if ($companyCount == 0) {
            $companies = [
                ['company-001', 'Metro Turizm', 'images/logos/metro.png'],
                ['company-002', 'Ulusoy Turizm', 'images/logos/ulusoy.png'],
                ['company-003', 'Varan Turizm', 'images/logos/varan.png'],
                ['company-004', 'Pamukkale Turizm', 'images/logos/pamukkale.png']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO bus_companies (id, name, logo_path) VALUES (?, ?, ?)");
            foreach ($companies as $company) {
                $stmt->execute($company);
            }
        }
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        $stmt->execute();
        $adminCount = $stmt->fetch()['count'];
        
        if ($adminCount == 0) {
            $stmt = $pdo->prepare("INSERT INTO users (id, full_name, email, role, password, balance) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute(['admin-001', 'Sistem Yöneticisi', 'admin@dvice.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0]);
        }
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
        $stmt->execute();
        $userCount = $stmt->fetch()['count'];
        
        if ($userCount == 0) {
            $stmt = $pdo->prepare("INSERT INTO users (id, full_name, email, role, password, balance) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute(['user-001', 'Test Kullanıcısı', 'yolcu@dvice.com', 'user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1000]);
        }
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'company'");
        $stmt->execute();
        $companyAdminCount = $stmt->fetch()['count'];
        
        if ($companyAdminCount == 0) {
            $companyAdmins = [
                ['company-admin-001', 'Metro Admin', 'metro@dvice.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'company-001'],
                ['company-admin-002', 'Ulusoy Admin', 'ulusoy@dvice.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'company-002']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO users (id, full_name, email, password, role, company_id, balance) VALUES (?, ?, ?, ?, 'company', ?, 0)");
            foreach ($companyAdmins as $admin) {
                $stmt->execute($admin);
            }
        }
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM coupons");
        $stmt->execute();
        $couponCount = $stmt->fetch()['count'];
        
        if ($couponCount == 0) {
            $coupons = [
                ['coupon-001', null, 'YENI10', 'percentage', 10, 0, 100, '2024-12-31 23:59:59'],
                ['coupon-002', null, 'KAMPANYA20', 'percentage', 20, 0, 50, '2024-12-31 23:59:59'],
                ['coupon-003', null, 'ERKEN15', 'percentage', 15, 0, 200, '2024-12-31 23:59:59']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO coupons (id, company_id, code, discount_type, discount_value, min_amount, max_uses, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($coupons as $coupon) {
                $stmt->execute($coupon);
            }
        }
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM trips");
        $stmt->execute();
        $tripCount = $stmt->fetch()['count'];
        
        if ($tripCount == 0) {
            $trips = [
                ['trip-001', 'company-001', 'İstanbul', 'Ankara', '2024-06-15 08:00:00', '2024-06-15 14:00:00', 150, 45, 'standard'],
                ['trip-002', 'company-001', 'İstanbul', 'İzmir', '2024-06-15 10:00:00', '2024-06-15 17:00:00', 120, 45, 'standard'],
                ['trip-003', 'company-002', 'Ankara', 'İstanbul', '2024-06-15 09:00:00', '2024-06-15 15:00:00', 150, 45, 'standard'],
                ['trip-004', 'company-003', 'İzmir', 'Antalya', '2024-06-15 11:00:00', '2024-06-15 18:00:00', 100, 45, 'standard'],
                ['trip-005', 'company-004', 'Bursa', 'İstanbul', '2024-06-15 13:00:00', '2024-06-15 15:30:00', 80, 45, 'standard']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity, seat_layout) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($trips as $trip) {
                $stmt->execute($trip);
            }
        }
        
    } catch (Exception $e) {
        error_log("Sample data error: " . $e->getMessage());
    }
}

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function formatCurrency($amount) {
    $amount = $amount ?? 0;
    return number_format($amount, 0, ',', '.') . ' TL';
}

function formatBalance($amount) {
    $amount = $amount ?? 0;
    return number_format($amount, 0, ',', '.') . ' TL';
}

function getUserRole() {
    if (!isset($_SESSION['user_id'])) {
        return 'visitor';
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    return $user ? $user['role'] : 'visitor';
}

function requireRole($allowedRoles) {
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    
    $userRole = getUserRole();
    
    if (!in_array($userRole, $allowedRoles)) {
        redirect('index.php');
    }
}

function getBookedSeats($tripId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT bs.seat_number 
        FROM booked_seats bs 
        JOIN tickets t ON bs.ticket_id = t.id 
        WHERE t.trip_id = ? AND t.status = 'ACTIVE'
    ");
    $stmt->execute([$tripId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function canCancelTicket($ticketId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT t.created_at, tr.departure_time 
        FROM tickets t 
        JOIN trips tr ON t.trip_id = tr.id 
        WHERE t.id = ? AND t.status = 'ACTIVE'
    ");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        return false;
    }
    
    $departureDateTime = new DateTime($ticket['departure_time']);
    $currentDateTime = new DateTime();
    

    $timeDifference = $departureDateTime->getTimestamp() - $currentDateTime->getTimestamp();
    

    error_log("Ticket ID: $ticketId, Departure: " . $ticket['departure_time'] . ", Current: " . $currentDateTime->format('Y-m-d H:i:s') . ", Difference: $timeDifference seconds (" . round($timeDifference/3600, 2) . " hours)");
    
    // 1 saat kuralı - kalkış saatinden 1 saatten az süre kalmış ise iptal edilemez
    // 3600 saniye = 1 saat
    if ($timeDifference <= 3600) {
        error_log("Ticket cancellation denied: Less than 1 hour remaining. Time left: " . round($timeDifference/60, 2) . " minutes");
        return false;
    }
    
    error_log("Ticket cancellation allowed: More than 1 hour remaining. Time left: " . round($timeDifference/3600, 2) . " hours");
    return true;
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header('Location: ' . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function formatPrice($price) {
    $price = $price ?? 0;
    return number_format($price, 0, ',', '.') . ' TL';
}

function formatDate($date) {
    return date('d.m.Y H:i', strtotime($date));
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}


function renderFooter() {
    ?>
    <footer class="footer" style="background: linear-gradient(135deg, #181818 0%, #2c2c2c 100%); color: #ffffff; padding: 3rem 0 1rem; margin-top: 2rem; border-top: 3px solid #e22027;">
        <div class="container">
            <div class="footer-content" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
                <div class="footer-section" style="text-align: center;">
                    <h3 style="color: #ffffff; font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;"><?php echo APP_NAME; ?></h3>
                    <p style="color: #cccccc; font-size: 1rem; line-height: 1.6;">Türkiye'nin en güvenilir otobüs bileti satış platformu</p>
                </div>
                <div class="footer-section" style="text-align: center;">
                    <h4 style="color: #ffffff; font-size: 1.2rem; font-weight: bold; margin-bottom: 1rem;">Hızlı Linkler</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 0.5rem;"><a href="index.php" style="color: #cccccc; text-decoration: none; transition: color 0.3s ease;">Ana Sayfa</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="about.php" style="color: #cccccc; text-decoration: none; transition: color 0.3s ease;">Hakkımızda</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="contact.php" style="color: #cccccc; text-decoration: none; transition: color 0.3s ease;">İletişim</a></li>
                    </ul>
                </div>
                <div class="footer-section" style="text-align: center;">
                    <h4 style="color: #ffffff; font-size: 1.2rem; font-weight: bold; margin-bottom: 1rem;">Destek</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 0.5rem;"><a href="faq.php" style="color: #cccccc; text-decoration: none; transition: color 0.3s ease;">SSS</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="terms.php" style="color: #cccccc; text-decoration: none; transition: color 0.3s ease;">Kullanım Şartları</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="privacy.php" style="color: #cccccc; text-decoration: none; transition: color 0.3s ease;">Gizlilik Politikası</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom" style="text-align: center; padding-top: 2rem; border-top: 1px solid rgba(226, 32, 39, 0.2);">
                <p style="color: #cccccc; font-size: 0.9rem; margin: 0;">&copy; 2025 DviceBilet. Tüm hakları saklıdır.</p>
            </div>
        </div>
    </footer>
    <?php
}

function renderNavbar($currentPage = '') {
    $userRole = getUserRole();
    ?>
    <div class="nav-menu" style="display: flex; gap: 2rem; align-items: center;">
        <a href="index.php" class="nav-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>" 
           style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500; position: relative;">
            Ana Sayfa
        </a>
        <?php if ($userRole === 'user'): ?>
            <a href="my_tickets.php" class="nav-link <?php echo $currentPage === 'my_tickets' ? 'active' : ''; ?>" 
               style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500; position: relative; <?php echo $currentPage === 'my_tickets' ? 'background: #e22027;' : ''; ?>">
                <i class="fas fa-bus"></i> Biletlerim
            </a>
        <?php endif; ?>
        <?php if ($userRole === 'company'): ?>
            <a href="company_panel.php" class="nav-link <?php echo $currentPage === 'company_panel' ? 'active' : ''; ?>" 
               style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500; position: relative; <?php echo $currentPage === 'company_panel' ? 'background: #e22027;' : ''; ?>">
                <i class="fas fa-building"></i> Firma Panel
            </a>
        <?php endif; ?>
        <?php if ($userRole === 'admin'): ?>
            <a href="admin_panel.php" class="nav-link <?php echo $currentPage === 'admin_panel' ? 'active' : ''; ?>" 
               style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500; position: relative; <?php echo $currentPage === 'admin_panel' ? 'background: #e22027;' : ''; ?>">
                <i class="fas fa-cog"></i> Admin Panel
            </a>
        <?php endif; ?>
        <?php if ($userRole !== 'admin'): ?>
            <a href="profile.php" class="nav-link <?php echo $currentPage === 'profile' ? 'active' : ''; ?>" 
               style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500; position: relative; <?php echo $currentPage === 'profile' ? 'background: #e22027;' : ''; ?>">
                <i class="fas fa-user"></i> Profil
            </a>
        <?php endif; ?>
        <a href="logout.php" class="nav-link" 
           style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500; position: relative;">
            Çıkış
        </a>
    </div>
    <?php
}
?>
