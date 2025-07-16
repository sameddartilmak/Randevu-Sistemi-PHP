<?php
/** @var PDO $pdo */
// Güvenli session başlatma işlemi
if (session_status() === PHP_SESSION_NONE) {
    // Session cookie parametrelerini ayarla
    session_set_cookie_params([
        'lifetime' => 0, // Tarayıcı kapandığında cookie silinsin
        'path' => '/',   // Cookie tüm site için geçerli
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // Sadece HTTPS'de gönderilsin
        'httponly' => true, // JavaScript erişimine kapalı, sadece HTTP protokolüyle erişilebilir
        'samesite' => 'Lax' // CSRF saldırılarına karşı koruma
    ]);
    session_start(); // Session başlat
}

// Oturum açılmamışsa ve "remember_token" cookie'si varsa
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    require 'db.php'; // Veritabanı bağlantısını dahil et

    $token = $_COOKIE['remember_token']; // Cookie'deki token'ı al

    // Veritabanında token doğrulaması yap
    $stmt = $pdo->prepare("SELECT id, role FROM users WHERE remember_token = ? AND remember_token IS NOT NULL");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // Token geçerliyse, session'a kullanıcı bilgilerini ata
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
    } else {
        // Token geçersizse, cookie'yi sil
        setcookie(
            'remember_token',
            '',
            time() - 3600, // geçmiş bir zamana ayarla, böylece silinir
            '/',
            '',
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // secure parametresi
            true // httponly parametresi
        );
        unset($_COOKIE['remember_token']); // PHP tarafında da cookie değişkenini kaldır
    }
}

