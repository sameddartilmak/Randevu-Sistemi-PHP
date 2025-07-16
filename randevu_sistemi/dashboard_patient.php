<?php
// Eğer session başlamadıysa, session'ı başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo ('. ');
/** @var PDO $pdo */
// Gerekli dosyaları dahil et
require 'remember_me_check.php';  // Beni hatırla çerez kontrolü
require 'db.php';                 // Veritabanı bağlantısı

// Oturum zaman aşımı süresi (120 saniye = 2 dakika)
$session_timeout = 120;

// Eğer son aktivite zamanı set edilmişse
if (isset($_SESSION['last_activity'])) {
    // Son aktiviteden itibaren geçen süreyi hesapla
    $inactive = time() - $_SESSION['last_activity'];

    // Eğer oturum süresi aşılmışsa session'u temizle ve login sayfasına yönlendir
    if ($inactive > $session_timeout) {
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit;
    }
}

// Oturum süresi sayacı için önceki son aktivite zamanını al
// Eğer yoksa şu anki zamanı kullan
$lastActivity = $_SESSION['last_activity'] ?? time();

// Kalan oturum süresini hesapla
$remaining_time = $session_timeout - (time() - $lastActivity);

// Eğer negatifse sıfır yap
if ($remaining_time < 0) $remaining_time = 0;

// Son olarak, son aktivite zamanını şu anki zamanla güncelle
$_SESSION['last_activity'] = time();

// Kullanıcının giriş yapıp yapmadığını ve rolünün 'patient' (hasta) olduğunu kontrol et
// Değilse login sayfasına yönlendir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.php");
    exit;
}

// Giriş yapan hastanın user_id'sini değişkene al
$patient_user_id = $_SESSION['user_id'];

// Giriş yapan hastanın ismini veritabanından çek
$stmtName = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmtName->execute([$patient_user_id]);
$patient = $stmtName->fetch();

// Hasta ismini al, eğer bulunamazsa 'Bilinmeyen Kullanıcı' yaz
$patient_name = $patient ? $patient['name'] : 'Bilinmeyen Kullanıcı';

// CSRF (Cross-Site Request Forgery) saldırılarına karşı token oluştur
// Eğer token yoksa yeni token oluşturup session'a kaydet
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Doktor listesini veritabanından çekiyoruz
// users tablosundan role'u 'doctor' olanların id, isim ve email bilgilerini alıyoruz
$stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE role = 'doctor'");
$stmt->execute();
$doctors = $stmt->fetchAll();

// Hastanın mevcut randevularını listelemek için sorgu


$stmt2 = $pdo->prepare("
   SELECT 
    a.id AS appointment_id,
    a.appointment_time,
    a.status,
    d.id AS doctor_id,
    u.name AS doctor_name
FROM appointments a
JOIN doctors d ON a.doctor_id = d.id
JOIN users u ON d.user_id = u.id
WHERE a.patient_id = (
    SELECT id FROM patients WHERE user_id = ?
)
ORDER BY a.appointment_time DESC
");
// Sorguyu çalıştırırken hastanın user_id'sini parametre olarak gönderiyoruz
$stmt2->execute([$patient_user_id]);
// Tüm randevuları çek
$appointments = $stmt2->fetchAll();

?>


<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>Hasta Paneli</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #6fb1fc, #4364f7);
            color: #2c3e50;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        .container {
            max-width: 1100px;
        }
        h1, h2 {
            font-weight: 600;
        }
        .card {
            border-radius: 20px;
        }
        table th, table td {
            vertical-align: middle !important;
        }
        .btn-cancel {
            border-radius: 10px;
        }
        a {
            text-decoration: none;
        }
        #session-timer {
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.8);
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 600;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            z-index: 9999;
        }
        .status-approved {
            color: green;
            font-weight: 700;
        }
        .status-rejected {
            color: red;
            font-weight: 700;
        }
        .status-pending {
            color: #d69e2e; /* turuncu */
            font-weight: 700;
        }
    </style>
</head>
<body>

<!-- Oturum süresi sayacı -->
<div id="session-timer">
    Oturum süresi: <span id="countdown"><?= $remaining_time ?></span> saniye
</div>

<div class="container bg-white p-5 shadow-lg rounded-4">

    <h1 class="text-center mb-1">👩‍⚕️ Hasta Paneli</h1>
    <p class="text-center text-muted mb-4">Hoş geldiniz, <strong><?= htmlspecialchars($patient_name) ?></strong></p>

    <div class="text-end mb-4">
        <!-- Logout linki logout.php'ye yönlendiriyor -->
        <a href="logout.php" class="btn btn-outline-primary">Çıkış</a>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-success text-center">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <section class="mb-5">
        <h2 class="mb-4">Doktor Listesi</h2>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-primary">
                <tr>
                    <th>Doktor Adı</th>
                    <th>Email</th>
                    <th>Randevu Al</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($doctors as $doctor): ?>
                    <tr>
                        <td><?= htmlspecialchars($doctor['name']) ?></td>
                        <td><?= htmlspecialchars($doctor['email']) ?></td>
                        <td>
                            <a href="make_appointment.php?doctor_id=<?= $doctor['id'] ?>" class="btn btn-success btn-sm">
                                Randevu Al
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section>
        <h2 class="mb-4">Mevcut Randevularınız</h2>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-secondary">
                <tr>
                    <th>Doktor</th>
                    <th>Randevu Saati</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
                </thead>
                <tbody>
                <?php if (count($appointments) === 0): ?>
                    <tr>
                        <td colspan="4" class="text-center">Henüz randevunuz yok.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($appointments as $app): ?>
                        <tr>
                            <td><?= htmlspecialchars($app['doctor_name']) ?></td>
                            <td><?= date('d.m.Y H:i', strtotime($app['appointment_time'])) ?></td>
                            <td class="<?php
                            if ($app['status'] === 'approved') echo 'status-approved';
                            elseif ($app['status'] === 'rejected') echo 'status-rejected';
                            else echo 'status-pending';
                            ?>">
                                <?php
                                if ($app['status'] === 'approved') echo 'Kabul Edildi';
                                elseif ($app['status'] === 'rejected') echo 'İptal Edildi';
                                else echo 'Beklemede';
                                ?>
                            </td>
                            <td>
                                <?php if ($app['status'] === 'pending'): ?>
                                    <form method="POST" action="cancel_appointment.php" class="d-inline" onsubmit="return confirm('Randevuyu iptal etmek istediğinize emin misiniz?');">
                                        <input type="hidden" name="id" value="<?= $app['appointment_id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm btn-cancel">İptal Et</button>
                                    </form>
                                <?php else: ?>
                                    <span>-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    let countdownElement = document.getElementById('countdown');
    let timeLeft = parseInt(countdownElement.textContent);

    function updateTimer() {
        if (timeLeft <= 0) {
            countdownElement.textContent = 0;
            window.location.href = 'index.php?timeout=1';
            return;
        }
        countdownElement.textContent = timeLeft;
        timeLeft--;
    }

    setInterval(updateTimer, 1000);
</script>

</body>
</html>
