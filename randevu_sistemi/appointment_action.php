<?php
// Eğer session başlatılmamışsa, başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** @var PDO $pdo */
require 'db.php'; // Veritabanı bağlantısı dahil et

// Doktor giriş kontrolü: Oturumda kullanıcı yoksa veya rolü doktor değilse login sayfasına gönder
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

// Oturumdaki user_id ile doctors tablosundan doktorun kendi id'sini al
$doctor_user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmt->execute([$doctor_user_id]);
$doctorRow = $stmt->fetch();

// Eğer doktor bulunamazsa hata sayfasına yönlendir
if (!$doctorRow) {
    header("Location: error.php?msg=" . urlencode("Doktor bulunamadı."));
    exit;
}
$doctor_id = $doctorRow['id'];

// Sadece POST metoduyla gelen istekleri kabul et, değilse hata sayfasına gönder
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: error.php?msg=" . urlencode("Geçersiz istek yöntemi."));
    exit;
}

// CSRF token doğrulaması yap, geçerli değilse hata sayfasına gönder
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header("Location: error.php?msg=" . urlencode("Geçersiz CSRF token."));
    exit;
}

// POST verilerinde id ve action alanlarının varlığını kontrol et, eksikse hata sayfasına gönder
if (!isset($_POST['id']) || !isset($_POST['action'])) {
    header("Location: error.php?msg=" . urlencode("Eksik veri."));
    exit;
}
//
$appointment_id = (int)$_POST['id']; // Randevu id'si, integer tipine çevir
$action = $_POST['action']; // İşlem türü (accept veya reject)

// İşlem türünün geçerli olup olmadığını kontrol et
if (!in_array($action, ['accept', 'reject'])) {
    header("Location: error.php?msg=" . urlencode("Geçersiz işlem."));
    exit;
}

// Randevuyu veritabanından kontrol et:
// - İstenen id'ye sahip,
// - Bu doktora ait,
// - Ve durumu 'pending' olan randevu olmalı
$stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND doctor_id = ? AND status = 'pending'");
$stmt->execute([$appointment_id, $doctor_id]);
$appointment = $stmt->fetch();

// Randevu yoksa veya işleme kapalıysa hata sayfasına gönder
if (!$appointment) {
    header("Location: error.php?msg=" . urlencode("Randevu bulunamadı veya işleme kapalı."));
    exit;
}

// Yeni durum belirle: kabul edilirse 'approved', reddedilirse 'rejected'
$new_status = ($action === 'accept') ? 'approved' : 'rejected';

// Randevunun durumunu güncelle
$update = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
$success = $update->execute([$new_status, $appointment_id]);

// Güncelleme başarısızsa hata sayfasına gönder
if (!$success) {
    header("Location: error.php?msg=" . urlencode("Güncelleme başarısız."));
    exit;
}

// Başarılı ise doktora ait panel sayfasına yönlendir ve mesaj gönder
header("Location: dashboard_doctor.php?msg=" . urlencode("Randevu durumu güncellendi."));
exit;
