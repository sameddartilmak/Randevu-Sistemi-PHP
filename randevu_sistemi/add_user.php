<?php
session_start();
require 'remember_me_check.php';
require 'db.php';

if (!isset($pdo)) {
    die("Veritabanı bağlantısı sağlanamadı.");
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    if (function_exists('openssl_random_pseudo_bytes')) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    } else {
        $_SESSION['csrf_token'] = bin2hex(md5(uniqid(mt_rand(), true)));
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Geçersiz CSRF isteği.");
    }

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // Doğrulamalar
    if (empty($name)) {
        $errors[] = "İsim boş olamaz.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli bir email giriniz.";
    }
    if (!in_array($role, ['patient', 'doctor'])) {
        $errors[] = "Rol hatalı seçildi.";
    }
    if (empty($password)) {
        $errors[] = "Şifre boş olamaz.";
    }
    if ($password !== $password_confirm) {
        $errors[] = "Şifreler uyuşmuyor.";
    }

    // E-posta kontrolü
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    if (!$stmt->execute([$email])) {
        $errors[] = "Veritabanı hatası oluştu, lütfen daha sonra tekrar deneyiniz.";
    } elseif ($stmt->fetchColumn() > 0) {
        $errors[] = "Bu email zaten kayıtlı.";
    }

    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // users tablosuna ekle
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $password_hash, $role])) {
            $userId = $pdo->lastInsertId();

            // Role göre ilgili tabloya ekle
            if ($role === 'patient') {
                $stmt2 = $pdo->prepare("INSERT INTO patients (user_id) VALUES (?)");
                $stmt2->execute([$userId]);
            } elseif ($role === 'doctor') {
                $stmt2 = $pdo->prepare("INSERT INTO doctors (user_id) VALUES (?)");
                $stmt2->execute([$userId]);
            }

            header("Location: dashboard_admin.php?msg=Kullanıcı başarıyla eklendi.");
            exit;
        } else {
            $errors[] = "Kullanıcı eklenirken hata oluştu: " . implode(", ", $stmt->errorInfo());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>Kullanıcı Ekle - Admin Paneli</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #6fb1fc, #4364f7);
            color: #2c3e50;
            padding: 40px 0;
        }
        .container {
            max-width: 600px;
            background: white;
            padding: 30px 40px;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.15);
        }
        h1 {
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
        }
        .btn-submit {
            border-radius: 10px;
            font-weight: 600;
        }
        a.btn-back {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
        }
        .error-list {
            background: #f8d7da;
            border: 1px solid #f5c2c7;
            color: #842029;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="container shadow-lg">
    <h1>Yeni Kullanıcı Ekle</h1>

    <?php if (!empty($errors)): ?>
        <div class="error-list">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>" />

        <div class="mb-3">
            <label for="name" class="form-label">İsim:</label>
            <input type="text" id="name" name="name" class="form-control" required value="<?= isset($name) ? htmlspecialchars($name) : '' ?>" />
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email:</label>
            <input type="email" id="email" name="email" class="form-control" required value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" />
        </div>

        <div class="mb-3">
            <label for="role" class="form-label">Rol:</label>
            <select id="role" name="role" class="form-select" required>
                <option value="" <?= !isset($role) ? 'selected' : '' ?>>Seçiniz</option>
                <option value="patient" <?= (isset($role) && $role == 'patient') ? 'selected' : '' ?>>Hasta</option>
                <option value="doctor" <?= (isset($role) && $role == 'doctor') ? 'selected' : '' ?>>Doktor</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Şifre:</label>
            <input type="password" id="password" name="password" class="form-control" required />
        </div>

        <div class="mb-3">
            <label for="password_confirm" class="form-label">Şifre (Tekrar):</label>
            <input type="password" id="password_confirm" name="password_confirm" class="form-control" required />
        </div>

        <button type="submit" class="btn btn-primary btn-submit w-100">
            <i class="bi bi-person-plus-fill me-1"></i> Kullanıcı Ekle
        </button>
    </form>

    <a href="dashboard_admin.php" class="btn btn-link btn-back">
        <i class="bi bi-arrow-left"></i> Geri Dön
    </a>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
