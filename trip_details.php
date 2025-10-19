<?php
require_once 'config.php';


// Sadece bilet satın alma için giriş gerektir

$db = getDB();

// URL'den sefer ID'sini al
$trip_id = $_GET['id'] ?? null;
if (!$trip_id) {
    redirect('index_bus.php');
}

// Sefer detaylarını al
$stmt = $db->prepare("
    SELECT t.*, bc.name as company_name, bc.logo_path 
    FROM trips t 
    JOIN bus_companies bc ON t.company_id = bc.id 
    WHERE t.id = ?
");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch();

if (!$trip) {
    redirect('index_bus.php');
}

// Seferden koltuk düzenini al
$seat_layout = $trip['seat_layout'] ?? '2+1';

// Bu sefer için rezerve edilmiş koltukları al
$booked_seats = getBookedSeats($trip_id);

// Koltuk seçimi ve rezervasyonu işle
$error = '';
$success = '';

// Seçilen koltukları oturumdan başlat
$session_key = 'selected_seats_' . $trip_id;
$selected_seats = $_SESSION[$session_key] ?? [];

// Koltuk seçimini işle (koltuk seçimi için GET parametresi)
if (isset($_GET['select_seat'])) {
    $seat_num = (int)$_GET['select_seat'];
    if (!in_array($seat_num, $booked_seats)) {
        if (in_array($seat_num, $selected_seats)) {
          
            $selected_seats = array_values(array_diff($selected_seats, [$seat_num]));
            
            $_SESSION[$session_key] = $selected_seats;
        } else {
           
            if (count($selected_seats) < 5) {
                $selected_seats[] = $seat_num;
               
                $_SESSION[$session_key] = $selected_seats;
            } else {
                $error = 'En fazla 5 koltuk seçebilirsiniz. Mevcut seçimlerinizi koruyoruz.';
             
             
                $selected_seats = $_SESSION[$session_key] ?? [];
            }
        }
    } else {
        $error = 'Bu koltuk daha önce satın alınmış.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $departure_time = new DateTime($trip['departure_time']);
    $current_time = new DateTime();
    
    if ($departure_time <= $current_time) {
        $error = 'Bu seferin kalkış saati geçmiş olduğu için bilet satın alınamaz.';
    }
    
    elseif (!isset($_SESSION['user_id'])) {
        $error = 'Bilet satın almak için giriş yapmanız gerekiyor.';
    } elseif (getUserRole() !== 'user') {
        $error = 'Sadece yolcu kullanıcıları bilet satın alabilir.';
    } elseif (isset($_POST['selected_seats']) && !empty($_POST['selected_seats'])) {
        $selected_seats = $_POST['selected_seats'];
        
        
        $conflict_seats = array_intersect($selected_seats, $booked_seats);
        if (!empty($conflict_seats)) {
            $error = 'Seçilen koltuklardan bazıları dolu: ' . implode(', ', $conflict_seats);
            $selected_seats = []; 
            $_SESSION[$session_key] = []; 
        } else {
            
            $total_price = count($selected_seats) * $trip['price'];
            
            
            $discount = 0;
            $coupon_used = null;
            if (!empty($_POST['coupon_code'])) {
                $stmt = $db->prepare("
                    SELECT id, discount_type, discount_value, min_amount, max_uses, expiry_date, company_id 
                    FROM coupons 
                    WHERE code = ? AND expiry_date > datetime('now')
                ");
                $stmt->execute([$_POST['coupon_code']]);
                $coupon = $stmt->fetch();
                
                if ($coupon) {
                  
                    $is_global_coupon = $coupon['company_id'] === null;
                    $is_company_coupon = $coupon['company_id'] === $trip['company_id'];
                    
                    if ($is_global_coupon || $is_company_coupon) {
                        // Minimum tutarı kontrol et
                        if ($total_price >= $coupon['min_amount']) {
                            // İndirimi hesapla
                            if ($coupon['discount_type'] === 'percentage') {
                                $discount = $total_price * ($coupon['discount_value'] / 100);
                            } else {
                                $discount = $coupon['discount_value'];
                            }
                            
                            // İndirimin toplam fiyatı aşmadığından emin ol
                            $discount = min($discount, $total_price);
                            $total_price -= $discount;
                            $coupon_used = $_POST['coupon_code'];
                        } else {
                            $error = 'Kupon için minimum ' . formatCurrency($coupon['min_amount']) . ' tutarında sepet gerekli.';
                            $selected_seats = []; // Kupon hatasında seçili koltukları sıfırla
                            $_SESSION[$session_key] = []; // Oturumu temizle
                        }
                    } else {
                        $error = 'Bu kupon bu firma için geçerli değil.';
                        $selected_seats = []; // Kupon hatasında seçili koltukları sıfırla
                        $_SESSION[$session_key] = []; // Oturumu temizle
                    }
                } else {
                    $error = 'Geçersiz veya süresi dolmuş kupon kodu.';
                    $selected_seats = []; // Kupon hatasında seçili koltukları sıfırla
                    $_SESSION[$session_key] = []; // Oturumu temizle
                }
            }
            
            // Sadece kupon hatası yoksa devam et
            if (empty($error)) {
                // Kullanıcının yeterli bakiyesi olup olmadığını kontrol et
                $stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if ($user['balance'] >= $total_price) {
                // Bilet oluştur
                $ticket_id = generateUUID();
                
                // Mevcut bağlantıyı kapat ve yeni bir tane oluştur
                $db = null;
                $db = getDB();
                
                $stmt = $db->prepare("
                    INSERT INTO tickets (id, trip_id, user_id, total_price, status, created_at) 
                    VALUES (?, ?, ?, ?, 'ACTIVE', datetime('now'))
                ");
                $stmt->execute([$ticket_id, $trip_id, $_SESSION['user_id'], $total_price]);
                
                // Koltukları rezerve et
                foreach ($selected_seats as $seat_num) {
                    $stmt = $db->prepare("
                        INSERT INTO booked_seats (ticket_id, seat_number) 
                        VALUES (?, ?)
                    ");
                    $stmt->execute([$ticket_id, $seat_num]);
                }
                
                // Kullanıcı bakiyesinden düş
                $stmt = $db->prepare("
                    UPDATE users SET balance = balance - ? WHERE id = ?
                ");
                $stmt->execute([$total_price, $_SESSION['user_id']]);
                
                // Eğer kullanıldıysa kupon kullanımını kaydet
                if ($coupon_used && isset($coupon['id'])) {
                    $stmt = $db->prepare("
                        INSERT INTO user_coupons (id, coupon_id, user_id, created_at) 
                        VALUES (?, ?, ?, datetime('now'))
                    ");
                    $stmt->execute([generateUUID(), $coupon['id'], $_SESSION['user_id']]);
                }
                
                // Eğer kullanıldıysa kupon kullanımını güncelle
                if ($coupon_used) {
                    $stmt = $db->prepare("
                        UPDATE coupons SET max_uses = max_uses - 1 
                        WHERE code = ?
                    ");
                    $stmt->execute([$coupon_used]);
                }
                
                // Seçili koltukları oturumdan temizle
                unset($_SESSION[$session_key]);
                
                // Bilet detayları sayfasına yönlendir
                redirect('ticket_details.php?id=' . $ticket_id);
            } else {
                $error = 'Yetersiz bakiye. Lütfen hesabınıza para yükleyin.';
                $selected_seats = []; // Yetersiz bakiye durumunda seçili koltukları sıfırla
                $_SESSION[$session_key] = []; // Oturumu temizle
            }
            }
        }
    } else {
        $error = 'Lütfen en az bir koltuk seçin.';
    }
}

// Sefer süresini hesapla
$departure_time = new DateTime($trip['departure_time']);
$arrival_time = new DateTime($trip['arrival_time']);
$duration = $departure_time->diff($arrival_time);

// Süreyi düzgün formatla - günler dahil toplam saatleri işle
$total_hours = ($duration->days * 24) + $duration->h;
$minutes = $duration->i;

if ($total_hours == 0 && $minutes == 0) {

    $departure_timestamp = strtotime($trip['departure_time']);
    $arrival_timestamp = strtotime($trip['arrival_time']);
    $diff_seconds = $arrival_timestamp - $departure_timestamp;
    $total_hours = floor($diff_seconds / 3600);
    $minutes = floor(($diff_seconds % 3600) / 60);
}

$duration_text = $total_hours . ' saat ' . $minutes . ' dakika';

$departure_time = new DateTime($trip['departure_time']);
$current_time = new DateTime();
$is_trip_expired = $departure_time <= $current_time;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Sefer Detayları</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="css/style.css?v=4.8">
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
                        <?php renderNavbar('trip_details'); ?>
                    <?php else: ?>
                        <a href="index.php" class="nav-link" style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500; position: relative;">Ana Sayfa</a>
                        <a href="login_bus.php" class="nav-link" style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500; position: relative;">Giriş</a>
                        <a href="register_bus.php" class="nav-link" style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500; position: relative;">Kayıt Ol</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <div class="container">
        <div class="trip-details">
            <div class="main-content">
                <!-- Trip Header -->
                <div class="trip-header">
                    <div class="trip-company">
                        <img src="<?php echo $trip['logo_path']; ?>" alt="<?php echo $trip['company_name']; ?>" class="company-logo">
                        <h2><?php echo $trip['company_name']; ?></h2>
                    </div>
                    <div class="trip-route">
                        <div class="route-info">
                            <div class="city-info">
                                <h3><?php echo $trip['departure_city']; ?></h3>
                                <p><?php echo date('H:i d.m.Y', strtotime($trip['departure_time'])); ?></p>
                            </div>
                            <div class="route-arrow">
                                <i class="fas fa-arrow-right"></i>
                                <span><?php echo $duration_text; ?></span>
                            </div>
                            <div class="city-info">
                                <h3><?php echo $trip['destination_city']; ?></h3>
                                <p><?php echo date('H:i d.m.Y', strtotime($trip['arrival_time'])); ?></p>
                            </div>
                        </div>
                        <div class="trip-price">
                            <span class="price"><?php echo formatCurrency($trip['price']); ?></span>
                            <span class="price-label">kişi başı</span>
                        </div>
                    </div>
                </div>

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

                <!-- Seat Selection -->
                <div class="seat-selection">
                    <h3><i class="fas fa-chair"></i> Koltuk Seçimi</h3>
                    
                    <?php if ($is_trip_expired): ?>
                        <div class="alert alert-error" style="margin-bottom: 2rem;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Bu seferin kalkış saati geçmiş olduğu için bilet satın alınamaz.</strong>
                            <br>
                            <small>Kalkış saati: <?php echo date('d.m.Y H:i', strtotime($trip['departure_time'])); ?></small>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Seat Legend -->
                    <div class="seat-legend">
                        <div class="legend-item">
                            <div class="legend-seat available"></div>
                            <span>Müsait</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-seat selected"></div>
                            <span>Seçili</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-seat occupied"></div>
                            <span>Dolu</span>
                        </div>
                    </div>
                    
                    <form method="POST" id="seatForm">
                        <div class="bus-container">
                        
                            <div class="bus-template-simple">
                               
                                <div class="bus-body-simple">
                           
                                    <div class="seat-column-simple left-column-simple">
                                        <?php 
                                    
                                        $total_capacity = $trip['capacity'];
                                        $left_seats = [];
                                        for ($i = 1; $i <= $total_capacity; $i += 3) {
                                            $left_seats[] = $i;
                                        }
                                        foreach ($left_seats as $seat_num): 
                                            $seat_class = in_array($seat_num, $booked_seats) ? 'occupied' : (in_array($seat_num, $selected_seats) ? 'selected' : 'available');
                                            $seat_link = ($is_trip_expired || in_array($seat_num, $booked_seats)) ? '' : "?id=" . $trip_id . "&select_seat=" . $seat_num;
                                        ?>
                                        <div class="seat-row-simple">
                                            <?php if ($seat_link): ?>
                                                <a href="<?php echo $seat_link; ?>" class="seat-simple <?php echo $seat_class; ?>" data-seat="<?php echo $seat_num; ?>"><?php echo $seat_num; ?></a>
                                            <?php else: ?>
                                                <div class="seat-simple <?php echo $seat_class; ?>" data-seat="<?php echo $seat_num; ?>"><?php echo $seat_num; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                            
                                    <div class="center-aisle-simple">
                                        <div class="aisle-line-simple"></div>
                                    </div>
                                    
                                 
                                    <div class="seat-column-simple right-column-simple">
                                        <?php 
                                     
                                        $right_seat_pairs = [];
                                        for ($i = 2; $i <= $total_capacity; $i += 3) {
                                            $pair = [$i];
                                            if ($i + 1 <= $total_capacity) {
                                                $pair[] = $i + 1;
                                            }
                                            $right_seat_pairs[] = $pair;
                                        }
                                        foreach ($right_seat_pairs as $seat_pair): 
                                        ?>
                                        <div class="seat-row-simple">
                                            <?php foreach ($seat_pair as $seat_num): 
                                                $seat_class = in_array($seat_num, $booked_seats) ? 'occupied' : (in_array($seat_num, $selected_seats) ? 'selected' : 'available');
                                                $seat_link = ($is_trip_expired || in_array($seat_num, $booked_seats)) ? '' : "?id=" . $trip_id . "&select_seat=" . $seat_num;
                                            ?>
                                            <?php if ($seat_link): ?>
                                                <a href="<?php echo $seat_link; ?>" class="seat-simple <?php echo $seat_class; ?>" data-seat="<?php echo $seat_num; ?>"><?php echo $seat_num; ?></a>
                                            <?php else: ?>
                                                <div class="seat-simple <?php echo $seat_class; ?>" data-seat="<?php echo $seat_num; ?>"><?php echo $seat_num; ?></div>
                                            <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                       
                        <div style="display: none;">
                            <?php 
                            $max_seats = $trip['capacity'];
                            for ($i = 1; $i <= $max_seats; $i++): 
                            ?>
                                <input type="checkbox" id="seat-<?php echo $i; ?>" name="selected_seats[]" value="<?php echo $i; ?>" class="seat-checkbox" <?php echo in_array($i, $booked_seats) ? 'disabled' : ''; ?> <?php echo in_array($i, $selected_seats) ? 'checked' : ''; ?>>
                            <?php endfor; ?>
                        </div>
                        
                        <div class="seat-selection-summary">
                            <h4>Seçilen Koltuklar:</h4>
                            <div id="selected-seats-list">
                                <?php if (empty($selected_seats)): ?>
                                    <p style="color: #666; font-style: italic;">Henüz koltuk seçilmedi.</p>
                                <?php else: ?>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                        <?php foreach ($selected_seats as $seat_num): ?>
                                            <span style="background: #e22027; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.9rem;">Koltuk <?php echo $seat_num; ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="total-price">
                                <strong>Toplam: <span id="total-price"><?php echo formatCurrency(count($selected_seats) * $trip['price']); ?></span></strong>
                            </div>
                            
                            <div class="coupon-section">
                                <label for="coupon_code">İndirim Kuponu (İsteğe bağlı):</label>
                                <input type="text" id="coupon_code" name="coupon_code" placeholder="Kupon kodunu girin">
                            </div>
                            
                            <?php if (!isset($_SESSION['user_id'])): ?>
                                <a href="login.php" class="btn btn-primary btn-large">
                                    <i class="fas fa-sign-in-alt"></i> Giriş Yap
                                </a>
                            <?php elseif (getUserRole() !== 'user'): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-info-circle"></i> Sadece yolcu kullanıcıları bilet satın alabilir.
                                </div>
                            <?php elseif ($is_trip_expired): ?>
                                <div class="alert alert-error">
                                    <i class="fas fa-exclamation-triangle"></i> Bu seferin kalkış saati geçmiş olduğu için bilet satın alınamaz.
                                </div>
                            <?php else: ?>
                                <?php if (!empty($selected_seats)): ?>
                                    <button type="submit" class="btn btn-primary btn-large">
                                        <i class="fas fa-ticket-alt"></i> Biletleri Satın Al
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-secondary btn-large" disabled>
                                        <i class="fas fa-ticket-alt"></i> Önce Koltuk Seçin
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="sidebar">
                <div class="info-card">
                    <h3><i class="fas fa-info-circle"></i> Sefer Bilgileri</h3>
                    <div class="info-item">
                        <strong>Firma:</strong>
                        <span><?php echo $trip['company_name']; ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Güzergah:</strong>
                        <span><?php echo $trip['departure_city']; ?> - <?php echo $trip['destination_city']; ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Kalkış:</strong>
                        <span><?php echo date('d.m.Y H:i', strtotime($trip['departure_time'])); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Varış:</strong>
                        <span><?php echo date('d.m.Y H:i', strtotime($trip['arrival_time'])); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Süre:</strong>
                        <span><?php echo $duration_text; ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Fiyat:</strong>
                        <span><?php echo formatCurrency($trip['price']); ?> / kişi</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
      
        document.addEventListener('DOMContentLoaded', function() {
            
            window.addEventListener('beforeunload', function() {
                sessionStorage.setItem('scrollPosition', window.pageYOffset);
            });
            
            
            var scrollPosition = sessionStorage.getItem('scrollPosition');
            if (scrollPosition) {
                window.scrollTo(0, scrollPosition);
                sessionStorage.removeItem('scrollPosition');
            }
        });
    </script>

    <?php renderFooter(); ?>

</body>
</html>
