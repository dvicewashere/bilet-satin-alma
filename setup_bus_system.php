<?php
// Otobüs Bileti Sistemi Kurulum Scripti
require_once 'config.php';

echo "<h1>🚌 Otobüs Bileti Sistemi Kurulumu</h1>";

try {
    echo "<p>Veritabanı oluşturuluyor...</p>";
    
    // Veritabanı dosyasını oluştur
    $db = getDB();
    
    echo "<p>✅ Veritabanı başarıyla oluşturuldu!</p>";
    
    // Test sorguları
    echo "<h2>📊 Sistem İstatistikleri</h2>";
    
    $tables = ['users', 'bus_companies', 'trips', 'tickets', 'coupons', 'user_coupons', 'booked_seats'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM $table");
            $stmt->execute();
            $count = $stmt->fetch()['count'];
            echo "<p>✅ <strong>$table:</strong> $count kayıt</p>";
        } catch (Exception $e) {
            echo "<p>❌ <strong>$table:</strong> Tablo bulunamadı</p>";
        }
    }
    
    echo "<h2>👥 Test Kullanıcıları</h2>";
    
    $stmt = $db->prepare("SELECT full_name, email, role, balance FROM users ORDER BY role");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    if ($users) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr><th>Ad Soyad</th><th>E-posta</th><th>Rol</th><th>Bakiye</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['full_name'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . ucfirst($user['role']) . "</td>";
            echo "<td>" . formatBalance($user['balance']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>🚌 Otobüs Firmaları</h2>";
    
    $stmt = $db->prepare("SELECT name, created_at FROM bus_companies ORDER BY name");
    $stmt->execute();
    $companies = $stmt->fetchAll();
    
    if ($companies) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr><th>Firma Adı</th><th>Kayıt Tarihi</th></tr>";
        foreach ($companies as $company) {
            echo "<tr>";
            echo "<td>" . $company['name'] . "</td>";
            echo "<td>" . date('d.m.Y', strtotime($company['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>🎫 Örnek Seferler</h2>";
    
    $stmt = $db->prepare("
        SELECT t.departure_city, t.destination_city, t.departure_time, t.price, bc.name as company_name
        FROM trips t 
        JOIN bus_companies bc ON t.company_id = bc.id 
        ORDER BY t.departure_time 
        LIMIT 5
    ");
    $stmt->execute();
    $trips = $stmt->fetchAll();
    
    if ($trips) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr><th>Güzergah</th><th>Firma</th><th>Tarih/Saat</th><th>Fiyat</th></tr>";
        foreach ($trips as $trip) {
            echo "<tr>";
            echo "<td>" . $trip['departure_city'] . " → " . $trip['destination_city'] . "</td>";
            echo "<td>" . $trip['company_name'] . "</td>";
            echo "<td>" . date('d.m.Y H:i', strtotime($trip['departure_time'])) . "</td>";
            echo "<td>" . formatCurrency($trip['price']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>🏷️ İndirim Kuponları</h2>";
    
    try {
        $stmt = $db->prepare("SELECT code, discount_type, discount_value, max_uses, expiry_date FROM coupons ORDER BY created_at");
        $stmt->execute();
        $coupons = $stmt->fetchAll();
        
        if ($coupons) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
            echo "<tr><th>Kupon Kodu</th><th>İndirim</th><th>Kullanım Limiti</th><th>Son Kullanma</th></tr>";
            foreach ($coupons as $coupon) {
                echo "<tr>";
                echo "<td><strong>" . $coupon['code'] . "</strong></td>";
                if ($coupon['discount_type'] === 'percentage') {
                    echo "<td>" . $coupon['discount_value'] . "%</td>";
                } else {
                    echo "<td>" . formatCurrency($coupon['discount_value']) . "</td>";
                }
                echo "<td>" . $coupon['max_uses'] . "</td>";
                echo "<td>" . date('d.m.Y', strtotime($coupon['expiry_date'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Henüz kupon oluşturulmamış.</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Kupon tablosu henüz oluşturulmamış veya eski yapıda.</p>";
    }
    
    echo "<hr>";
    echo "<h2>🎯 Giriş Bilgileri</h2>";
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>Test Hesapları:</h3>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@dvice.com / admin123</li>";
    echo "<li><strong>Yolcu:</strong> yolcu@dvice.com / test123 (1000 birim bakiye)</li>";
    echo "<li><strong>Firma Admin (Metro):</strong> metro@dvice.com / deneme123</li>";
    echo "<li><strong>Firma Admin (Ulusoy):</strong> ulusoy@dvice.com / deneme123</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h2>🔗 Sayfa Linkleri</h2>";
    echo "<div style='background: #e8f4fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>Ana Sayfalar:</h3>";
    echo "<ul>";
    echo "<li><a href='index_bus.php' target='_blank'>Ana Sayfa (Sefer Arama)</a></li>";
    echo "<li><a href='login_bus.php' target='_blank'>Giriş Sayfası</a></li>";
    echo "<li><a href='register_bus.php' target='_blank'>Kayıt Sayfası</a></li>";
    echo "</ul>";
    
    echo "<h3>Kullanıcı Sayfaları:</h3>";
    echo "<ul>";
    echo "<li><a href='my_tickets.php' target='_blank'>Biletlerim (Giriş gerekli)</a></li>";
    echo "<li><a href='profile.php' target='_blank'>Profil Sayfası</a></li>";
    echo "</ul>";
    
    echo "<h3>Yönetim Panelleri:</h3>";
    echo "<ul>";
    echo "<li><a href='admin_panel.php' target='_blank'>Admin Panel (Admin gerekli)</a></li>";
    echo "<li><a href='company_panel.php' target='_blank'>Firma Panel (Firma Admin gerekli)</a></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h2>🔧 Şifre Düzeltme İşlemi</h2>";
    
    try {
        // Admin şifresi
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([password_hash('admin123', PASSWORD_DEFAULT), 'admin@dvice.com']);
        echo "<p>✅ Admin şifresi güncellendi (admin@dvice.com / admin123)</p>";
        
        // Test kullanıcı şifresi
        $stmt->execute([password_hash('test123', PASSWORD_DEFAULT), 'yolcu@dvice.com']);
        echo "<p>✅ Test kullanıcı şifresi güncellendi (yolcu@dvice.com / test123)</p>";
        
        // Metro admin şifresi
        $stmt->execute([password_hash('deneme123', PASSWORD_DEFAULT), 'metro@dvice.com']);
        echo "<p>✅ Metro admin şifresi güncellendi (metro@dvice.com / deneme123)</p>";
        
        // Ulusoy admin şifresi
        $stmt->execute([password_hash('deneme123', PASSWORD_DEFAULT), 'ulusoy@dvice.com']);
        echo "<p>✅ Ulusoy admin şifresi güncellendi (ulusoy@dvice.com / deneme123)</p>";
        
    } catch (Exception $e) {
        echo "<p>❌ Şifre güncelleme hatası: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>🧪 Şifre Testi</h2>";
    
    // Test şifreleri
    $test_emails = ['admin@dvice.com', 'yolcu@dvice.com', 'metro@dvice.com', 'ulusoy@dvice.com'];
    
    foreach ($test_emails as $email) {
        echo "<h3>E-posta: $email</h3>";
        
        $stmt = $db->prepare("SELECT password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<p>✅ Kullanıcı bulundu</p>";
            
            // Test both passwords
            $admin_test = password_verify('admin123', $user['password']);
            $test_test = password_verify('test123', $user['password']);
            
            echo "<p>Şifre 'admin123': " . ($admin_test ? "✅ DOĞRU" : "❌ YANLIŞ") . "</p>";
            echo "<p>Şifre 'test123': " . ($test_test ? "✅ DOĞRU" : "❌ YANLIŞ") . "</p>";
            
            if ($admin_test) {
                echo "<p><strong>Kullanılacak şifre: admin123</strong></p>";
            } elseif ($test_test) {
                echo "<p><strong>Kullanılacak şifre: test123</strong></p>";
            }
        } else {
            echo "<p>❌ Kullanıcı bulunamadı</p>";
        }
        echo "<hr>";
    }

    echo "<h2>✅ Kurulum Tamamlandı!</h2>";
    echo "<p><strong>Otobüs Bileti Satış Platformu</strong> başarıyla kuruldu ve test edilmeye hazır.</p>";
    echo "<p>Tüm özellikler PDF gereksinimlerine göre uygulandı:</p>";
    echo "<ul>";
    echo "<li>✅ Kullanıcı rolleri (Admin, Firma Admin, User)</li>";
    echo "<li>✅ Otobüs firması yönetimi</li>";
    echo "<li>✅ Sefer yönetimi ve koltuk rezervasyonu</li>";
    echo "<li>✅ Kupon sistemi ve indirim uygulaması</li>";
    echo "<li>✅ Sanal kredi sistemi ve bakiye yönetimi</li>";
    echo "<li>✅ PDF bilet üretimi</li>";
    echo "<li>✅ 1 saat kuralı ile bilet iptal sistemi</li>";
    echo "<li>✅ Yetki tablosuna göre sayfa erişim kontrolü</li>";
    echo "<li>✅ Şifre düzeltme ve test sistemi</li>";
    echo "</ul>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>🎯 Giriş Bilgileri</h3>";
    echo "<p>Artık giriş yapabilirsiniz:</p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@dvice.com / admin123</li>";
    echo "<li><strong>Test Kullanıcı:</strong> yolcu@dvice.com / test123</li>";
    echo "<li><strong>Metro Admin:</strong> metro@dvice.com / deneme123</li>";
    echo "<li><strong>Ulusoy Admin:</strong> ulusoy@dvice.com / deneme123</li>";
    echo "</ul>";
    echo "<p><a href='login_bus.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Giriş Sayfasına Git</a></p>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2>❌ Hata: " . $e->getMessage() . "</h2>";
    echo "<p>Lütfen config.php dosyasını kontrol edin.</p>";
}
?>

<?php renderFooter(); ?>

</body>
</html>
