<?php
require_once 'config.php';

$error = '';
$success = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_form'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Lütfen tüm alanları doldurun.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta adresi girin.';
    } else {
 
        $success = 'Mesajınız başarıyla gönderildi! En kısa sürede size dönüş yapacağız.';
        
        $name = $email = $subject = $message = '';
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İletişim - <?php echo APP_NAME; ?></title>
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
                    <?php if (isLoggedIn()): ?>
                        <?php renderNavbar('contact'); ?>
                    <?php else: ?>
                        <a href="index.php" class="nav-link" style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500; position: relative;">Ana Sayfa</a>
                        <a href="login_bus.php" class="nav-link" style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500; position: relative;">Giriş</a>
                        <a href="register_bus.php" class="nav-link" style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500; position: relative;">Kayıt Ol</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <!-- Contact Section -->
    <section class="contact-section" style="padding: 4rem 0; background: #181818;">
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <!-- Page Header -->
            <div class="page-header" style="background: rgba(255, 255, 255, 0.95); border-radius: 25px; padding: 3rem; margin-bottom: 3rem; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); text-align: center;">
                <h1 style="color: #181818; font-size: 3rem; font-weight: bold; margin-bottom: 1rem;">İletişim</h1>
                <div style="width: 80px; height: 4px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 2px; margin: 0 auto 2rem;"></div>
                <p style="color: #666; font-size: 1.2rem; line-height: 1.6; max-width: 800px; margin: 0 auto;">Sorularınız, önerileriniz veya şikayetleriniz için bizimle iletişime geçin. Size yardımcı olmaktan mutluluk duyarız.</p>
            </div>

            <div class="modern-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin-bottom: 3rem;">
                <!-- Contact Form -->
                <div class="modern-card" style="background: rgba(255, 255, 255, 0.95); border-radius: 25px; padding: 3rem; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                    <h2 style="color: #181818; font-size: 2rem; font-weight: bold; margin-bottom: 2rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-envelope" style="color: #e22027; font-size: 1.5rem;"></i>
                        Bize Mesaj Gönderin
                    </h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error" style="background: rgba(220, 53, 69, 0.1); color: #dc3545; border: 2px solid #dc3545; padding: 1rem; border-radius: 15px; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success" style="background: rgba(40, 167, 69, 0.1); color: #28a745; border: 2px solid #28a745; padding: 1rem; border-radius: 15px; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="modern-form">
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label style="color: #181818; font-weight: 600; margin-bottom: 0.5rem; display: block;">Ad Soyad *</label>
                            <input type="text" name="name" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" 
                                   style="width: 100%; padding: 1rem; border: 2px solid #e0e0e0; border-radius: 15px; font-size: 1rem; background: rgba(255, 255, 255, 0.9); color: #181818; transition: all 0.3s ease;">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label style="color: #181818; font-weight: 600; margin-bottom: 0.5rem; display: block;">E-posta *</label>
                            <input type="email" name="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" 
                                   style="width: 100%; padding: 1rem; border: 2px solid #e0e0e0; border-radius: 15px; font-size: 1rem; background: rgba(255, 255, 255, 0.9); color: #181818; transition: all 0.3s ease;">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label style="color: #181818; font-weight: 600; margin-bottom: 0.5rem; display: block;">Konu *</label>
                            <select name="subject" required style="width: 100%; padding: 1rem; border: 2px solid #e0e0e0; border-radius: 15px; font-size: 1rem; background: rgba(255, 255, 255, 0.9); color: #181818; transition: all 0.3s ease;">
                                <option value="">Konu seçin</option>
                                <option value="genel" <?php echo (isset($subject) && $subject === 'genel') ? 'selected' : ''; ?>>Genel Bilgi</option>
                                <option value="bilet" <?php echo (isset($subject) && $subject === 'bilet') ? 'selected' : ''; ?>>Bilet İşlemleri</option>
                                <option value="iptal" <?php echo (isset($subject) && $subject === 'iptal') ? 'selected' : ''; ?>>İptal/İade</option>
                                <option value="teknik" <?php echo (isset($subject) && $subject === 'teknik') ? 'selected' : ''; ?>>Teknik Destek</option>
                                <option value="sikayet" <?php echo (isset($subject) && $subject === 'sikayet') ? 'selected' : ''; ?>>Şikayet</option>
                                <option value="oneri" <?php echo (isset($subject) && $subject === 'oneri') ? 'selected' : ''; ?>>Öneri</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 2rem;">
                            <label style="color: #181818; font-weight: 600; margin-bottom: 0.5rem; display: block;">Mesajınız *</label>
                            <textarea name="message" required rows="5" style="width: 100%; padding: 1rem; border: 2px solid #e0e0e0; border-radius: 15px; font-size: 1rem; background: rgba(255, 255, 255, 0.9); color: #181818; transition: all 0.3s ease; resize: vertical;"><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                        </div>
                        
                        <button type="submit" name="contact_form" class="modern-btn" style="width: 100%; padding: 1.2rem; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); color: #ffffff; text-decoration: none; border-radius: 25px; font-weight: 600; text-align: center; border: none; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(226, 32, 39, 0.3); font-size: 1.1rem;">
                            <i class="fas fa-paper-plane" style="margin-right: 8px;"></i>
                            Mesaj Gönder
                        </button>
                    </form>
                </div>

                <!-- Contact Information -->
                <div class="modern-card" style="background: rgba(255, 255, 255, 0.95); border-radius: 25px; padding: 3rem; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                    <h2 style="color: #181818; font-size: 2rem; font-weight: bold; margin-bottom: 2rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-phone" style="color: #e22027; font-size: 1.5rem;"></i>
                        İletişim Bilgileri
                    </h2>
                    
                    <div style="margin-bottom: 2rem;">
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; padding: 1rem; background: rgba(226, 32, 39, 0.1); border-radius: 15px;">
                            <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-phone" style="color: #e22027; font-size: 1.2rem;"></i>
                            </div>
                            <div>
                                <h4 style="color: #181818; font-size: 1.1rem; font-weight: bold; margin-bottom: 0.3rem;">Telefon</h4>
                                <p style="color: #666; font-size: 1rem; margin: 0;">+90 (212) 555 0123</p>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; padding: 1rem; background: rgba(226, 32, 39, 0.1); border-radius: 15px;">
                            <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-envelope" style="color: #e22027; font-size: 1.2rem;"></i>
                            </div>
                            <div>
                                <h4 style="color: #181818; font-size: 1.1rem; font-weight: bold; margin-bottom: 0.3rem;">E-posta</h4>
                                <p style="color: #666; font-size: 1rem; margin: 0;">Muhammedharunseker@gmail.com</p>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; padding: 1rem; background: rgba(226, 32, 39, 0.1); border-radius: 15px;">
                            <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-map-marker-alt" style="color: #e22027; font-size: 1.2rem;"></i>
                            </div>
                            <div>
                                <h4 style="color: #181818; font-size: 1.1rem; font-weight: bold; margin-bottom: 0.3rem;">Adres</h4>
                                <p style="color: #666; font-size: 1rem; margin: 0;">SiberVatan, Türkiye</p>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: rgba(226, 32, 39, 0.1); border-radius: 15px;">
                            <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-clock" style="color: #e22027; font-size: 1.2rem;"></i>
                            </div>
                            <div>
                                <h4 style="color: #181818; font-size: 1.1rem; font-weight: bold; margin-bottom: 0.3rem;">Çalışma Saatleri</h4>
                                <p style="color: #666; font-size: 1rem; margin: 0;">7/24 Destek</p>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: rgba(226, 32, 39, 0.1); padding: 1.5rem; border-radius: 15px; text-align: center;">
                        <h4 style="color: #181818; font-size: 1.2rem; font-weight: bold; margin-bottom: 1rem;">Sosyal Medya</h4>
                        <div style="display: flex; justify-content: center; gap: 1rem;">
                            <a href="https://www.instagram.com/muhammedharunseker/" style="width: 40px; height: 40px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #ffffff; text-decoration: none; transition: all 0.3s ease;">
                                <i style="color: #e22027; font-size: 1.2rem;" class="fab fa-instagram"></i>
                            </a>
                            <a href="https://www.linkedin.com/in/muhammed-harun-şeker/" style="width: 40px; height: 40px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #ffffff; text-decoration: none; transition: all 0.3s ease;">
                                <i style="color: #e22027; font-size: 1.2rem;" class="fab fa-linkedin-in"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="modern-card" style="background: rgba(255, 255, 255, 0.95); border-radius: 25px; padding: 3rem; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                <h2 style="color: #181818; font-size: 2.5rem; font-weight: bold; text-align: center; margin-bottom: 2rem;">
                    <i class="fas fa-question-circle" style="color: #e22027; margin-right: 1rem;"></i>
                    Sık Sorulan Sorular
                </h2>
                <div class="modern-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                    <div style="padding: 1.5rem; background: rgba(226, 32, 39, 0.05); border-radius: 15px;">
                        <h4 style="color: #181818; font-size: 1.2rem; font-weight: bold; margin-bottom: 0.5rem;">Biletimi nasıl iptal edebilirim?</h4>
                        <p style="color: #666; font-size: 0.9rem; line-height: 1.5;">Kalkış saatinden 1 saat öncesine kadar biletinizi iptal edebilirsiniz. İptal işlemi için "Biletlerim" sayfasını kullanabilirsiniz.</p>
                    </div>
                    <div style="padding: 1.5rem; background: rgba(226, 32, 39, 0.05); border-radius: 15px;">
                        <h4 style="color: #181818; font-size: 1.2rem; font-weight: bold; margin-bottom: 0.5rem;">Ödeme nasıl yapabilirim?</h4>
                        <p style="color: #666; font-size: 0.9rem; line-height: 1.5;">Sanal kredi sistemi ile ödeme yapabilirsiniz. Hesabınıza kredi yükleyerek bilet satın alabilirsiniz.</p>
                    </div>
                    <div style="padding: 1.5rem; background: rgba(226, 32, 39, 0.05); border-radius: 15px;">
                        <h4 style="color: #181818; font-size: 1.2rem; font-weight: bold; margin-bottom: 0.5rem;">PDF biletimi nasıl indirebilirim?</h4>
                        <p style="color: #666; font-size: 0.9rem; line-height: 1.5;">Satın aldığınız biletlerinizi "Biletlerim" sayfasından PDF olarak indirebilirsiniz.</p>
                    </div>
                    <div style="padding: 1.5rem; background: rgba(226, 32, 39, 0.05); border-radius: 15px;">
                        <h4 style="color: #181818; font-size: 1.2rem; font-weight: bold; margin-bottom: 0.5rem;">Koltuk seçimi nasıl yapılır?</h4>
                        <p style="color: #666; font-size: 0.9rem; line-height: 1.5;">Sefer detayları sayfasında otobüs şemasından istediğiniz koltuğu seçebilirsiniz.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php renderFooter(); ?>

</body>
</html>
