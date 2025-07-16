<?php
/** @var PDO $pdo */
// Veritabanı bağlantısı için db.php dosyasını dahil et
require 'db.php';

// Eğer session başlatılmamışsa, başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tüm session değişkenlerini temizle (unset)
session_unset();

// Oturumu tamamen sonlandır (destroy)
session_destroy();

// Tarayıcıdaki 'remember_token' isimli çerezi (cookie) sil
// Süresi geçmiş bir tarih vererek çerezi kaldırıyoruz
// '/' tüm site için geçerli olması için path, HTTPS kontrolü ve HttpOnly bayrağı ile güvenlik artırılmış
setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);

// Tekrar db.php dosyasını dahil etmiş (tekrardan gerek yok ama var)
// Eğer session'da user_id varsa, yani kullanıcı bilgisi varsa
if (isset($_SESSION['user_id'])) {
    // Kullanıcının veritabanındaki remember_token değerini NULL yap (temizle)
    $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

// Kullanıcıyı login sayfasına yönlendir
header("Location: login.php");
exit;  // scriptin devamını engelle
