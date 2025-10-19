<?php
require_once 'config.php';

requireRole(['user', 'company', 'admin']);

$ticket_id = isset($_GET['id']) ? sanitize($_GET['id']) : '';

if (empty($ticket_id)) {
    if ($_SESSION['user_role'] === 'company') {
        redirect('company_panel.php');
    } else {
    redirect('my_tickets.php');
    }
}

$db = getDB();

// Bilet bilgilerini al - şirket yöneticileri için de erişim sağla
if ($_SESSION['user_role'] === 'company') {
 
    $stmt = $db->prepare("
        SELECT t.*, tr.*, bc.name as company_name, bc.logo_path, u.full_name, u.email
        FROM tickets t
        JOIN trips tr ON t.trip_id = tr.id
        JOIN bus_companies bc ON tr.company_id = bc.id
        JOIN users u ON t.user_id = u.id
        WHERE t.id = ? AND tr.company_id = ?
    ");
    $stmt->execute([$ticket_id, $_SESSION['company_id']]);
} else {
  
$stmt = $db->prepare("
    SELECT t.*, tr.*, bc.name as company_name, bc.logo_path, u.full_name, u.email
    FROM tickets t
    JOIN trips tr ON t.trip_id = tr.id
    JOIN bus_companies bc ON tr.company_id = bc.id
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ? AND t.user_id = ?
");
$stmt->execute([$ticket_id, $_SESSION['user_id']]);
}
$ticket = $stmt->fetch();

if (!$ticket) {
    if ($_SESSION['user_role'] === 'company') {
        redirect('company_panel.php');
    } else {
    redirect('my_tickets.php');
    }
}

// Koltuk bilgileri
$stmt = $db->prepare("SELECT seat_number FROM booked_seats WHERE ticket_id = ? ORDER BY seat_number");
$stmt->execute([$ticket_id]);
$seats = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Logo dosya yolları
$dvicebilet_logo_path = 'images/logos/dvicebilet-logo.svg';
$company_logo_path = $ticket['logo_path'] ?: 'images/default-company.png';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet PDF - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: #f5f5f5;
        }
        .ticket-container {
            max-width: 600px; 
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .header { 
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e22027;
        }
        .logo-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        .dvicebilet-logo {
            width: 100px;
            height: 50px;
            background: #e22027;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            border-radius: 8px;
            text-align: center; 
            line-height: 1.2;
        }
        .company-logo {
            width: 100px;
            height: 50px;
            background: #f8f9fa;
            border: 2px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold; 
            color: #333; 
            border-radius: 8px;
            text-align: center;
            line-height: 1.2;
        }
        .ticket-info {
            text-align: center;
        }
        .ticket-number { 
            font-size: 14px; 
            font-weight: bold;
            margin-bottom: 10px;
        }
        .status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
        }
        .status-active { background: #d4edda; color: #155724; border: 2px solid #155724; }
        .status-canceled { background: #f8d7da; color: #721c24; border: 2px solid #721c24; }
        .status-expired { background: #fff3cd; color: #856404; border: 2px solid #856404; }
        
        .route-section {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        .route-info {
            text-align: center; 
        }
        .city { 
            font-weight: bold;
            font-size: 18px; 
            margin-bottom: 5px;
        }
        .time { 
            font-size: 20px;
            color: #e22027;
            font-weight: bold; 
            margin-bottom: 5px;
        }
        .date { 
            font-size: 14px; 
            color: #666;
        }
        .arrow { 
            font-size: 30px;
            color: #e22027;
            margin: 0 20px;
        }
        
        .info-section {
            margin: 20px 0;
            padding: 15px;
            background: #f5f5f5; 
            border-left: 4px solid #e22027;
            border-radius: 5px;
        }
        .info-section h3 {
            margin-top: 0;
            font-size: 16px;
            margin-bottom: 10px;
            color: #e22027;
        }
        .info-section p {
            margin: 5px 0; 
            font-size: 14px;
        }
        
        .seats {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 10px;
        }
        .seat {
            background: #e22027;
            color: white; 
            padding: 5px 10px; 
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .footer { 
            text-align: center; 
            border-top: 2px solid #e22027;
            padding-top: 20px; 
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
        
        .download-btn {
            background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin: 20px auto;
            display: block;
            transition: all 0.3s ease;
        }
        .download-btn:hover {
            background: linear-gradient(135deg, #c41e3a 0%, #a01a2e 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(226, 32, 39, 0.4);
        }
        .download-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        .fa-spinner {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="header">
            <div class="logo-section">
                <div class="dvicebilet-logo" id="dvicebilet-logo">
                    DVICE<br>BILET
                </div>
                <div class="company-logo" id="company-logo">
                    <?php echo htmlspecialchars(substr($ticket['company_name'], 0, 8)); ?>
                </div>
            </div>
            <div class="ticket-info">
                <div class="ticket-number">Bilet No: <?php echo htmlspecialchars($ticket_id); ?></div>
                <div class="status status-<?php echo strtolower($ticket['status']); ?>">
                    <?php echo ($ticket['status'] === 'ACTIVE' ? 'AKTİF' : ($ticket['status'] === 'CANCELED' ? 'İPTAL EDİLDİ' : 'SÜRESİ DOLDU')); ?>
                </div>
            </div>
        </div>
        
        <div class="route-section">
            <div class="route-info">
                <div class="city"><?php echo htmlspecialchars($ticket['departure_city']); ?></div>
                <div class="time"><?php echo date('H:i', strtotime($ticket['departure_time'])); ?></div>
                <div class="date"><?php echo date('d.m.Y', strtotime($ticket['departure_time'])); ?></div>
            </div>
            <div class="arrow">→</div>
            <div class="route-info">
                <div class="city"><?php echo htmlspecialchars($ticket['destination_city']); ?></div>
                <div class="time"><?php echo date('H:i', strtotime($ticket['arrival_time'])); ?></div>
                <div class="date"><?php echo date('d.m.Y', strtotime($ticket['arrival_time'])); ?></div>
            </div>
        </div>
        
        <div class="info-section">
            <h3>YOLCU BİLGİLERİ</h3>
            <p><strong>Ad Soyad:</strong> <?php echo htmlspecialchars($ticket['full_name']); ?></p>
            <p><strong>E-posta:</strong> <?php echo htmlspecialchars($ticket['email']); ?></p>
        </div>
        
        <div class="info-section">
            <h3>KOLTUKLAR</h3>
            <div class="seats">
                <?php foreach ($seats as $seat): ?>
                    <span class="seat"><?php echo htmlspecialchars($seat); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="info-section">
            <h3>FİYAT</h3>
            <p><strong>Toplam:</strong> <?php echo formatCurrency($ticket['total_price']); ?></p>
            <p><strong>Satın Alma:</strong> <?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?></p>
        </div>
        
        <div class="footer">
            <p>Bu bilet geçerlidir. Seyahat gününde yanınızda bulundurunuz.</p>
            <p>İyi yolculuklar dileriz.</p>
        </div>

        <button class="download-btn" id="downloadBtn" onclick="generatePDF()">
            <i class="fas fa-download"></i> PDF İndir
        </button>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        // Logoları yükle
        async function loadLogos() {
            try {
                // DVICEBILET logosu
                const dvicebiletResponse = await fetch('images/logos/dvicebilet-logo.png');
                if (dvicebiletResponse.ok) {
                    const blob = await dvicebiletResponse.blob();
                    const reader = new FileReader();
                    reader.onload = function() {
                        const dvicebiletLogo = document.getElementById('dvicebilet-logo');
                        dvicebiletLogo.innerHTML = `<img src="${reader.result}" style="max-width: 100%; max-height: 100%; object-fit: contain;">`;
                    };
                    reader.readAsDataURL(blob);
                }
            } catch (error) {
                console.log('DVICEBILET logosu yüklenemedi:', error);
            }

            try {
                // Firma logosu
                const companyResponse = await fetch('<?php echo $company_logo_path; ?>');
                if (companyResponse.ok) {
                    const blob = await companyResponse.blob();
                    const reader = new FileReader();
                    reader.onload = function() {
                        const companyLogo = document.getElementById('company-logo');
                        companyLogo.innerHTML = `<img src="${reader.result}" style="max-width: 100%; max-height: 100%; object-fit: contain;">`;
                    };
                    reader.readAsDataURL(blob);
                }
            } catch (error) {
                console.log('Firma logosu yüklenemedi:', error);
            }
        }

        // PDF oluştur
        async function generatePDF() {
            const downloadBtn = document.getElementById('downloadBtn');
            const originalText = downloadBtn.innerHTML;
            
            try {
                // Loading durumu
                downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> PDF Oluşturuluyor...';
                downloadBtn.disabled = true;
                
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('p', 'mm', 'a4');
                
                // Türkçe karakter desteği için font ayarları
                doc.setFont('helvetica', 'normal');
                
                // Türkçe karakter mapping
                const turkishChars = {
                    'İ': 'I', 'ı': 'i', 'Ğ': 'G', 'ğ': 'g',
                    'Ü': 'U', 'ü': 'u', 'Ş': 'S', 'ş': 's',
                    'Ö': 'O', 'ö': 'o', 'Ç': 'C', 'ç': 'c'
                };
                
                function fixTurkishChars(text) {
                    return text.replace(/[İıĞğÜüŞşÖöÇç]/g, char => turkishChars[char] || char);
                }
                
                // Sayfa boyutları
                const pageWidth = doc.internal.pageSize.getWidth();
                const pageHeight = doc.internal.pageSize.getHeight();
                
                // Ana kart arka planı
                doc.setFillColor(255, 255, 255);
                doc.roundedRect(10, 10, pageWidth - 20, pageHeight - 20, 5, 5, 'F');
                
                // Üst orta kısım - otobüs bileti yazısı
                doc.setTextColor(0, 0, 0);
                doc.setFontSize(18);
                doc.setFont('helvetica', 'bold');
                doc.text('OTOBUS BILETI', pageWidth/2, 25, { align: 'center' });

                // DviceBilet logosu (PNG) - firma logosunun üzerinde
                try {
                    const dviceResp = await fetch('images/logos/dvicebilet-logo.png');
                    if (dviceResp.ok) {
                        const blob = await dviceResp.blob();
                        const base64 = await new Promise((resolve) => {
                            const reader = new FileReader();
                            reader.onload = () => resolve(reader.result);
                            reader.readAsDataURL(blob);
                        });
                        // Konum: sol üst, firma logosunun tam üzerinde - PNG format (daha dar)
                        doc.addImage(base64, 'PNG', 20, 15, 27, 15);
                    }
                } catch (e) {
                    // sessiz geç
                }

                // Sol üst - firma logosu (geniş ama kısa)
                try {
                    const companyResponse = await fetch('<?php echo $company_logo_path; ?>');
                    if (companyResponse.ok) {
                        const blob = await companyResponse.blob();
                        const base64 = await new Promise((resolve) => {
                            const reader = new FileReader();
                            reader.onload = () => resolve(reader.result);
                            reader.readAsDataURL(blob);
                        });
                        
                        doc.addImage(base64, 'PNG', 20, 32, 60, 16);
                    } else {
                        
                        doc.setTextColor(0, 0, 0);
                        doc.setFontSize(12);
                        doc.setFont('helvetica', 'bold');
                        doc.text('<?php echo htmlspecialchars($ticket['company_name']); ?>', 20, 45);
                    }
                } catch (error) {
                 
                    doc.setTextColor(0, 0, 0);
                    doc.setFontSize(12);
                    doc.setFont('helvetica', 'bold');
                    doc.text('<?php echo htmlspecialchars($ticket['company_name']); ?>', 20, 45);
                }

                // Sağ üst - bilet numarası ve durum
                doc.setTextColor(0, 0, 0);
                doc.setFontSize(10);
                doc.setFont('helvetica', 'normal');
                doc.text('Bilet No: <?php echo htmlspecialchars($ticket_id); ?>', pageWidth - 20, 40, { align: 'right' });

                // Durum butonu - kırmızı arka plan
                const statusText = '<?php echo ($ticket['status'] === 'ACTIVE' ? 'AKTIF' : ($ticket['status'] === 'CANCELED' ? 'IPTAL EDILDI' : 'SURESI DOLDU')); ?>';
                doc.setFillColor(226, 32, 39);
                doc.roundedRect(pageWidth - 50, 45, 30, 8, 2, 2, 'F');
                doc.setTextColor(255, 255, 255);
                doc.setFontSize(8);
                doc.setFont('helvetica', 'bold');
                doc.text(statusText, pageWidth - 35, 50, { align: 'center' });

                // Ayırıcı çizgi
                doc.setDrawColor(226, 32, 39);
                doc.line(15, 60, pageWidth - 15, 60);

                // Güzergah bilgisi - resimdeki gibi
                doc.setTextColor(0, 0, 0);

                // Kalkış bilgileri
                doc.setFontSize(14);
                doc.setFont('helvetica', 'bold');
                doc.text(fixTurkishChars('<?php echo htmlspecialchars($ticket['departure_city']); ?>'), 20, 75);
                doc.setTextColor(226, 32, 39);
                doc.setFontSize(16);
                doc.setFont('helvetica', 'bold');
                doc.text('<?php echo date('H:i', strtotime($ticket['departure_time'])); ?>', 20, 80);
                doc.setTextColor(0, 0, 0);
                doc.setFontSize(10);
                doc.setFont('helvetica', 'normal');
                doc.text('<?php echo date('d.m.Y', strtotime($ticket['departure_time'])); ?>', 20, 85);

            
                const arrowCenterX = pageWidth/2;
                const arrowY = 80;
                doc.setDrawColor(0, 0, 0);
                doc.setLineWidth(0.4);
                doc.line(arrowCenterX - 12, arrowY, arrowCenterX + 8, arrowY);
                doc.setFillColor(0, 0, 0);
                doc.triangle(arrowCenterX + 8, arrowY, arrowCenterX + 3, arrowY - 2.5, arrowCenterX + 3, arrowY + 2.5, 'F');

                // Varış bilgileri
                doc.setTextColor(0, 0, 0);
                doc.setFontSize(14);
                doc.setFont('helvetica', 'bold');
                doc.text(fixTurkishChars('<?php echo htmlspecialchars($ticket['destination_city']); ?>'), pageWidth - 20, 75, { align: 'right' });
                doc.setTextColor(226, 32, 39);
                doc.setFontSize(16);
                doc.setFont('helvetica', 'bold');
                doc.text('<?php echo date('H:i', strtotime($ticket['arrival_time'])); ?>', pageWidth - 20, 80, { align: 'right' });
                doc.setTextColor(0, 0, 0);
                doc.setFontSize(10);
                doc.setFont('helvetica', 'normal');
                doc.text('<?php echo date('d.m.Y', strtotime($ticket['arrival_time'])); ?>', pageWidth - 20, 85, { align: 'right' });

                // Yolcu bilgileri - resimdeki gibi gri arka plan
                doc.setFillColor(245, 245, 245);
                doc.roundedRect(15, 95, pageWidth - 30, 20, 3, 3, 'F');
                doc.setTextColor(226, 32, 39);
                doc.setFontSize(11);
                doc.setFont('helvetica', 'bold');
                doc.text('YOLCU BILGILERI', 20, 103);
                doc.setTextColor(0, 0, 0);
                doc.setFontSize(10);
                doc.setFont('helvetica', 'normal');
                doc.text('Ad Soyad: ' + fixTurkishChars('<?php echo htmlspecialchars($ticket['full_name']); ?>'), 20, 107);
                doc.text('E-posta: <?php echo htmlspecialchars($ticket['email']); ?>', 20, 111);

                doc.setFillColor(245, 245, 245);
                doc.roundedRect(15, 125, pageWidth - 30, 20, 3, 3, 'F');
                doc.setTextColor(226, 32, 39);
                doc.setFontSize(11);
                doc.setFont('helvetica', 'bold');
                doc.text('KOLTUKLAR', 20, 133);

                // Koltuk numaraları - kırmızı badge'ler
                let seatX = 20;
                <?php foreach ($seats as $seat): ?>
                    doc.setFillColor(226, 32, 39);
                    doc.roundedRect(seatX, 135, 8, 6, 1, 1, 'F');
                    doc.setTextColor(255, 255, 255);
                    doc.setFontSize(8);
                    doc.setFont('helvetica', 'bold');
                    doc.text('<?php echo htmlspecialchars($seat); ?>', seatX + 4, 139, { align: 'center' });
                    seatX += 12;
                <?php endforeach; ?>

             
                doc.setFillColor(245, 245, 245);
                doc.roundedRect(15, 155, pageWidth - 30, 20, 3, 3, 'F');
                doc.setTextColor(226, 32, 39);
                doc.setFontSize(11);
                doc.setFont('helvetica', 'bold');
                doc.text('FIYAT', 20, 163);
                doc.setTextColor(0, 0, 0);
                doc.setFontSize(10);
                doc.setFont('helvetica', 'normal');
                doc.text('Toplam: <?php echo formatCurrency($ticket['total_price']); ?> TL', 20, 167);
                doc.text('Satin Alma: <?php echo date('d.m.Y H:i', strtotime($ticket['created_at'])); ?>', 20, 171);

             
                doc.setDrawColor(226, 32, 39);
                doc.line(15, 185, pageWidth - 15, 185);

              
                doc.setTextColor(0, 0, 0);
                doc.setFontSize(9);
                doc.setFont('helvetica', 'normal');
                doc.text('Bu bilet gecerlidir. Seyahat gununde yaninizda bulundurunuz.', pageWidth/2, 195, { align: 'center' });
                doc.text('Iyi yolculuklar dileriz.', pageWidth/2, 200, { align: 'center' });
                
                // PDF'i indir
                const fileName = 'bilet_<?php echo $ticket_id; ?>.pdf';
                doc.save(fileName);
                
                // Başarı mesajı
                console.log('PDF başarıyla oluşturuldu ve indirildi: ' + fileName);
                
                // Buton durumunu geri yükle
                downloadBtn.innerHTML = originalText;
                downloadBtn.disabled = false;
                
            } catch (error) {
                console.error('PDF oluşturma hatası:', error);
                alert('PDF oluşturulurken bir hata oluştu. Lütfen tekrar deneyin.');
                
                // Buton durumunu geri yükle
                downloadBtn.innerHTML = originalText;
                downloadBtn.disabled = false;
            }
        }

        // Sayfa yüklendiğinde logoları yükle
        document.addEventListener('DOMContentLoaded', loadLogos);
    </script>

    <?php renderFooter(); ?>

</body>
</html>
