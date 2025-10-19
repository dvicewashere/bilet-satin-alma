<?php
require_once 'config.php';

requireRole(['user', 'company']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('my_tickets.php');
}

$ticket_id = isset($_POST['ticket_id']) ? sanitize($_POST['ticket_id']) : '';

if (empty($ticket_id)) {
    redirect('my_tickets.php');
}

$db = getDB();

// Bilet detaylarını al - firma yöneticisinin kendi firmasının biletlerini iptal etmesine izin ver
$user_role = getUserRole();
if ($user_role === 'company') {

    $stmt = $db->prepare("
        SELECT t.*, tr.departure_time, u.balance
        FROM tickets t
        JOIN trips tr ON t.trip_id = tr.id
        JOIN bus_companies bc ON tr.company_id = bc.id
        JOIN users u ON t.user_id = u.id
        WHERE t.id = ? AND bc.id = (SELECT company_id FROM users WHERE id = ?) AND t.status = 'ACTIVE'
    ");
    $stmt->execute([$ticket_id, $_SESSION['user_id']]);
} else {
    // Normal kullanıcı sadece kendi biletlerini iptal edebilir
    $stmt = $db->prepare("
        SELECT t.*, tr.departure_time, u.balance
        FROM tickets t
        JOIN trips tr ON t.trip_id = tr.id
        JOIN users u ON t.user_id = u.id
        WHERE t.id = ? AND t.user_id = ? AND t.status = 'ACTIVE'
    ");
    $stmt->execute([$ticket_id, $_SESSION['user_id']]);
}
$ticket = $stmt->fetch();

if (!$ticket) {
    $_SESSION['error'] = 'Bilet bulunamadı veya zaten iptal edilmiş.';
    redirect('my_tickets.php');
}

// İptalin izin verilip verilmediğini kontrol et (1 saat kuralı)
$departureDateTime = new DateTime($ticket['departure_time']);
$currentDateTime = new DateTime();
$timeDifference = $departureDateTime->getTimestamp() - $currentDateTime->getTimestamp();

// Hata ayıklama: Zaman farkını kaydet
error_log("CANCEL CHECK - Ticket ID: $ticket_id, Departure: " . $ticket['departure_time'] . ", Current: " . $currentDateTime->format('Y-m-d H:i:s') . ", Difference: $timeDifference seconds (" . round($timeDifference/60, 2) . " minutes)");


if ($timeDifference <= 3600) { // 3600 seconds = 1 hour
    error_log("CANCELLATION DENIED - Less than 1 hour remaining. Time left: " . round($timeDifference/60, 2) . " minutes");
    $_SESSION['error'] = 'Kalkış saatinden 1 saatten az bir süre kaldığı için bilet iptal edilemez. Kalan süre: ' . round($timeDifference/60, 2) . ' dakika.';
    redirect('my_tickets.php');
}

error_log("CANCELLATION ALLOWED - More than 1 hour remaining. Time left: " . round($timeDifference/3600, 2) . " hours");

// Bilet iptal et ve iade et
$db->beginTransaction();

try {
    // Bileti iptal et
    $stmt = $db->prepare("UPDATE tickets SET status = 'CANCELED' WHERE id = ?");
    $stmt->execute([$ticket_id]);
    
    // Kullanıcı bakiyesine iade et
    $stmt = $db->prepare("
        UPDATE users SET balance = balance + ? WHERE id = ?
    ");
    $stmt->execute([$ticket['total_price'], $_SESSION['user_id']]);
    
    $db->commit();
    
    $_SESSION['success'] = 'Bilet başarıyla iptal edildi. ' . formatCurrency($ticket['total_price']) . ' tutarı hesabınıza iade edildi.';
    
} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['error'] = 'Bilet iptal edilirken bir hata oluştu.';
}

redirect('my_tickets.php');
?>
