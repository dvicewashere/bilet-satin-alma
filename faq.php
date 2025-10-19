<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sıkça Sorulan Sorular - <?php echo APP_NAME; ?></title>
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

    <!-- FAQ Section -->
    <section style="padding: 4rem 0; background: #181818;">
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <div class="modern-card" style="background: rgba(255, 255, 255, 0.95); border-radius: 25px; padding: 3rem; margin: 2rem 0; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                <div style="text-align: center; margin-bottom: 3rem;">
                    <h1 style="color: #181818; font-size: 2.5rem; font-weight: bold; margin-bottom: 1rem;">
                        <i class="fas fa-question-circle" style="color: #e22027; margin-right: 1rem;"></i>
                        Sıkça Sorulan Sorular
                    </h1>
                    <p style="color: #666; font-size: 1.1rem;">DVICEBILET hakkında merak ettiğiniz her şey</p>
                </div>

                <div style="max-width: 800px; margin: 0 auto;">
                    <div class="faq-item" style="margin-bottom: 2rem; border-bottom: 1px solid #e9ecef; padding-bottom: 2rem;">
                        <h3 style="color: #e22027; font-size: 1.3rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-bus" style="margin-right: 0.5rem;"></i>
                            Bilet nasıl satın alabilirim?
                        </h3>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem;">
                            Ana sayfada kalkış ve varış şehrinizi seçin, tarih belirleyin ve arama yapın. 
                            Size uygun seferi bulduktan sonra koltuk seçimi yaparak biletinizi satın alabilirsiniz.
                        </p>
                    </div>

                    <div class="faq-item" style="margin-bottom: 2rem; border-bottom: 1px solid #e9ecef; padding-bottom: 2rem;">
                        <h3 style="color: #e22027; font-size: 1.3rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-credit-card" style="margin-right: 0.5rem;"></i>
                            Hangi ödeme yöntemlerini kabul ediyorsunuz?
                        </h3>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem;">
                            Kredi kartı, banka kartı ve dijital cüzdanlar ile ödeme yapabilirsiniz. 
                            Tüm ödemeler SSL sertifikası ile güvenli şekilde işlenir.
                        </p>
                    </div>

                    <div class="faq-item" style="margin-bottom: 2rem; border-bottom: 1px solid #e9ecef; padding-bottom: 2rem;">
                        <h3 style="color: #e22027; font-size: 1.3rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-times-circle" style="margin-right: 0.5rem;"></i>
                            Biletimi iptal edebilir miyim?
                        </h3>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem;">
                            Evet, kalkış saatinden en az 1 saat önce biletinizi iptal edebilirsiniz. 
                            İptal edilen biletlerin ücreti hesabınıza iade edilir.
                        </p>
                    </div>

                    <div class="faq-item" style="margin-bottom: 2rem; border-bottom: 1px solid #e9ecef; padding-bottom: 2rem;">
                        <h3 style="color: #e22027; font-size: 1.3rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-download" style="margin-right: 0.5rem;"></i>
                            Biletimi nasıl indirebilirim?
                        </h3>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem;">
                            "Biletlerim" sayfasından satın aldığınız biletin yanındaki "PDF İndir" butonuna tıklayarak 
                            biletinizi PDF formatında indirebilirsiniz.
                        </p>
                    </div>

                    <div class="faq-item" style="margin-bottom: 2rem; border-bottom: 1px solid #e9ecef; padding-bottom: 2rem;">
                        <h3 style="color: #e22027; font-size: 1.3rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-user-plus" style="margin-right: 0.5rem;"></i>
                            Hesap nasıl oluştururum?
                        </h3>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem;">
                            "Kayıt Ol" sayfasından ad, soyad, e-posta ve şifre bilgilerinizi girerek 
                            ücretsiz hesap oluşturabilirsiniz. E-posta adresinizi doğrulamanız gerekmektedir.
                        </p>
                    </div>

                    <div class="faq-item" style="margin-bottom: 2rem; border-bottom: 1px solid #e9ecef; padding-bottom: 2rem;">
                        <h3 style="color: #e22027; font-size: 1.3rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-shield-alt" style="margin-right: 0.5rem;"></i>
                            Bilgilerim güvenli mi?
                        </h3>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem;">
                            Evet, tüm kişisel bilgileriniz SSL şifreleme ile korunur. 
                            KVKK uyumlu olarak verilerinizi güvenli şekilde saklarız.
                        </p>
                    </div>

                    <div class="faq-item" style="margin-bottom: 2rem;">
                        <h3 style="color: #e22027; font-size: 1.3rem; font-weight: bold; margin-bottom: 1rem;">
                            <i class="fas fa-headset" style="margin-right: 0.5rem;"></i>
                            Destek alabilir miyim?
                        </h3>
                        <p style="color: #181818; line-height: 1.6; font-size: 1rem;">
                            7/24 müşteri hizmetleri desteği sunuyoruz. İletişim sayfamızdan bizimle iletişime geçebilir 
                            veya canlı destek hattımızı kullanabilirsiniz.
                        </p>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 3rem; padding-top: 2rem; border-top: 2px solid #e22027;">
                    <h3 style="color: #181818; font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;">
                        Sorunuzun cevabını bulamadınız mı?
                    </h3>
                    <p style="color: #666; font-size: 1rem; margin-bottom: 2rem;">
                        Bizimle iletişime geçin, size yardımcı olmaktan mutluluk duyarız.
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
