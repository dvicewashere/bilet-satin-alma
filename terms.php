<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanım Şartları - <?php echo APP_NAME; ?></title>
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

    <!-- Terms Section -->
    <section style="padding: 4rem 0; background: #181818;">
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <div class="modern-card" style="background: rgba(255, 255, 255, 0.95); border-radius: 25px; padding: 3rem; margin: 2rem 0; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                <div style="text-align: center; margin-bottom: 3rem;">
                    <h1 style="color: #181818; font-size: 2.5rem; font-weight: bold; margin-bottom: 1rem;">
                        <i class="fas fa-file-contract" style="color: #e22027; margin-right: 1rem;"></i>
                        Kullanım Şartları
                    </h1>
                    <p style="color: #666; font-size: 1.1rem;">Son güncelleme: 1 Ocak 2025</p>
                </div>

                <div style="max-width: 800px; margin: 0 auto;">
                    <div class="terms-section" style="margin-bottom: 2rem;">
                        <h2 style="color: #e22027; font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-gavel" style="margin-right: 0.5rem;"></i>
                            1. Genel Hükümler
                        </h2>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem; margin-bottom: 1rem;">
                            Bu kullanım şartları, DVICEBILET platformunu kullanan tüm kullanıcılar için geçerlidir. 
                            Platformu kullanarak bu şartları kabul etmiş sayılırsınız.
                        </p>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem;">
                            DVICEBILET, Türkiye Cumhuriyeti yasalarına tabidir ve İstanbul mahkemeleri yetkilidir.
                        </p>
                    </div>

                    <div class="terms-section" style="margin-bottom: 2rem;">
                        <h2 style="color: #e22027; font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-user-check" style="margin-right: 0.5rem;"></i>
                            2. Kullanıcı Sorumlulukları
                        </h2>
                        <ul style="color: #181818; line-height: 1.6; font-size: 1rem; padding-left: 2rem;">
                            <li style="margin-bottom: 0.5rem;">Doğru ve güncel bilgiler vermek</li>
                            <li style="margin-bottom: 0.5rem;">Hesap güvenliğini sağlamak</li>
                            <li style="margin-bottom: 0.5rem;">Yasalara uygun davranmak</li>
                            <li style="margin-bottom: 0.5rem;">Başkalarının haklarına saygı göstermek</li>
                            <li style="margin-bottom: 0.5rem;">Platformu kötüye kullanmamak</li>
                        </ul>
                    </div>

                    <div class="terms-section" style="margin-bottom: 2rem;">
                        <h2 style="color: #e22027; font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-ticket-alt" style="margin-right: 0.5rem;"></i>
                            3. Bilet Satışı ve İptal
                        </h2>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem; margin-bottom: 1rem;">
                            Biletler kalkış saatinden en az 1 saat öncesine kadar iptal edilebilir. 
                            İptal edilen biletlerin ücreti hesabınıza iade edilir.
                        </p>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem;">
                            Bilet fiyatları değişiklik gösterebilir. Satın alma anındaki fiyat geçerlidir.
                        </p>
                    </div>

                    <div class="terms-section" style="margin-bottom: 2rem;">
                        <h2 style="color: #e22027; font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-shield-alt" style="margin-right: 0.5rem;"></i>
                            4. Gizlilik ve Veri Güvenliği
                        </h2>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem; margin-bottom: 1rem;">
                            Kişisel verileriniz KVKK uyumlu olarak işlenir ve korunur. 
                            Detaylı bilgi için Gizlilik Politikamızı inceleyebilirsiniz.
                        </p>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem;">
                            Tüm ödemeler SSL sertifikası ile güvenli şekilde işlenir.
                        </p>
                    </div>

                    <div class="terms-section" style="margin-bottom: 2rem;">
                        <h2 style="color: #e22027; font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-ban" style="margin-right: 0.5rem;"></i>
                            5. Yasak Kullanımlar
                        </h2>
                        <ul style="color: #181818; line-height: 1.6; font-size: 1rem; padding-left: 2rem;">
                            <li style="margin-bottom: 0.5rem;">Sahte bilgi vermek</li>
                            <li style="margin-bottom: 0.5rem;">Sistem güvenliğini tehdit etmek</li>
                            <li style="margin-bottom: 0.5rem;">Başkalarının hesaplarını kullanmak</li>
                            <li style="margin-bottom: 0.5rem;">Spam veya zararlı içerik göndermek</li>
                            <li style="margin-bottom: 0.5rem;">Telif hakkı ihlali yapmak</li>
                        </ul>
                    </div>

                    <div class="terms-section" style="margin-bottom: 2rem;">
                        <h2 style="color: #e22027; font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-exclamation-triangle" style="margin-right: 0.5rem;"></i>
                            6. Sorumluluk Sınırları
                        </h2>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem; margin-bottom: 1rem;">
                            DVICEBILET, üçüncü taraf hizmet sağlayıcılarının eylemlerinden sorumlu değildir. 
                            Otobüs firmalarının gecikme veya iptal durumlarından sorumlu değiliz.
                        </p>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem;">
                            Platform teknik bakım nedeniyle geçici olarak erişilemez olabilir.
                        </p>
                    </div>

                    <div class="terms-section" style="margin-bottom: 2rem;">
                        <h2 style="color: #e22027; font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-edit" style="margin-right: 0.5rem;"></i>
                            7. Değişiklikler
                        </h2>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem;">
                            Bu kullanım şartları önceden haber verilmeksizin değiştirilebilir. 
                            Güncel şartlar platform üzerinde yayınlanır.
                        </p>
                    </div>

                    <div class="terms-section" style="margin-bottom: 2rem;">
                        <h2 style="color: #e22027; font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-phone" style="margin-right: 0.5rem;"></i>
                            8. İletişim
                        </h2>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem; margin-bottom: 1rem;">
                            Sorularınız için bizimle iletişime geçebilirsiniz:
                        </p>
                        <ul style="color: #181818; line-height: 1.6; font-size: 1rem; padding-left: 2rem;">
                            <li style="margin-bottom: 0.5rem;">E-posta: muhammedharunseker@gmail.com</li>
                            <li style="margin-bottom: 0.5rem;">Telefon: +90 (212) 555 0123</li>
                            <li style="margin-bottom: 0.5rem;">Adres: SiberVatan, Türkiye</li>
                        </ul>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 3rem; padding-top: 2rem; border-top: 2px solid #e22027;">
                    <h3 style="color: #181818; font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;">
                        Bu şartları kabul ediyor musunuz?
                    </h3>
                    <p style="color: #666; font-size: 1rem; margin-bottom: 2rem;">
                        Platformu kullanarak bu kullanım şartlarını kabul etmiş sayılırsınız.
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
