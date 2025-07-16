<?php
// Eğer session başlamamışsa başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PDO veri tabanı bağlantısını belirt (IDE ve kod tamamlama için)
/** @var PDO $pdo */

// Veritabanı bağlantısını içeren dosyayı dahil et
require 'db.php';

// Hata mesajı göstermek için fonksiyon tanımlaması
function showError($message) {
    // HTML şablonu içinde Bootstrap ile stil verilmiş hata sayfası oluşturur
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8" />
        <title>Hata</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    </head>
    <body style="background:#f8f9fa; display:flex; justify-content:center; align-items:center; height:100vh;">
        <div class="alert alert-danger text-center shadow p-4 rounded" style="max-width:400px; width:100%;">
            <h4 class="alert-heading">Hata!</h4>
            <p>$message</p>
            <a href="login.php" class="btn btn-primary mt-3">Giriş Sayfasına Dön</a>
        </div>
    </body>
    </html>
HTML;
    exit; // Hata gösterdikten sonra scriptin devam etmesini engelle
}

// Eğer istek POST ise (form gönderilmişse)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Formdan gelen email ve şifreyi al, emailde boşlukları kırp
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Email veya şifre boş ise hata göster
    if (empty($email) || empty($password)) {
        showError("Lütfen email ve şifrenizi girin.");
    }

    // Veritabanında email ile kullanıcıyı ara
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Kullanıcı varsa ve şifre doğrulanıyorsa (password_verify ile hash karşılaştırması)
    if ($user && password_verify($password, $user['password'])) {
        // Oturum değişkenlerine kullanıcı bilgilerini ata
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        // Eğer "Beni hatırla" seçeneği işaretlenmişse
        if (!empty($_POST['remember_me'])) {
            // Rastgele 64 karakterlik token oluştur (32 byte hex)
            $token = bin2hex(random_bytes(32));

            // Token'ı veritabanındaki ilgili kullanıcıya kaydet
            // users tablosunda remember_token adında bir sütun olmalı
            $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
            $stmt->execute([$token, $user['id']]);

            // Tarayıcıya token içeren cookie gönder (30 gün geçerli, sadece HTTP üzerinden erişilebilir)
            setcookie("remember_token", $token, time() + (30 * 24 * 60 * 60), "/", "", false, true);
        }

        // Kullanıcının rolüne göre uygun dashboard sayfasına yönlendir
        switch ($user['role']) {
            case 'admin':
                header("Location: dashboard_admin.php");
                exit;
            case 'doctor':
                header("Location: dashboard_doctor.php");
                exit;
            case 'patient':

                header("Location: dashboard_patient.php");
                exit;
            default:
                // Eğer tanınmayan rol varsa hata göster
                showError("Bilinmeyen kullanıcı rolü!");
        }
    } else {
        // Kullanıcı yoksa veya şifre yanlışsa hata mesajı göster
        showError("Email veya şifre hatalı!");
    }
} else {
    // İstek POST değilse, örneğin direkt sayfaya GET ile erişilmişse hata göster
    showError("Geçersiz istek!");
}
