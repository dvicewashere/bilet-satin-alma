<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gizlilik Politikası - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="css/style.css?v=3.2">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body style="background: #181818; min-height: 100vh; position: relative; overflow-x: hidden;">
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
                    <a href="index.php" style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500;">
                        Ana Sayfa
                    </a>
                    <a href="login_bus.php" style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500;">
                        Giriş Yap
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Privacy Section -->
    <section style="padding: 4rem 0; background: #181818;">
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <div class="modern-card" style="background: rgba(255, 255, 255, 0.95); border-radius: 25px; padding: 3rem; margin: 2rem 0; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                <div style="text-align: center; margin-bottom: 3rem;">
                    <h1 style="color: #181818; font-size: 2.5rem; font-weight: bold; margin-bottom: 1rem;">
                        <i class="fas fa-user-shield" style="color: #e22027; margin-right: 1rem;"></i>
                        Gizlilik Politikası
                    </h1>
                    <p style="color: #666; font-size: 1.1rem;">Son güncelleme: 1 Ocak 2025</p>
                </div>

                <div style="max-width: 800px; margin: 0 auto;">
                    <div class="privacy-section" style="margin-bottom: 2rem;">
                        <h2 style="color: #e22027; font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>
                            1. Genel Bilgiler
                        </h2>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem; margin-bottom: 1rem;">
                            DVICEBILET olarak, kişisel verilerinizin korunması bizim için önemlidir. 
                            Bu gizlilik politikası, hangi bilgileri topladığımızı ve nasıl kullandığımızı açıklar.
                        </p>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem;">
                            Bu politika, 6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK) uyumlu olarak hazırlanmıştır.
                        </p>
                    </div>

                    <div class="privacy-section" style="margin-bottom: 2rem;">
                        <h2 style="color: #e22027; font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-database" style="margin-right: 0.5rem;"></i>
                            2. Toplanan Bilgiler
                        </h2>
                        <h3 style="color: #181818; font-size: 1.2rem; font-weight: bold; margin-bottom: 0.5rem;">Kişisel Bilgiler:</h3>
                        <ul style="color: #181818; line-height: 1.6; font-size: 1rem; padding-left: 2rem; margin-bottom: 1rem;">
                            <li style="margin-bottom: 0.5rem;">Ad ve soyad</li>
                            <li style="margin-bottom: 0.5rem;">E-posta adresi</li>
                            <li style="margin-bottom: 0.5rem;">Telefon numarası</li>
                            <li style="margin-bottom: 0.5rem;">Kimlik bilgileri (bilet satışı için)</li>
                        </ul>
                        <h3 style="color: #181818; font-size: 1.2rem; font-weight: bold; margin-bottom: 0.5rem;">Teknik Bilgiler:</h3>
                        <ul style="color: #181818; line-height: 1.6; font-size: 1rem; padding-left: 2rem;">
                            <li style="margin-bottom: 0.5rem;">IP adresi</li>
                            <li style="margin-bottom: 0.5rem;">Tarayıcı bilgileri</li>
                            <li style="margin-bottom: 0.5rem;">Çerezler (cookies)</li>
                            <li style="margin-bottom: 0.5rem;">Site kullanım verileri</li>
                        </ul>
                    </div>

                    <div class="privacy-section" style="margin-bottom: 2rem;">
                        <h2 style="color: #e22027; font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-cogs" style="margin-right: 0.5rem;"></i>
                            3. Bilgilerin Kullanımı
                        </h2>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem; margin-bottom: 1rem;">
                            Topladığımız bilgileri aşağıdaki amaçlarla kullanırız:
                        </p>
                        <ul style="color: #181818; line-height: 1.6; font-size: 1rem; padding-left: 2rem;">
                            <li style="margin-bottom: 0.5rem;">Bilet satışı ve rezervasyon işlemleri</li>
                            <li style="margin-bottom: 0.5rem;">Müşteri hizmetleri desteği</li>
                            <li style="margin-bottom: 0.5rem;">Hesap yönetimi ve güvenlik</li>
                            <li style="margin-bottom: 0.5rem;">Yasal yükümlülüklerin yerine getirilmesi</li>
                            <li style="margin-bottom: 0.5rem;">Hizmet kalitesinin artırılması</li>
                        </ul>
                    </div>

                    <div class="privacy-section" style="margin-bottom: 2rem;">
                        <h2 style="color: #e22027; font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-share-alt" style="margin-right: 0.5rem;"></i>
                            4. Bilgi Paylaşımı
                        </h2>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem; margin-bottom: 1rem;">
                            Kişisel bilgilerinizi üçüncü taraflarla paylaşmayız, ancak aşağıdaki durumlar hariç:
                        </p>
                        <ul style="color: #181818; line-height: 1.6; font-size: 1rem; padding-left: 2rem;">
                            <li style="margin-bottom: 0.5rem;">Yasal zorunluluklar</li>
                            <li style="margin-bottom: 0.5rem;">Mahkeme kararları</li>
                            <li style="margin-bottom: 0.5rem;">Kamu güvenliği</li>
                            <li style="margin-bottom: 0.5rem;">Hizmet sağlayıcıları (güvenli şekilde)</li>
                        </ul>
                    </div>

                    <div class="privacy-section" style="margin-bottom: 2rem;">
                        <h2 style="color: #e22027; font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-lock" style="margin-right: 0.5rem;"></i>
                            5. Veri Güvenliği
                        </h2>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem; margin-bottom: 1rem;">
                            Verilerinizi korumak için aşağıdaki güvenlik önlemlerini alırız:
                        </p>
                        <ul style="color: #181818; line-height: 1.6; font-size: 1rem; padding-left: 2rem;">
                            <li style="margin-bottom: 0.5rem;">SSL şifreleme</li>
                            <li style="margin-bottom: 0.5rem;">Güvenli sunucular</li>
                            <li style="margin-bottom: 0.5rem;">Düzenli güvenlik güncellemeleri</li>
                            <li style="margin-bottom: 0.5rem;">Erişim kontrolleri</li>
                            <li style="margin-bottom: 0.5rem;">Veri yedekleme sistemleri</li>
                        </ul>
                    </div>

                    <div class="privacy-section" style="margin-bottom: 2rem;">
                        <h2 style="color: #e22027; font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-cookie-bite" style="margin-right: 0.5rem;"></i>
                            6. Çerezler (Cookies)
                        </h2>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem; margin-bottom: 1rem;">
                            Web sitemizde çerezler kullanırız. Çerezler, site deneyiminizi iyileştirmek için kullanılır.
                        </p>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem;">
                            Çerezleri tarayıcı ayarlarınızdan devre dışı bırakabilirsiniz, ancak bu durumda 
                            bazı özellikler çalışmayabilir.
                        </p>
                    </div>

                    <div class="privacy-section" style="margin-bottom: 2rem;">
                        <h2 style="color: #e22027; font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-user-edit" style="margin-right: 0.5rem;"></i>
                            7. Haklarınız
                        </h2>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem; margin-bottom: 1rem;">
                            KVKK kapsamında aşağıdaki haklara sahipsiniz:
                        </p>
                        <ul style="color: #181818; line-height: 1.6; font-size: 1rem; padding-left: 2rem;">
                            <li style="margin-bottom: 0.5rem;">Bilgilerinizin işlenip işlenmediğini öğrenme</li>
                            <li style="margin-bottom: 0.5rem;">İşlenen bilgilerinizi talep etme</li>
                            <li style="margin-bottom: 0.5rem;">Yanlış bilgilerin düzeltilmesini isteme</li>
                            <li style="margin-bottom: 0.5rem;">Bilgilerinizin silinmesini isteme</li>
                            <li style="margin-bottom: 0.5rem;">İşleme faaliyetlerine itiraz etme</li>
                        </ul>
                    </div>

                    <div class="privacy-section" style="margin-bottom: 2rem;">
                        <h2 style="color: #e22027; font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-envelope" style="margin-right: 0.5rem;"></i>
                            8. İletişim
                        </h2>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem; margin-bottom: 1rem;">
                            Gizlilik politikamız hakkında sorularınız için:
                        </p>
                        <ul style="color: #181818; line-height: 1.6; font-size: 1rem; padding-left: 2rem;">
                            <li style="margin-bottom: 0.5rem;">E-posta: muhammedharunseker@gmail.com</li>
                            <li style="margin-bottom: 0.5rem;">Telefon: +90 (212) 555 0123</li>
                            <li style="margin-bottom: 0.5rem;">Adres: SiverVatan, Türkiye</li>
                        </ul>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 3rem; padding-top: 2rem; border-top: 2px solid #e22027;">
                    <h3 style="color: #181818; font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;">
                        Verileriniz güvende
                    </h3>
                    <p style="color: #666; font-size: 1rem; margin-bottom: 2rem;">
                        Gizliliğinizi korumak için elimizden geleni yapıyoruz.
                    </p>
                    <a href="index.php" style="background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); color:rgb(0, 0, 0); padding: 1rem 2rem; border-radius: 25px; text-decoration: none; font-weight: bold; display: inline-block; transition: all 0.3s ease;">
                        <i class="fas fa-home" style="margin-right: 0.5rem;"></i>
                        Ana Sayfaya Dön
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php renderFooter(); ?>

</body>
</html>
