<?php
require_once 'config.php';

// Veritabanı bağlantısını başlat
$db = getDB();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
  
    $role = 'user';
    
    // Doğrulama
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Tüm zorunlu alanları doldurun.';
    } elseif ($password !== $confirm_password) {
        $error = 'Şifreler eşleşmiyor.';
    } elseif (strlen($password) < 6) {
        $error = 'Şifre en az 6 karakter olmalıdır.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta adresi girin.';
    } else {
        $db = getDB();
        
        // E-posta zaten var mı kontrol et
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Bu e-posta adresi zaten kullanılıyor.';
        } else {
            // Kullanıcı oluştur
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $user_id = generateUUID();
            
            // Normal kullanıcılar için varsayılan bakiye ayarla
            $balance = 1000; // Kullanıcılar için 1000 birim varsayılan kredi
            
            $stmt = $db->prepare("
                INSERT INTO users (id, full_name, email, password, role, balance) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$user_id, $full_name, $email, $hashed_password, $role, $balance])) {
                $success = 'Kayıt başarılı! Şimdi giriş yapabilirsiniz.';
                // Otomatik giriş
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_email'] = $email;
                redirect('index.php');
            } else {
                $error = 'Kayıt sırasında bir hata oluştu.';
            }
        }
    }
}

// Eğer zaten giriş yapılmışsa, ana sayfaya yönlendir
if (isLoggedIn()) {
    redirect('index.php');
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="css/style.css?v=3.2">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

</head>
<body style="background: #181818; min-height: 100vh; position: relative; overflow-x: hidden;">
    <div class="auth-container" style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; background: #181818;">
        <div class="auth-form" style="background: rgba(255, 255, 255, 0.95); border-radius: 25px; padding: 3rem; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); width: 100%; max-width: 600px; position: relative; overflow: hidden;">
            <div class="auth-header" style="text-align: center; margin-bottom: 1.25rem;">
               
                <div class="svgContainer" style="width:150px;height:150px;border-radius:50%;border:3px solid #e22027;margin:0 auto 1rem;overflow:hidden;background:#fff5f5;">
        <svg class="mySVG" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 200 200" style="width:100%;height:100%">
          <defs>
            <circle id="armMaskPath" cx="100" cy="100" r="100"/>  
          </defs>
          <clipPath id="armMask">
            <use xlink:href="#armMaskPath" overflow="visible"/>
          </clipPath>
          <circle cx="100" cy="100" r="100" fill="#fdecee"/>
          <g class="body">
            <path fill="#FFFFFF" d="M193.3,135.9c-5.8-8.4-15.5-13.9-26.5-13.9H151V72c0-27.6-22.4-50-50-50S51,44.4,51,72v50H32.1 c-10.6,0-20,5.1-25.8,13l0,78h187L193.3,135.9z"/>
            <path fill="none" stroke="#773a3a" stroke-width="2.5" stroke-linecap="round" stroke-linejoinn="round" d="M193.3,135.9 c-5.8-8.4-15.5-13.9-26.5-13.9H151V72c0-27.6-22.4-50-50-50S51,44.4,51,72v50H32.1c-10.6,0-20,5.1-25.8,13"/>
            <path fill="#fadddd" d="M100,156.4c-22.9,0-43,11.1-54.1,27.7c15.6,10,34.2,15.9,54.1,15.9s38.5-5.8,54.1-15.9 C143,167.5,122.9,156.4,100,156.4z"/>
          </g>
          <g class="earL">
            <g class="outerEar" fill="#fff5f5" stroke="#e22027" stroke-width="2.5">
              <circle cx="47" cy="83" r="11.5"/>
              <path d="M46.3 78.9c-2.3 0-4.1 1.9-4.1 4.1 0 2.3 1.9 4.1 4.1 4.1" stroke-linecap="round" stroke-linejoin="round"/>
            </g>
            <g class="earHair">
              <rect x="51" y="64" fill="#FFFFFF" width="15" height="35"/>
              <path d="M53.4 62.8C48.5 67.4 45 72.2 42.8 77c3.4-.1 6.8-.1 10.1.1-4 3.7-6.8 7.6-8.2 11.6 2.1 0 4.2 0 6.3.2-2.6 4.1-3.8 8.3-3.7 12.5 1.2-.7 3.4-1.4 5.2-1.9" fill="#fff" stroke="#e22027" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </g>
          </g>
          <g class="earR">
            <g class="outerEar" fill="#fff5f5" stroke="#e22027" stroke-width="2.5">
              <circle cx="155" cy="83" r="11.5"/>
              <path d="M155.7 78.9c2.3 0 4.1 1.9 4.1 4.1 0 2.3-1.9 4.1-4.1 4.1" stroke-linecap="round" stroke-linejoin="round"/>
            </g>
            <g class="earHair">
              <rect x="131" y="64" fill="#FFFFFF" width="20" height="35"/>
              <path d="M148.6 62.8c4.9 4.6 8.4 9.4 10.6 14.2-3.4-.1-6.8-.1-10.1.1 4 3.7 6.8 7.6 8.2 11.6-2.1 0-4.2 0-6.3.2 2.6 4.1 3.8 8.3 3.7 12.5-1.2-.7-3.4-1.4-5.2-1.9" fill="#fff" stroke="#e22027" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </g>
          </g>
          <path class="chin" d="M84.1 121.6c2.7 2.9 6.1 5.4 9.8 7.5l.9-4.5c2.9 2.5 6.3 4.8 10.2 6.5 0-1.9-.1-3.9-.2-5.8 3 1.2 6.2 2 9.7 2.5-.3-2.1-.7-4.1-1.2-6.1" fill="none" stroke="#773a3a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
          <path class="face" fill="#fff5f5" d="M134.5,46v35.5c0,21.815-15.446,39.5-34.5,39.5s-34.5-17.685-34.5-39.5V46"/>
          <path class="hair" fill="#FFFFFF" stroke="#773a3a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" d="M81.457,27.929 c1.755-4.084,5.51-8.262,11.253-11.77c0.979,2.565,1.883,5.14,2.712,7.723c3.162-4.265,8.626-8.27,16.272-11.235 c-0.737,3.293-1.588,6.573-2.554,9.837c4.857-2.116,11.049-3.64,18.428-4.156c-2.403,3.23-5.021,6.391-7.852,9.474"/>
          <g class="eyebrow">
            <path fill="#FFFFFF" d="M138.142,55.064c-4.93,1.259-9.874,2.118-14.787,2.599c-0.336,3.341-0.776,6.689-1.322,10.037 c-4.569-1.465-8.909-3.222-12.996-5.226c-0.98,3.075-2.07,6.137-3.267,9.179c-5.514-3.067-10.559-6.545-15.097-10.329 c-1.806,2.889-3.745,5.73-5.816,8.515c-7.916-4.124-15.053-9.114-21.296-14.738l1.107-11.768h73.475V55.064z"/>
            <path fill="#FFFFFF" stroke="#e22027" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" d="M63.56,55.102 c6.243,5.624,13.38,10.614,21.296,14.738c2.071-2.785,4.01-5.626,5.816-8.515c4.537,3.785,9.583,7.263,15.097,10.329 c1.197-3.043,2.287-6.104,3.267-9.179c4.087,2.004,8.427,3.761,12.996,5.226c0.545-3.348,0.986-6.696,1.322-10.037 c4.913-0.481,9.857-1.34,14.787-2.599"/>
          </g>
          <g class="eyeL">
            <circle cx="85.5" cy="78.5" r="3.5" fill="#e22027"/>
            <circle cx="84" cy="76" r="1" fill="#fff"/>
          </g>
          <g class="eyeR">
            <circle cx="114.5" cy="78.5" r="3.5" fill="#e22027"/>
            <circle cx="113" cy="76" r="1" fill="#fff"/>
          </g>
          <g class="mouth">
            <path class="mouthBG" fill="#7a1a24" d="M100.2,101c-0.4,0-1.4,0-1.8,0c-2.7-0.3-5.3-1.1-8-2.5c-0.7-0.3-0.9-1.2-0.6-1.8 c0.2-0.5,0.7-0.7,1.2-0.7c0.2,0,0.5,0.1,0.6,0.2c3,1.5,5.8,2.3,8.6,2.3s5.7-0.7,8.6-2.3c0.2-0.1,0.4-0.2,0.6-0.2 c0.5,0,1,0.3,1.2,0.7c0.4,0.7,0.1,1.5-0.6,1.9c-2.6,1.4-5.3,2.2-7.9,2.5C101.7,101,100.5,101,100.2,101z"/>
            <path style="display: none;" class="mouthSmallBG" fill="#617E92" d="M100.2,101c-0.4,0-1.4,0-1.8,0c-2.7-0.3-5.3-1.1-8-2.5c-0.7-0.3-0.9-1.2-0.6-1.8 c0.2-0.5,0.7-0.7,1.2-0.7c0.2,0,0.5,0.1,0.6,0.2c3,1.5,5.8,2.3,8.6,2.3s5.7-0.7,8.6-2.3c0.2-0.1,0.4-0.2,0.6-0.2 c0.5,0,1,0.3,1.2,0.7c0.4,0.7,0.1,1.5-0.6,1.9c-2.6,1.4-5.3,2.2-7.9,2.5C101.7,101,100.5,101,100.2,101z"/>
            <path style="display: none;" class="mouthMediumBG" d="M95,104.2c-4.5,0-8.2-3.7-8.2-8.2v-2c0-1.2,1-2.2,2.2-2.2h22c1.2,0,2.2,1,2.2,2.2v2 c0,4.5-3.7,8.2-8.2,8.2H95z"/>
            <path style="display: none;" class="mouthLargeBG" d="M100 110.2c-9 0-16.2-7.3-16.2-16.2 0-2.3 1.9-4.2 4.2-4.2h24c2.3 0 4.2 1.9 4.2 4.2 0 9-7.2 16.2-16.2 16.2z" fill="#617e92" stroke="#773a3a" stroke-linejoin="round" stroke-width="2.5"/>
            <defs>
              <path id="mouthMaskPath" d="M100.2,101c-0.4,0-1.4,0-1.8,0c-2.7-0.3-5.3-1.1-8-2.5c-0.7-0.3-0.9-1.2-0.6-1.8 c0.2-0.5,0.7-0.7,1.2-0.7c0.2,0,0.5,0.1,0.6,0.2c3,1.5,5.8,2.3,8.6,2.3s5.7-0.7,8.6-2.3c0.2-0.1,0.4-0.2,0.6-0.2 c0.5,0,1,0.3,1.2,0.7c0.4,0.7,0.1,1.5-0.6,1.9c-2.6,1.4-5.3,2.2-7.9,2.5C101.7,101,100.5,101,100.2,101z"/>
            </defs>
            <clipPath id="mouthMask">
              <use xlink:href="#mouthMaskPath" overflow="visible"/>
            </clipPath>
            <g clip-path="url(#mouthMask)"><g class="tongue"><circle cx="100" cy="107" r="8" fill="#e4677f"/><ellipse class="tongueHighlight" cx="100" cy="100.5" rx="3" ry="1.5" opacity=".15" fill="#fff"/></g></g>
            <path clip-path="url(#mouthMask)" class="tooth" style="fill:#FFFFFF;" d="M106,97h-4c-1.1,0-2-0.9-2-2v-2h8v2C108,96.1,107.1,97,106,97z"/>
            <path class="mouthOutline" fill="none" stroke="#a01a2e" stroke-width="2.5" stroke-linejoin="round" d="M100.2,101c-0.4,0-1.4,0-1.8,0c-2.7-0.3-5.3-1.1-8-2.5c-0.7-0.3-0.9-1.2-0.6-1.8 c0.2-0.5,0.7-0.7,1.2-0.7c0.2,0,0.5,0.1,0.6,0.2c3,1.5,5.8,2.3,8.6,2.3s5.7-0.7,8.6-2.3c0.2-0.1,0.4-0.2,0.6-0.2 c0.5,0,1,0.3,1.2,0.7c0.4,0.7,0.1,1.5-0.6,1.9c-2.6,1.4-5.3,2.2-7.9,2.5C101.7,101,100.5,101,100.2,101z"/>
          </g>
          <path class="nose" d="M97.7 79.9h4.7c1.9 0 3 2.2 1.9 3.7l-2.3 3.3c-.9 1.3-2.9 1.3-3.8 0l-2.3-3.3c-1.3-1.6-.2-3.7 1.8-3.7z" fill="#e22027"/>
          <g class="arms" clip-path="url(#armMask)">
            <g class="armL">
              <path fill="#fadddd" stroke="#773a3a" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="2.5" d="M121.3 97.4L111 58.7l38.8-10.4 20 36.1z"/>
              <path fill="#fadddd" stroke="#773a3a" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="2.5" d="M134.4 52.5l19.3-5.2c2.7-.7 5.4.9 6.1 3.5.7 2.7-.9 5.4-3.5 6.1L146 59.7M160.8 76.5l19.4-5.2c2.7-.7 5.4.9 6.1 3.5.7 2.7-.9 5.4-3.5 6.1l-18.3 4.9M158.3 66.8l23.1-6.2c2.7-.7 5.4.9 6.1 3.5.7 2.7-.9 5.4-3.5 6.1l-23.1 6.2M150.9 58.4l26-7c2.7-.7 5.4.9 6.1 3.5.7 2.7-.9 5.4-3.5 6.1l-21.3 5.7"/>
              <path fill="#f3a9a9" d="M178.8 74.7l2.2-.6c1.1-.3 2.2.3 2.4 1.4.3 1.1-.3 2.2-1.4 2.4l-2.2.6-1-3.8zM180.1 64l2.2-.6c1.1-.3 2.2.3 2.4 1.4.3 1.1-.3 2.2-1.4 2.4l-2.2.6-1-3.8zM175.5 54.9l2.2-.6c1.1-.3 2.2.3 2.4 1.4.3 1.1-.3 2.2-1.4 2.4l-2.2.6-1-3.8zM152.1 49.4l2.2-.6c1.1-.3 2.2.3 2.4 1.4.3 1.1-.3 2.2-1.4 2.4l-2.2.6-1-3.8z"/>
              <path fill="#fff" stroke="#773a3a" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M123.5 96.8c-41.4 14.9-84.1 30.7-108.2 35.5L1.2 80c33.5-9.9 71.9-16.5 111.9-21.8"/>
              <path fill="#fff" stroke="#773a3a" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M108.5 59.4c7.7-5.3 14.3-8.4 22.8-13.2-2.4 5.3-4.7 10.3-6.7 15.1 4.3.3 8.4.7 12.3 1.3-4.2 5-8.1 9.6-11.5 13.9 3.1 1.1 6 2.4 8.7 3.8-1.4 2.9-2.7 5.8-3.9 8.5 2.5 3.5 4.6 7.2 6.3 11-4.9-.8-9-.7-16.2-2.7M94.5 102.8c-.6 4-3.8 8.9-9.4 14.7-2.6-1.8-5-3.7-7.2-5.7-2.5 4.1-6.6 8.8-12.2 14-1.9-2.2-3.4-4.5-4.5-6.9-4.4 3.3-9.5 6.9-15.4 10.8-.2-3.4.1-7.1 1.1-10.9M97.5 62.9c-1.7-2.4-5.9-4.1-12.4-5.2-.9 2.2-1.8 4.3-2.5 6.5-3.8-1.8-9.4-3.1-17-3.8.5 2.3 1.2 4.5 1.9 6.8-5-.6-11.2-.9-18.4-1 2 2.9.9 3.5 3.9 6.2"/>
            </g>
            <g class="armR">
              <path fill="#fadddd" stroke="#773a3a" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="2.5" d="M265.4 97.3l10.4-38.6-38.9-10.5-20 36.1z"/>
              <path fill="#fadddd" stroke="#773a3a" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="2.5" d="M252.4 52.4L233 47.2c-2.7-.7-5.4.9-6.1 3.5-.7 2.7.9 5.4 3.5 6.1l10.3 2.8M226 76.4l-19.4-5.2c-2.7-.7-5.4.9-6.1 3.5-.7 2.7.9 5.4 3.5 6.1l18.3 4.9M228.4 66.7l-23.1-6.2c-2.7-.7-5.4.9-6.1 3.5-.7 2.7.9 5.4 3.5 6.1l23.1 6.2M235.8 58.3l-26-7c-2.7-.7-5.4.9-6.1 3.5-.7 2.7.9 5.4 3.5 6.1l21.3 5.7"/>
              <path fill="#f3a9a9" d="M207.9 74.7l-2.2-.6c-1.1-.3-2.2.3-2.4 1.4-.3 1.1.3 2.2 1.4 2.4l2.2.6 1-3.8zM206.7 64l-2.2-.6c-1.1-.3-2.2.3-2.4 1.4-.3 1.1.3 2.2 1.4 2.4l2.2.6 1-3.8zM211.2 54.8l-2.2-.6c-1.1-.3-2.2.3-2.4 1.4-.3 1.1.3 2.2 1.4 2.4l2.2.6 1-3.8zM234.6 49.4l-2.2-.6c-1.1-.3-2.2.3-2.4 1.4-.3 1.1.3 2.2 1.4 2.4l2.2.6 1-3.8z"/>
              <path fill="#fff" stroke="#773a3a" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M263.3 96.7c41.4 14.9 84.1 30.7 108.2 35.5l14-52.3C352 70 313.6 63.5 273.6 58.1"/>
              <path fill="#fff" stroke="#773a3a" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M278.2 59.3l-18.6-10 2.5 11.9-10.7 6.5 9.9 8.7-13.9 6.4 9.1 5.9-13.2 9.2 23.1-.9M284.5 100.1c-.4 4 1.8 8.9 6.7 14.8 3.5-1.8 6.7-3.6 9.7-5.5 1.8 4.2 5.1 8.9 10.1 14.1 2.7-2.1 5.1-4.4 7.1-6.8 4.1 3.4 9 7 14.7 11 1.2-3.4 1.8-7 1.7-10.9M314 66.7s5.4-5.7 12.6-7.4c1.7 2.9 3.3 5.7 4.9 8.6 3.8-2.5 9.8-4.4 18.2-5.7.1 3.1.1 6.1 0 9.2 5.5-1 12.5-1.6 20.8-1.9-1.4 3.9-2.5 8.4-2.5 8.4"/>
            </g>        
          </g>
        </svg>
      </div>
                <h2 style="color: #181818; margin-bottom: 0.5rem; font-size: 2rem; font-weight: bold;">Kayıt Ol</h2>
                <p style="color: #666; font-size: 1rem;">Hesap oluşturun ve seyahat etmeye başlayın</p>
                <div style="width: 60px; height: 4px; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); border-radius: 2px; margin: 1rem auto 0;"></div>
            </div>
            
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
            
            <form method="POST" action="">
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="full_name" style="color: #181818; font-weight: 600; margin-bottom: 0.5rem; display: block;">Ad Soyad *</label>
                    <input type="text" id="full_name" name="full_name" required 
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" 
                           style="width: 100%; padding: 1rem; border: 2px solid #e0e0e0; border-radius: 15px; font-size: 1rem; background: rgba(255, 255, 255, 0.9); color: #181818; transition: all 0.3s ease;">
                </div>
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="email" style="color: #181818; font-weight: 600; margin-bottom: 0.5rem; display: block;">E-posta *</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                           style="width: 100%; padding: 1rem; border: 2px solid #e0e0e0; border-radius: 15px; font-size: 1rem; background: rgba(255, 255, 255, 0.9); color: #181818; transition: all 0.3s ease;">
                </div>
                
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label for="password" style="color: #181818; font-weight: 600; margin-bottom: 0.5rem; display: block;">Şifre *</label>
                        <input type="password" id="password" name="password" required 
                               style="width: 100%; padding: 1rem; border: 2px solid #e0e0e0; border-radius: 15px; font-size: 1rem; background: rgba(255, 255, 255, 0.9); color: #181818; transition: all 0.3s ease;">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password" style="color: #181818; font-weight: 600; margin-bottom: 0.5rem; display: block;">Şifre Tekrar *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               style="width: 100%; padding: 1rem; border: 2px solid #e0e0e0; border-radius: 15px; font-size: 1rem; background: rgba(255, 255, 255, 0.9); color: #181818; transition: all 0.3s ease;">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full" style="width: 100%; padding: 1.2rem; background: linear-gradient(135deg, #e22027 0%, #c41e3a 100%); color: #ffffff; text-decoration: none; border-radius: 25px; font-weight: 600; text-align: center; border: none; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(226, 32, 39, 0.3); font-size: 1.1rem;">
                    <i class="fas fa-user-plus" style="margin-right: 8px;"></i>
                    Kayıt Ol
                </button>
            </form>
            
            <div class="auth-footer" style="text-align: center; margin-top: 2rem;">
                <p style="color: #666; margin-bottom: 0.5rem;">Zaten hesabınız var mı? <a href="login.php" style="color: #e22027; text-decoration: none; font-weight: 600;">Giriş yapın</a></p>
                <p style="color: #666;"><a href="index.php" style="color: #e22027; text-decoration: none; font-weight: 600;">Ana sayfaya dön</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/1.20.3/TweenMax.min.js"></script>
    <script>
    (function(){
        var emailEl = document.getElementById('email');
        var svg = document.querySelector('.svgContainer');
        var eyeL = document.querySelector('.eyeL');
        var eyeR = document.querySelector('.eyeR');
        function getPosition(el){var r=el.getBoundingClientRect();return {x:r.left+window.scrollX,y:r.top+window.scrollY};}
        function onEmailInput(){ if(!emailEl||!svg) return; var dX=(Math.min(emailEl.selectionEnd||0,(emailEl.value||'').length)/Math.max((emailEl.value||'').length||1,1))-.5; var moveX=dX*10; TweenMax.to(eyeL,0.4,{x:moveX,ease:Power2.easeOut}); TweenMax.to(eyeR,0.4,{x:moveX,ease:Power2.easeOut}); }
        if(emailEl){ emailEl.addEventListener('input', onEmailInput); emailEl.addEventListener('focus', function(){ var g=emailEl.parentElement; if(g){g.classList.add('focusWithText');}}); emailEl.addEventListener('blur', function(){ var g=emailEl.parentElement; if(g){g.classList.remove('focusWithText');} TweenMax.to([eyeL,eyeR],0.4,{x:0,y:0});}); }
    })();
    </script>

<script>
    (function(){
      var email = document.querySelector('#email'), password = document.querySelector('#password'), mySVG = document.querySelector('.svgContainer'),
          armL = document.querySelector('.armL'), armR = document.querySelector('.armR'), eyeL = document.querySelector('.eyeL'), eyeR = document.querySelector('.eyeR'),
          nose = document.querySelector('.nose'), mouth = document.querySelector('.mouth'), mouthBG = document.querySelector('.mouthBG'),
          mouthSmallBG = document.querySelector('.mouthSmallBG'), mouthMediumBG = document.querySelector('.mouthMediumBG'), mouthLargeBG = document.querySelector('.mouthLargeBG'),
          mouthMaskPath = document.querySelector('#mouthMaskPath'), mouthOutline = document.querySelector('.mouthOutline'), tooth = document.querySelector('.tooth'),
          tongue = document.querySelector('.tongue'), chin = document.querySelector('.chin'), face = document.querySelector('.face'), eyebrow = document.querySelector('.eyebrow'),
          outerEarL = document.querySelector('.earL .outerEar'), outerEarR = document.querySelector('.earR .outerEar'), earHairL = document.querySelector('.earL .earHair'), earHairR = document.querySelector('.earR .earHair'), hair = document.querySelector('.hair');

      var caretPos, curEmailIndex, screenCenter, svgCoords, eyeMaxHorizD = 20, eyeMaxVertD = 10, noseMaxHorizD = 23, noseMaxVertD = 10, dFromC, eyeDistH, eyeLDistV, eyeRDistV, eyeDistR, mouthStatus = 'small';

      function getAngle(x1,y1,x2,y2){ return Math.atan2(y1 - y2, x1 - x2); }
      function getPosition(el){ var r = el.getBoundingClientRect(); return { x: r.left + window.scrollX, y: r.top + window.scrollY }; }

      function safeTo(target, dur, props){ if(target){ TweenMax.to(target, dur, props); } }
      function safeSet(target, props){ if(target){ TweenMax.set(target, props); } }

      function getCoord(e){
        if(!email || !mySVG) return;
        var carPos = email.selectionEnd,
            div = document.createElement('div'),
            span = document.createElement('span'),
            copyStyle = getComputedStyle(email),
            emailCoords = {}, caretCoords = {}, centerCoords = {};
        [].forEach.call(copyStyle, function(prop){ div.style[prop] = copyStyle[prop]; });
        div.style.position = 'absolute';
        document.body.appendChild(div);
        div.textContent = email.value.substr(0, carPos);
        span.textContent = email.value.substr(carPos) || '.';
        div.appendChild(span);
        emailCoords = getPosition(email);
        caretCoords = getPosition(span);
        centerCoords = getPosition(mySVG);
        svgCoords = getPosition(mySVG);
        screenCenter = centerCoords.x + (mySVG.offsetWidth / 2);
        caretPos = caretCoords.x + emailCoords.x;
        dFromC = screenCenter - caretPos;
        var pFromC = Math.round((caretPos / screenCenter) * 100) / 100;
        if(pFromC > 1){ pFromC -= 2; pFromC = Math.abs(pFromC); }
        eyeDistH = -dFromC * .05;
        if(eyeDistH > eyeMaxHorizD) eyeDistH = eyeMaxHorizD; else if(eyeDistH < -eyeMaxHorizD) eyeDistH = -eyeMaxHorizD;
        var eyeLCoords = {x: svgCoords.x + 84, y: svgCoords.y + 76}, eyeRCoords = {x: svgCoords.x + 113, y: svgCoords.y + 76};
        var noseCoords = {x: svgCoords.x + 97, y: svgCoords.y + 81}, mouthCoords = {x: svgCoords.x + 100, y: svgCoords.y + 100};
        var eyeLAngle = getAngle(eyeLCoords.x, eyeLCoords.y, emailCoords.x + caretCoords.x, emailCoords.y + 25);
        var eyeLX = Math.cos(eyeLAngle) * eyeMaxHorizD, eyeLY = Math.sin(eyeLAngle) * eyeMaxVertD;
        var eyeRAngle = getAngle(eyeRCoords.x, eyeRCoords.y, emailCoords.x + caretCoords.x, emailCoords.y + 25);
        var eyeRX = Math.cos(eyeRAngle) * eyeMaxHorizD, eyeRY = Math.sin(eyeRAngle) * eyeMaxVertD;
        var noseAngle = getAngle(noseCoords.x, noseCoords.y, emailCoords.x + caretCoords.x, emailCoords.y + 25);
        var noseX = Math.cos(noseAngle) * noseMaxHorizD, noseY = Math.sin(noseAngle) * noseMaxVertD;
        var mouthAngle = getAngle(mouthCoords.x, mouthCoords.y, emailCoords.x + caretCoords.x, emailCoords.y + 25);
        var mouthX = Math.cos(mouthAngle) * noseMaxHorizD, mouthY = Math.sin(mouthAngle) * noseMaxVertD, mouthR = Math.cos(mouthAngle) * 6;
        var chinX = mouthX * .8, chinY = mouthY * .5, chinS = 1 - ((dFromC * .15) / 100); if(chinS > 1){ chinS = 1 - (chinS - 1); }
        var faceX = mouthX * .3, faceY = mouthY * .4, faceSkew = Math.cos(mouthAngle) * 5, eyebrowSkew = Math.cos(mouthAngle) * 25;

        safeTo(eyeL, 1, {x: -eyeLX , y: -eyeLY, ease: Expo.easeOut});
        safeTo(eyeR, 1, {x: -eyeRX , y: -eyeRY, ease: Expo.easeOut});
        safeTo(nose, 1, {x: -noseX, y: -noseY, rotation: mouthR, transformOrigin: 'center center', ease: Expo.easeOut});
        safeTo(mouth, 1, {x: -mouthX , y: -mouthY, rotation: mouthR, transformOrigin: 'center center', ease: Expo.easeOut});
        safeTo(chin, 1, {x: -chinX, y: -chinY, scaleY: chinS, ease: Expo.easeOut});
        safeTo(face, 1, {x: -faceX, y: -faceY, skewX: -faceSkew, transformOrigin: 'center top', ease: Expo.easeOut});
        safeTo(eyebrow, 1, {x: -faceX, y: -faceY, skewX: -eyebrowSkew, transformOrigin: 'center top', ease: Expo.easeOut});
        safeTo(outerEarL, 1, {x: Math.cos(mouthAngle) * 4, y: -Math.cos(mouthAngle) * 5, ease: Expo.easeOut});
        safeTo(outerEarR, 1, {x: Math.cos(mouthAngle) * 4, y: Math.cos(mouthAngle) * 5, ease: Expo.easeOut});
        safeTo(earHairL, 1, {x: -Math.cos(mouthAngle) * 4, y: -Math.cos(mouthAngle) * 5, ease: Expo.easeOut});
        safeTo(earHairR, 1, {x: -Math.cos(mouthAngle) * 4, y: Math.cos(mouthAngle) * 5, ease: Expo.easeOut});
        safeTo(hair, 1, {x: Math.cos(mouthAngle) * 6, scaleY: 1.2, transformOrigin: 'center bottom', ease: Expo.easeOut});
        document.body.removeChild(div);
      }

      function onEmailInput(e){ if(!email) return; getCoord(e); var value = e.target.value; curEmailIndex = value.length; }
      function onEmailFocus(e){ if(!email) return; e.target.parentElement.classList.add('focusWithText'); getCoord(e); }
      function onEmailBlur(e){ if(!email) return; if(e.target.value === ''){ e.target.parentElement.classList.remove('focusWithText'); } resetFace(); }
      var passwordFocused = false;
      var confirmPasswordFocused = false;

      function coverEyes(){ 
        safeTo(armL, .45, {x: -93, y: 2, rotation: 0, ease: Quad.easeOut}); 
        safeTo(armR, .45, {x: -93, y: 2, rotation: 0, ease: Quad.easeOut, delay: .1}); 
      }
      function uncoverEyes(){ 
        // Sadece her iki şifre alanından da çıkıldığında gözleri aç
        setTimeout(function() {
          if(!passwordFocused && !confirmPasswordFocused) {
            safeTo(armL, 1.35, {y: 220, ease: Quad.easeOut}); 
            safeTo(armL, 1.35, {rotation: 105, ease: Quad.easeOut, delay: .1}); 
            safeTo(armR, 1.35, {y: 220, ease: Quad.easeOut}); 
            safeTo(armR, 1.35, {rotation: -105, ease: Quad.easeOut, delay: .1}); 
          }
        }, 10);
      }
      function resetFace(){ safeTo([eyeL, eyeR], 1, {x: 0, y: 0, ease: Expo.easeOut}); safeTo(nose, 1, {x: 0, y: 0, scaleX: 1, scaleY: 1, ease: Expo.easeOut}); safeTo(mouth, 1, {x: 0, y: 0, rotation: 0, ease: Expo.easeOut}); safeTo(chin, 1, {x: 0, y: 0, scaleY: 1, ease: Expo.easeOut}); safeTo([face, eyebrow], 1, {x: 0, y: 0, skewX: 0, ease: Expo.easeOut}); safeTo([outerEarL, outerEarR, earHairL, earHairR, hair], 1, {x: 0, y: 0, scaleY: 1, ease: Expo.easeOut}); }

      if(email){
        email.addEventListener('focus', onEmailFocus);
        email.addEventListener('blur', onEmailBlur);
        email.addEventListener('input', onEmailInput);
      }
      if(password){ 
        password.addEventListener('focus', function() { passwordFocused = true; coverEyes(); }); 
        password.addEventListener('blur', function() { passwordFocused = false; uncoverEyes(); }); 
      }
      
      // Şifre tekrar input'u için de aynı davranışı ekle
      var confirmPassword = document.querySelector('#confirm_password');
      if(confirmPassword){ 
        confirmPassword.addEventListener('focus', function() { confirmPasswordFocused = true; coverEyes(); }); 
        confirmPassword.addEventListener('blur', function() { confirmPasswordFocused = false; uncoverEyes(); }); 
      }
      safeSet(armL, {x: -93, y: 220, rotation: 105, transformOrigin: 'top left'});
      safeSet(armR, {x: -93, y: 220, rotation: -105, transformOrigin: 'top right'});
    })();
    </script>

    <?php renderFooter(); ?>

</body>
</html>
