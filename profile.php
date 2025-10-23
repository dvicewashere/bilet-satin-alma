<?php //Dvice was here!
require_once 'config.php';

requireRole(['user', 'company']);


if (getUserRole() === 'admin') {
    redirect('admin_panel.php');
}

$db = getDB();
$message = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    
    if (empty($full_name) || empty($email)) {
        $message = '<div class="alert alert-error">Ad soyad ve e-posta gereklidir.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-error">Geçerli bir e-posta adresi girin.</div>';
    } else {
     
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $message = '<div class="alert alert-error">Bu e-posta adresi zaten kullanılıyor.</div>';
        } else {
            $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
            if ($stmt->execute([$full_name, $email, $_SESSION['user_id']])) {
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $full_name;
                $message = '<div class="alert alert-success">Profil başarıyla güncellendi.</div>';
            } else {
                $message = '<div class="alert alert-error">Profil güncellenirken bir hata oluştu.</div>';
            }
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = '<div class="alert alert-error">Tüm şifre alanları gereklidir.</div>';
    } elseif ($new_password !== $confirm_password) {
        $message = '<div class="alert alert-error">Yeni şifreler eşleşmiyor.</div>';
    } elseif (strlen($new_password) < 6) {
        $message = '<div class="alert alert-error">Yeni şifre en az 6 karakter olmalıdır.</div>';
    } else {

        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                $message = '<div class="alert alert-success">Şifre başarıyla değiştirildi.</div>';
            } else {
                $message = '<div class="alert alert-error">Şifre değiştirilirken bir hata oluştu.</div>';
            }
        } else {
            $message = '<div class="alert alert-error">Mevcut şifre yanlış.</div>';
        }
    }
}


$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();


if (getUserRole() === 'company' && isset($user['company_id'])) {
  
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_tickets,
            SUM(CASE WHEN t.status = 'ACTIVE' THEN 1 ELSE 0 END) as active_tickets,
            SUM(CASE WHEN t.status = 'CANCELED' THEN 1 ELSE 0 END) as canceled_tickets,
            SUM(CASE WHEN t.status = 'ACTIVE' THEN t.total_price ELSE 0 END) as total_earned
        FROM tickets t
        JOIN trips tr ON t.trip_id = tr.id
        WHERE tr.company_id = ?
    ");
    $stmt->execute([$user['company_id']]);
} else {
 
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_tickets,
            SUM(CASE WHEN status = 'ACTIVE' THEN 1 ELSE 0 END) as active_tickets,
            SUM(CASE WHEN status = 'CANCELED' THEN 1 ELSE 0 END) as canceled_tickets,
            SUM(CASE WHEN status = 'ACTIVE' THEN total_price ELSE 0 END) as total_spent
        FROM tickets 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
}
$stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?php echo APP_NAME; ?></title>
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
                
                <?php renderNavbar('profile'); ?>
            </div>
        </nav>
    </header>

 
    <section class="profile-section" style="padding: 2rem 0; background: #181818;">
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px; position: relative; z-index: 1;">
            <div class="profile-header" style="background: rgba(255, 255, 255, 0.95); border-radius: 25px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); position: relative; overflow: hidden;">
                <div style="text-align: center; margin-bottom: 1rem;">
                    <h1 style="color: #181818; font-size: 2.5rem; font-weight: bold; margin-bottom: 1rem;">Profil Bilgileri</h1>
                    <div style="width: 60px; height: 4px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 2px; margin: 0 auto;"></div>
                </div>
                <div class="user-balance" style="text-align: center; padding: 1rem; background: rgba(226, 32, 39, 0.1); border-radius: 15px; border: 2px solid #e22027;">
                    <i class="fas fa-wallet" style="color: #e22027; font-size: 1.2rem; margin-right: 0.5rem;"></i>
                    <span style="color: #181818; font-size: 1.1rem; font-weight: bold;">Bakiyem: <?php echo formatBalance($user['balance']); ?></span>
                </div>
            </div>
            
            <?php echo $message; ?>
            
            <div class="profile-content" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
               
                <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                    <div class="stat-card" style="background: rgba(255, 255, 255, 0.95); border-radius: 20px; padding: 2rem; text-align: center; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); position: relative; overflow: hidden;">
                        <div class="stat-number" style="font-size: 2.5rem; font-weight: bold; color: #e22027; margin-bottom: 0.5rem;"><?php echo $stats['total_tickets']; ?></div>
                        <div class="stat-label" style="font-size: 1.1rem; color: #181818; font-weight: 600;">Toplam Bilet</div>
                    </div>
                    <div class="stat-card" style="background: rgba(255, 255, 255, 0.95); border-radius: 20px; padding: 2rem; text-align: center; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); position: relative; overflow: hidden;">
                        <div class="stat-number" style="font-size: 2.5rem; font-weight: bold; color: #e22027; margin-bottom: 0.5rem;"><?php echo $stats['active_tickets']; ?></div>
                        <div class="stat-label" style="font-size: 1.1rem; color: #181818; font-weight: 600;">Aktif Bilet</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['canceled_tickets']; ?></div>
                        <div class="stat-label">İptal Edilen</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php 
                            if (getUserRole() === 'company' && isset($user['company_id'])) {
                                echo formatCurrency($stats['total_earned']);
                            } else {
                                echo formatCurrency($stats['total_spent']);
                            }
                            ?>
                        </div>
                        <div class="stat-label">
                            <?php 
                            if (getUserRole() === 'company' && isset($user['company_id'])) {
                                echo 'Toplam Gelir';
                            } else {
                                echo 'Toplam Harcama';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
              
                <div class="modern-card">
                    <h3><i class="fas fa-user-edit"></i> Profil Bilgilerini Güncelle</h3>
                    <form method="POST" class="modern-form">
                        <div class="modern-grid">
                            <div class="form-group">
                                <label for="full_name">Ad Soyad *</label>
                                <input type="text" id="full_name" name="full_name" required 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">E-posta *</label>
                                <input type="email" id="email" name="email" required 
                                       value="<?php echo htmlspecialchars($user['email']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Hesap Türü</label>
                                <input type="text" value="<?php echo ucfirst($user['role']); ?>" readonly 
                                       style="background: #f8f9fa;">
                            </div>
                            <div class="form-group">
                                <button type="submit" name="update_profile" class="modern-btn">
                                    <i class="fas fa-save"></i> Profili Güncelle
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
              
                <div class="modern-card">
                    <h3><i class="fas fa-key"></i> Şifre Değiştir</h3>
                    <form method="POST" class="modern-form">
                        <div class="modern-grid">
                            <div class="form-group">
                                <label for="current_password">Mevcut Şifre *</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label for="new_password">Yeni Şifre *</label>
                                <input type="password" id="new_password" name="new_password" required>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Yeni Şifre Tekrar *</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                            <div class="form-group">
                                <button type="submit" name="change_password" class="modern-btn">
                                    <i class="fas fa-key"></i> Şifreyi Değiştir
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <?php renderFooter(); ?>

</body>
</html>

