<?php
// Eğer session başlamadıysa başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'remember_me_check.php';  // Beni hatırla çerezini kontrol eden dosya
require 'db.php';                 // Veritabanı bağlantısı

/** @var PDO $pdo */

// Oturum zaman aşımı süresi (saniye cinsinden)
$timeout = 120;

// Son etkinlik zamanını session'dan al, yoksa şu anki zamanı kullan
$last_activity = $_SESSION['last_activity'] ?? time();

// Son etkinlik ile şu an arasındaki geçen süre
$inactive = time() - $last_activity;

// Kalan süreyi hesapla
$remaining = $timeout - $inactive;
if ($remaining < 0) $remaining = 0;

// Eğer belirlenen süre geçtiyse (zaman aşımı olduysa)
if ($inactive > $timeout) {
    // Session içeriğini temizle
    session_unset();
    // Oturumu tamamen sonlandır
    session_destroy();

    // Eğer "remember_token" çerezi varsa sil
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, "/");
    }

    // Login sayfasına yönlendir ve timeout parametresi gönder
    header("Location: login.php?timeout=1");
    exit;
}

// Son etkinlik zamanını güncelle (şu an)
$_SESSION['last_activity'] = time();

// Admin giriş kontrolü: user_id yoksa veya role admin değilse login sayfasına yönlendir
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Giriş yapan adminin user_id'sini al
$admin_id = $_SESSION['user_id'];

// Adminin ismini users tablosundan çek
$stmtAdmin = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmtAdmin->execute([$admin_id]);
$admin = $stmtAdmin->fetch();

// Eğer admin bulunamazsa 'Yönetici' yaz
$admin_name = $admin ? $admin['name'] : 'Yönetici';

// Kullanıcıları çek (sadece hasta ve doktor olanlar)
$stmt = $pdo->prepare("SELECT * FROM users WHERE role IN ('patient', 'doctor')");
$stmt->execute();
$users = $stmt->fetchAll();

// Randevuları çek, hasta ve doktor isimleri ile birlikte
$stmt2 = $pdo->prepare("
    SELECT a.id, a.appointment_time, a.status, 
       pu.name as patient_name, du.name as doctor_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN users pu ON p.user_id = pu.id
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users du ON d.user_id = du.id
    ORDER BY a.appointment_time DESC
");
$stmt2->execute();
$appointments = $stmt2->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>Admin Paneli</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        /* Sayfa ve kart tasarım stilleri */
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #6fb1fc, #4364f7);
            color: #2c3e50;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        .container {
            max-width: 1200px;
        }
        .card {
            border-radius: 20px;
        }
        h1, h2 {
            font-weight: 600;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .btn-danger {
            border-radius: 10px;
        }
        .btn-add {
            font-weight: 500;
            border-radius: 10px;
        }
        a {
            text-decoration: none;
        }
        /* Oturum sayacı konumu ve görünümü */
        #session-timer {
            position: fixed;
            top: 10px;
            left: 10px;
            background: rgba(255,255,255,0.85);
            border-radius: 8px;
            padding: 8px 12px;
            font-weight: 600;
            font-size: 16px;
            color: #4364f7;
            box-shadow: 0 0 8px rgba(0,0,0,0.15);
            z-index: 9999;
            user-select: none;
        }
    </style>
</head>
<body>

<!-- Oturum kalan süresini gösteren alan -->
<div id="session-timer">Oturum Süresi: <span id="timer"><?= $remaining ?></span> saniye</div>

<div class="container bg-white p-5 shadow-lg rounded-4">
    <h1 class="text-center mb-1">👨‍⚕️ Admin Paneli</h1>
    <p class="text-center text-muted mb-4">Hoş geldiniz, <strong> <?= htmlspecialchars($admin_name) ?> </strong></p>

    <!-- Eğer timeout parametresi varsa, uyarı mesajı göster -->
    <?php if (isset($_GET['timeout'])): ?>
        <div class="alert alert-warning text-center">Oturumunuz 120 saniye boyunca işlem yapılmadığı için sonlandırıldı. Lütfen tekrar giriş yapın.</div>
    <?php endif; ?>

    <!-- Kullanıcılar ve çıkış butonları -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="m-0">Kullanıcılar</h2>
        <div>
            <!-- Çıkış butonu -->
            <form method="POST" action="logout.php" style="display:inline;" onsubmit="return confirm('Çıkış yapmak istediğinize emin misiniz?');">
                <button type="submit" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-box-arrow-right"></i> Çıkış
                </button>
            </form>
            <!-- Yeni kullanıcı ekleme butonu -->
            <a href="add_user.php" class="btn btn-success btn-add">
                <i class="bi bi-person-plus-fill me-1"></i>Yeni Kullanıcı Ekle
            </a>
        </div>
    </div>

    <!-- Kullanıcılar tablosu -->
    <div class="table-responsive mb-5">
        <table class="table table-bordered table-striped">
            <thead class="table-primary">
            <tr>
                <th>ID</th>
                <th>Ad</th>
                <th>Email</th>
                <th>Rol</th>
                <th>İşlemler</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td>
                        <!-- Kullanıcı silme butonu, tıklamadan önce onay alır -->
                        <a href="delete_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Silmek istediğinize emin misiniz?')">
                            <i class="bi bi-trash-fill"></i> Sil
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Randevular tablosu -->
    <h2 class="mb-3">Randevular</h2>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-secondary">
            <tr>
                <th>ID</th>
                <th>Hasta</th>
                <th>Doktor</th>
                <th>Randevu Saati</th>
                <th>Durum</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($appointments as $app): ?>
                <tr>
                    <td><?= $app['id'] ?></td>
                    <td><?= htmlspecialchars($app['patient_name']) ?></td>
                    <td><?= htmlspecialchars($app['doctor_name']) ?></td>
                    <td><?= htmlspecialchars($app['appointment_time']) ?></td>
                    <td>
                        <?php
                        // Durumlar için Türkçe karşılıklar
                        $statusMap = [
                            'pending' => 'Beklemede',
                            'approved' => 'Onaylandı',
                            'reject' => 'İptal Edildi', // Buradaki 'reject' durumunu kontrol et, gerekirse 'rejected' yap
                        ];
                        // Eğer durum tanımlı değilse varsayılan metin
                        $translated = $statusMap[$app['status']] ?? 'Hasta İptal Etti';
                        ?>
                        <?= htmlspecialchars($translated) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Oturum kalan süresi için sayaç
    let timeLeft = <?= $remaining ?>;
    const timerEl = document.getElementById('timer');

    // Her saniye kalan süreyi azalt ve güncelle
    const interval = setInterval(() => {
        if (timeLeft <= 0) {
            clearInterval(interval);
            // Süre dolunca otomatik olarak login sayfasına yönlendir
            window.location.href = 'login.php?timeout=1';
        } else {
            timerEl.textContent = timeLeft;
            timeLeft--;
        }
    }, 1000);
</script>

</body>
</html>
