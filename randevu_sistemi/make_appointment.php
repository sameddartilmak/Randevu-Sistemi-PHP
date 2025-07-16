<?php
// Eğer session başlatılmamışsa, session'ı başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PDO bağlantısını temsil eden değişkenin türünü belirt (IDE için)
/** @var PDO $pdo */

// Giriş kontrolü ve "beni hatırla" kontrolü için gerekli dosyaları dahil et
require 'remember_me_check.php';
require 'db.php';

// Kullanıcı giriş yapmış mı ve rolü 'patient' mı kontrol et
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    // Değilse login sayfasına yönlendir
    header("Location: login.php");
    exit;
}

// URL'de doktor ID parametresi var mı kontrol et
if (!isset($_GET['doctor_id'])) {
    // Yoksa hata mesajı ver ve işlemi durdur
    die("Doktor seçimi yapılmadı.");
}
// Gelen doktor_id'yi tam sayı olarak al (güvenlik için)
$doctor_id = (int)$_GET['doctor_id'];

// Doktor bilgilerini veritabanından al
// doctors tablosu ile users tablosunu join ederek doktorun ID'si ve ismini çekiyoruz
$stmt = $pdo->prepare("
    SELECT d.id AS doctor_id, u.name 
    FROM doctors d 
    JOIN users u ON d.user_id = u.id 
    WHERE d.id = ?
");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch();

// Eğer doktor bilgisi bulunamazsa hata ver
if (!$doctor) {
    die("Geçersiz doktor.");
}

// Şu anki giriş yapmış kullanıcının hasta ID'sini alıyoruz
$stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();

// Hasta bilgisi yoksa hata ver
if (!$patient) {
    die("Hasta bilgisi bulunamadı.");
}
// Hasta ID'sini değişkene atıyoruz
$patient_id = $patient['id'];

// CSRF koruması için token oluştur (eğer yoksa)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Rastgele 32 byte hex token üret
}
$csrf_token = $_SESSION['csrf_token'];

// Form POST edilmişse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token kontrolü yap, geçersizse işlemi durdur
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Geçersiz CSRF token.");
    }

    // Formdan randevu saati alınır, boş ise hata mesajı ayarlanır
    $appointment_time = $_POST['appointment_time'] ?? '';
    if (empty($appointment_time)) {
        $error = "Lütfen randevu saati seçiniz.";
    } else {
        // Aynı doktor için aynı saatte onaylanmış randevu var mı kontrol et
        $check = $pdo->prepare("
            SELECT COUNT(*) 
            FROM appointments 
            WHERE doctor_id = ? AND appointment_time = ? AND status = 'accepted'
        ");
        $check->execute([$doctor_id, $appointment_time]);
        $count = $check->fetchColumn();

        // Eğer o saat doluysa hata mesajı ayarla
        if ($count > 0) {
            $error = "Seçtiğiniz saat doludur, lütfen başka bir saat seçin.";
        } else {
            // Randevuyu veritabanına ekle, durumu 'pending' olarak ayarla
            $insert = $pdo->prepare("
                INSERT INTO appointments (doctor_id, patient_id, appointment_time, status, created_at) 
                VALUES (?, ?, ?, 'pending', NOW())
            ");
            $insert->execute([$doctor_id, $patient_id, $appointment_time]);

            // İşlem başarılıysa hastanın dashboard'una mesajla yönlendir
            header("Location: dashboard_patient.php?msg=Randevu talebiniz gönderildi.");
            exit;
        }
    }
}

// Kullanıcının seçebileceği örnek randevu saatleri
$available_times = [
    '2025-06-28 09:00:00',
    '2025-06-28 10:00:00',
    '2025-06-28 11:00:00',
    '2025-06-28 14:00:00',
    '2025-06-28 15:00:00',
];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($doctor['name']) ?> için Randevu Al</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #6fb1fc, #4364f7);
            color: #2c3e50;
            padding: 40px 20px;
        }
        .container {
            max-width: 600px;
            background: white;
            padding: 30px 40px;
            border-radius: 20px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }
        h1 {
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
        }
        label {
            font-weight: 600;
        }
        .btn-submit {
            border-radius: 10px;
        }
        .link-back {
            display: block;
            margin-top: 20px;
            text-align: center;
            font-weight: 600;
            color: #4364f7;
            text-decoration: none;
        }
        .link-back:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <h1><?= htmlspecialchars($doctor['name']) ?> için Randevu Al</h1>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-4">
            <label for="appointment_time" class="form-label">Randevu Saati Seçin:</label>
            <select name="appointment_time" id="appointment_time" class="form-select" required>
                <option value="">-- Saat Seçin --</option>
                <?php foreach ($available_times as $time): ?>
                    <option value="<?= $time ?>"><?= date('d.m.Y H:i', strtotime($time)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>" />
        <button type="submit" class="btn btn-primary btn-submit w-100">Randevu Talep Et</button>
    </form>

    <a href="dashboard_patient.php" class="link-back">&larr; Geri Dön</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
