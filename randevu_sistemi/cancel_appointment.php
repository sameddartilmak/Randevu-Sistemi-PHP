<?php
// Eğer session başlatılmamışsa başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** @var PDO $pdo */
require 'remember_me_check.php'; // "Beni hatırla" kontrolü için ek dosya
require 'db.php'; // Veritabanı bağlantısı

// Hasta giriş kontrolü: oturumda user_id yoksa veya rol patient değilse login sayfasına yönlendir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.php");
    exit;
}

// Sadece POST yöntemi ile istek kabul edilir, değilse hata verir
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Geçersiz istek yöntemi.");
}

// CSRF token kontrolü: token yoksa veya uyuşmuyorsa hata ver
if (empty($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Geçersiz CSRF token.");
}

// Oturumdaki user_id bilgisini al
$user_id = $_SESSION['user_id'];

// Bu user_id'ye bağlı hasta kaydını patients tablosundan çek
$stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([$user_id]);
$patient = $stmt->fetch();

// Hasta kaydı yoksa hata ver
if (!$patient) {
    die("Hasta bulunamadı.");
}

$patient_id = $patient['id'];

// POST ile gelen randevu id'sinin varlığını kontrol et
if (!isset($_POST['id'])) {
    die("Geçersiz istek.");
}

$appointment_id = (int)$_POST['id']; // Gelen randevu id'sini integer'a çevir

// Randevunun bu hastaya ait ve durumu 'pending' (beklemede) olup olmadığını kontrol et
$stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND patient_id = ? AND status = 'pending'");
$stmt->execute([$appointment_id, $patient_id]);
$appointment = $stmt->fetch();

// Randevu bulunamazsa veya iptal edilemez durumdaysa hata ver
if (!$appointment) {
    die("Randevu bulunamadı veya iptal edilemez.");
}

// Randevunun durumunu 'rejected' (iptal edilmiş) olarak güncelle
$update = $pdo->prepare("UPDATE appointments SET status = 'rejected' WHERE id = ?");
$success = $update->execute([$appointment_id]);

// Güncelleme başarısızsa hata ver
if (!$success) {
    die("Randevu iptal edilemedi.");
}

// İşlem başarılıysa kullanıcıya gösterilmek üzere flash mesajı session'a yaz
$_SESSION['flash_message'] = "Randevu başarıyla iptal edildi.";

// Hasta paneline yönlendir
header("Location: dashboard_patient.php");
exit;
