<?php
// Eğer session başlamadıysa başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** @var PDO $pdo */
require 'remember_me_check.php';  // Beni hatırla çerez kontrolü için
require 'db.php';                 // Veritabanı bağlantısı

$session_timeout = 120;  // Oturum zaman aşımı süresi (saniye)

// Oturum zaman aşımı kontrolü
if (isset($_SESSION['last_activity'])) {
    // Son etkinlikten itibaren geçen süreyi hesapla
    $inactive = time() - $_SESSION['last_activity'];
    // Eğer süre zaman aşımını geçtiyse oturumu temizle ve login sayfasına yönlendir
    if ($inactive > $session_timeout) {
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit;
    }
}

// Son etkinlik zamanını al, yoksa şu anki zamanı kullan
$lastActivity = $_SESSION['last_activity'] ?? time();

// Kalan oturum süresini hesapla
$remaining_time = $session_timeout - (time() - $lastActivity);

// Negatif olursa sıfır yap
if ($remaining_time < 0) $remaining_time = 0;

// Son etkinlik zamanını güncelle (şu an)
$_SESSION['last_activity'] = time();

// Kullanıcı giriş ve rol kontrolü (sadece doktorlar erişebilir)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

// Giriş yapan doktorun user_id'sini al
$doctor_user_id = $_SESSION['user_id'];

// Doktorun kullanıcı tablosundaki tam adını çek
$stmtName = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmtName->execute([$doctor_user_id]);
$doctor = $stmtName->fetch();
// Eğer doktor bulunamazsa 'Bilinmeyen Doktor' yaz
$doctor_name = $doctor ? $doctor['name'] : 'Bilinmeyen Doktor';

// Doktor tablosundan, user_id'ye karşılık gelen doctor_id'yi al
$stmtDocId = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
$stmtDocId->execute([$doctor_user_id]);
$doctorRow = $stmtDocId->fetch();
// Eğer doktor bulunamazsa hata mesajı göster ve işlemi durdur
if (!$doctorRow) {
    die("Doktor bulunamadı.");
}
$doctor_id = $doctorRow['id'];

// CSRF (Cross-Site Request Forgery) saldırılarına karşı token oluştur
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Bekleyen randevuları çek (status = 'pending')
// Hasta bilgisi için user tablosu ile JOIN yapılıyor
$stmt_pending = $pdo->prepare("
    SELECT a.id, a.appointment_time, a.status, u.name AS patient_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE a.doctor_id = ? AND a.status = 'pending'
    ORDER BY a.appointment_time ASC
");
$stmt_pending->execute([$doctor_id]);
$appointments_pending = $stmt_pending->fetchAll();

// Geçmiş randevuları çek (status 'approved' veya 'rejected')
$stmt_history = $pdo->prepare("
    SELECT a.id, a.appointment_time, a.status, u.name AS patient_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE a.doctor_id = ? AND a.status IN ('approved', 'rejected')
    ORDER BY a.appointment_time DESC
");
$stmt_history->execute([$doctor_id]);
$appointments_history = $stmt_history->fetchAll();

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>Doktor Paneli</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        /* Sayfa genel stil ayarları */
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
        .btn-action {
            border-radius: 10px;
        }
        a, button {
            user-select: none; /* Metnin seçilmesini engeller */
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
            display: flex;
            align-items: center;
            gap: 15px;
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
            color: #d69e2e;
            font-weight: 700;
        }
        #logout-btn {
            background-color: #dc3545;
            border: none;
            color: white;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        #logout-btn:hover {
            background-color: #b02a37;
        }
    </style>
</head>
<body>

<!-- Oturum süresi sayaç alanı ve çıkış butonu -->
<div id="session-timer">
    <div>Oturum Süresi: <span id="countdown"><?= $remaining_time ?></span> saniye</div>
    <!-- Çıkış formu, çıkış yapmadan önce onay ister -->
    <form method="POST" action="logout.php" onsubmit="return confirm('Çıkış yapmak istediğinize emin misiniz?');" style="margin:0;">
        <button id="logout-btn" type="submit">Çıkış</button>
    </form>
</div>

<div class="container bg-white p-5 shadow-lg rounded-4">

    <h1 class="text-center mb-1">👨‍⚕️ Doktor Paneli</h1>
    <p class="text-center text-muted mb-4">Hoş geldiniz, <strong><?= htmlspecialchars($doctor_name) ?></strong></p>

    <section class="mb-5">
        <h2 class="mb-4">Bekleyen Randevu Talepleriniz</h2>

        <?php if (empty($appointments_pending)): ?>
            <!-- Eğer bekleyen randevu yoksa bilgi mesajı göster -->
            <div class="alert alert-info text-center">Henüz bekleyen randevu talebiniz yok.</div>
        <?php else: ?>
            <!-- Bekleyen randevular tablosu -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle text-center">
                    <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Hasta</th>
                        <th>Randevu Saati</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($appointments_pending as $app): ?>
                        <tr>
                            <td><?= $app['id'] ?></td>
                            <td><?= htmlspecialchars($app['patient_name']) ?></td>
                            <!-- Tarih saat formatlanıyor -->
                            <td><?= date('d.m.Y H:i', strtotime($app['appointment_time'])) ?></td>
                            <td class="status-pending">Beklemede</td>
                            <td>
                                <!-- Randevu kabul formu, CSRF token ile korunuyor -->
                                <form method="POST" action="appointment_action.php" class="d-inline" onsubmit="return confirm('Randevuyu kabul etmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="id" value="<?= $app['id'] ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <button type="submit" class="btn btn-success btn-sm btn-action">Kabul Et</button>
                                </form>
                                <!-- Randevu reddetme formu, CSRF token ile korunuyor -->
                                <form method="POST" action="appointment_action.php" class="d-inline" onsubmit="return confirm('Randevuyu reddetmek istediğinize emin misiniz?');">
                                    <input type="hidden" name="id" value="<?= $app['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <button type="submit" class="btn btn-danger btn-sm btn-action">Reddet</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section>
        <h2 class="mb-4">Geçmiş Randevularınız</h2>

        <?php if (empty($appointments_history)): ?>
            <!-- Eğer geçmiş randevu yoksa bilgi mesajı göster -->
            <div class="alert alert-secondary text-center">Henüz geçmiş randevunuz yok.</div>
        <?php else: ?>
            <!-- Geçmiş randevular tablosu -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle text-center">
                    <thead class="table-secondary">
                    <tr>
                        <th>ID</th>
                        <th>Hasta</th>
                        <th>Randevu Saati</th>
                        <th>Durum</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($appointments_history as $app): ?>
                        <tr>
                            <td><?= $app['id'] ?></td>
                            <td><?= htmlspecialchars($app['patient_name']) ?></td>
                            <td><?= date('d.m.Y H:i', strtotime($app['appointment_time'])) ?></td>
                            <!-- Duruma göre renk ve metin -->
                            <td class="<?= $app['status'] === 'approved' ? 'status-approved' : 'status-rejected' ?>">
                                <?= $app['status'] === 'approved' ? 'Kabul Edildi' : 'Reddedildi' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

</div>

<!-- Bootstrap JS Bundle (dropdown, modal gibi bileşenler için) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Oturum süresi sayacı için JavaScript kodu
    let countdownElement = document.getElementById('countdown');
    let timeLeft = parseInt(countdownElement.textContent);

    function updateTimer() {
        if (timeLeft <= 0) {
            countdownElement.textContent = 0;
            // Süre dolunca anasayfaya timeout parametresi ile yönlendir
            window.location.href = 'index.php?timeout=1';
            return;
        }
        countdownElement.textContent = timeLeft;
        timeLeft--;
    }

    // Her saniye sayacı güncelle
    setInterval(updateTimer, 1000);
</script>

</body>
</html>
