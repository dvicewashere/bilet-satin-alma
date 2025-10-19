<?php
require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hakkımızda - <?php echo APP_NAME; ?></title>
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
                        <?php renderNavbar('about'); ?>
                    <?php else: ?>
                        <a href="index.php" class="nav-link" style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500; position: relative;">Ana Sayfa</a>
                        <a href="login_bus.php" class="nav-link" style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500; position: relative;">Giriş</a>
                        <a href="register_bus.php" class="nav-link" style="color: #ffffff; text-decoration: none; padding: 0.5rem 1rem; border-radius: 25px; transition: all 0.3s ease; font-weight: 500; position: relative;">Kayıt Ol</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <!-- About Section -->
    <section class="about-section" style="padding: 4rem 0; background: #181818;">
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <!-- Page Header -->
            <div class="page-header" style="background: rgba(255, 255, 255, 0.95); border-radius: 25px; padding: 3rem; margin-bottom: 3rem; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); text-align: center;">
                <h1 style="color: #181818; font-size: 3rem; font-weight: bold; margin-bottom: 1rem;">Hakkımızda</h1>
                <div style="width: 80px; height: 4px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 2px; margin: 0 auto 2rem;"></div>
                <p style="color: #666; font-size: 1.2rem; line-height: 1.6; max-width: 800px; margin: 0 auto;">Türkiye'nin en güvenilir ve modern otobüs bileti satış platformu olarak, seyahat deneyiminizi kolaylaştırmak için buradayız.</p>
            </div>

            <!-- Mission & Vision -->
            <div class="modern-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
                <div class="modern-grid-item" style="background: rgba(255, 255, 255, 0.95); border-radius: 20px; padding: 2.5rem; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); text-align: center;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                        <i class="fas fa-bullseye" style="color: #e22027; font-size: 2rem;"></i>
                    </div>
                    <h3 style="color: #181818; font-size: 1.8rem; font-weight: bold; margin-bottom: 1rem;">Misyonumuz</h3>
                    <p style="color: #666; font-size: 1rem; line-height: 1.6;">Türkiye genelinde güvenli, konforlu ve ekonomik seyahat imkanı sunarak, müşterilerimizin hayatlarını kolaylaştırmak ve seyahat deneyimlerini en üst seviyeye çıkarmak.</p>
                </div>

                <div class="modern-grid-item" style="background: rgba(255, 255, 255, 0.95); border-radius: 20px; padding: 2.5rem; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); text-align: center;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                        <i class="fas fa-eye" style="color: #e22027; font-size: 2rem;"></i>
                    </div>
                    <h3 style="color: #181818; font-size: 1.8rem; font-weight: bold; margin-bottom: 1rem;">Vizyonumuz</h3>
                    <p style="color: #666; font-size: 1rem; line-height: 1.6;">Türkiye'nin en büyük ve en güvenilir otobüs bileti satış platformu olmak, teknoloji ile seyahat deneyimini yeniden tanımlamak.</p>
                </div>
            </div>

            <!-- Our Story -->
            <div class="modern-card" style="background: rgba(255, 255, 255, 0.95); border-radius: 25px; padding: 3rem; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); margin-bottom: 3rem;">
                <h2 style="color: #181818; font-size: 2.5rem; font-weight: bold; text-align: center; margin-bottom: 2rem;">
                    <i class="fas fa-history" style="color: #e22027; margin-right: 1rem;"></i>
                    Hikayemiz
                </h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                    <div style="text-align: center;">
                        <div style="width: 100px; height: 100px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <span style="color: #e22027; font-size: 2rem; font-weight: bold;">2020</span>
                        </div>
                        <h4 style="color: #181818; font-size: 1.3rem; font-weight: bold; margin-bottom: 0.5rem;">Kuruluş</h4>
                        <p style="color: #666; font-size: 0.9rem;">DviceBilet, seyahat sektöründeki ihtiyaçları gözlemleyerek kuruldu.</p>
                    </div>
                    <div style="text-align: center;">
                        <div style="width: 100px; height: 100px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <span style="color: #e22027; font-size: 2rem; font-weight: bold;">2022</span>
                        </div>
                        <h4 style="color: #181818; font-size: 1.3rem; font-weight: bold; margin-bottom: 0.5rem;">Gelişim</h4>
                        <p style="color: #666; font-size: 0.9rem;">Platformumuzu genişleterek daha fazla şehir ve firma ile çalışmaya başladık.</p>
                    </div>
                    <div style="text-align: center;">
                        <div style="width: 100px; height: 100px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <span style="color: #e22027; font-size: 2rem; font-weight: bold;">2025</span>
                        </div>
                        <h4 style="color: #181818; font-size: 1.3rem; font-weight: bold; margin-bottom: 0.5rem;">Bugün</h4>
                        <p style="color: #666; font-size: 0.9rem;">Türkiye'nin en güvenilir otobüs bileti platformu olarak hizmet veriyoruz.</p>
                    </div>
                </div>
            </div>

            <!-- Values -->
            <div class="modern-card" style="background: rgba(255, 255, 255, 0.95); border-radius: 25px; padding: 3rem; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); margin-bottom: 3rem;">
                <h2 style="color: #181818; font-size: 2.5rem; font-weight: bold; text-align: center; margin-bottom: 2rem;">
                    <i class="fas fa-heart" style="color: #e22027; margin-right: 1rem;"></i>
                    Değerlerimiz
                </h2>
                <div class="modern-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                    <div style="text-align: center; padding: 1.5rem;">
                        <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <i class="fas fa-shield-alt" style="color: #e22027; font-size: 1.5rem;"></i>
                        </div>
                        <h4 style="color: #181818; font-size: 1.2rem; font-weight: bold; margin-bottom: 0.5rem;">Güvenlik</h4>
                        <p style="color: #666; font-size: 0.9rem;">Müşteri bilgilerinin güvenliği bizim önceliğimizdir.</p>
                    </div>
                    <div style="text-align: center; padding: 1.5rem;">
                        <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <i class="fas fa-star" style="color: #e22027; font-size: 1.5rem;"></i>
                        </div>
                        <h4 style="color: #181818; font-size: 1.2rem; font-weight: bold; margin-bottom: 0.5rem;">Kalite</h4>
                        <p style="color: #666; font-size: 0.9rem;">En yüksek kalite standartlarında hizmet sunuyoruz.</p>
                    </div>
                    <div style="text-align: center; padding: 1.5rem;">
                        <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <i class="fas fa-users" style="color: #e22027; font-size: 1.5rem;"></i>
                        </div>
                        <h4 style="color: #181818; font-size: 1.2rem; font-weight: bold; margin-bottom: 0.5rem;">Müşteri Odaklılık</h4>
                        <p style="color: #666; font-size: 0.9rem;">Müşteri memnuniyeti bizim için en önemli değerdir.</p>
                    </div>
                    <div style="text-align: center; padding: 1.5rem;">
                        <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <i class="fas fa-lightbulb" style="color: #e22027; font-size: 1.5rem;"></i>
                        </div>
                        <h4 style="color: #181818; font-size: 1.2rem; font-weight: bold; margin-bottom: 0.5rem;">İnovasyon</h4>
                        <p style="color: #666; font-size: 0.9rem;">Sürekli gelişim ve yenilikçi çözümler üretiyoruz.</p>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="modern-card" style="background: rgba(255, 255, 255, 0.95); border-radius: 25px; padding: 3rem; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                <h2 style="color: #181818; font-size: 2.5rem; font-weight: bold; text-align: center; margin-bottom: 2rem;">
                    <i class="fas fa-chart-bar" style="color: #e22027; margin-right: 1rem;"></i>
                    Rakamlarla DviceBilet
                </h2>
                <div class="modern-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
                    <div style="text-align: center; padding: 1.5rem;">
                        <div style="font-size: 3rem; font-weight: bold; color: #e22027; margin-bottom: 0.5rem;">50K+</div>
                        <h4 style="color: #181818; font-size: 1.1rem; font-weight: bold;">Mutlu Müşteri</h4>
                    </div>
                    <div style="text-align: center; padding: 1.5rem;">
                        <div style="font-size: 3rem; font-weight: bold; color: #e22027; margin-bottom: 0.5rem;">81</div>
                        <h4 style="color: #181818; font-size: 1.1rem; font-weight: bold;">Şehir</h4>
                    </div>
                    <div style="text-align: center; padding: 1.5rem;">
                        <div style="font-size: 3rem; font-weight: bold; color: #e22027; margin-bottom: 0.5rem;">100K+</div>
                        <h4 style="color: #181818; font-size: 1.1rem; font-weight: bold;">Satılan Bilet</h4>
                    </div>
                    <div style="text-align: center; padding: 1.5rem;">
                        <div style="font-size: 3rem; font-weight: bold; color: #e22027; margin-bottom: 0.5rem;">24/7</div>
                        <h4 style="color: #181818; font-size: 1.1rem; font-weight: bold;">Destek</h4>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php renderFooter(); ?>

</body>
</html>
