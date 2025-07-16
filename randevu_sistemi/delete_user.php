<?php

// Eğer session daha önce başlatılmadıysa, başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** @var PDO $pdo */
require 'remember_me_check.php';
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Geçersiz istek.");
}

$user_id = (int)$_GET['id'];

if ($user_id == $_SESSION['user_id']) {
    die("Kendi hesabınızı silemezsiniz.");
}

// Kullanıcı bilgilerini al (rol bilgisini de kullanacağız)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("Kullanıcı bulunamadı.");
}

// Kullanıcının rolüne göre patients veya doctors tablosundan da sil
if ($user['role'] === 'patient') {
    $delPatient = $pdo->prepare("DELETE FROM patients WHERE user_id = ?");
    $delPatient->execute([$user_id]);
} elseif ($user['role'] === 'doctor') {
    $delDoctor = $pdo->prepare("DELETE FROM doctors WHERE user_id = ?");
    $delDoctor->execute([$user_id]);
}

// Kullanıcıya ait randevuları sil
$delAppointments = $pdo->prepare("DELETE FROM appointments WHERE patient_id = ? OR doctor_id = ?");
$delAppointments->execute([$user_id, $user_id]);

// Son olarak users tablosundan kullanıcıyı sil
$delUser = $pdo->prepare("DELETE FROM users WHERE id = ?");
$delUser->execute([$user_id]);

header("Location: dashboard_admin.php?msg=Kullanıcı ve ilgili veriler silindi.");
exit;
