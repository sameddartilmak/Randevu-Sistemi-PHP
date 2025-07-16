<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kayıt Sonucu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #6fb1fc, #4364f7);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .alert-box {
            max-width: 500px;
            width: 100%;
        }
    </style>
</head>
<body>

<div class="alert-box">
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = 'patient';

        if (empty($name) || empty($email) || empty($password)) {
            echo '<div class="alert alert-danger">Lütfen tüm alanları doldurun.</div>';
        } else {
            // E-posta zaten kayıtlı mı kontrol et
            $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $check->execute([$email]);

            if ($check->fetchColumn() > 0) {
                echo '<div class="alert alert-warning">⚠️ Bu e-posta zaten kayıtlı. Lütfen başka bir e-posta deneyin.</div>';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Kullanıcıyı ekle
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$name, $email, $hashedPassword, $role])) {
                    $userId = $pdo->lastInsertId();

                    // Hasta tablosuna da ekle
                    $stmtPatient = $pdo->prepare("INSERT INTO patients (user_id) VALUES (?)");
                    $stmtPatient->execute([$userId]);

                    // Kayıt başarılı mesajı ve butonla girişe yönlendirme
                    echo '<div class="alert alert-success shadow-sm text-center" role="alert">
                    ✅ <strong>Kayıt başarılı!</strong><br>Giriş yapabilirsiniz.
                    <a href="login.php" class="btn btn-primary mt-3">Giriş Yap</a>
                  </div>';
                } else {
                    echo '<div class="alert alert-danger">❌ Kullanıcı kaydı sırasında bir hata oluştu.</div>';
                }
            }
        }
    } else {
        echo '<div class="alert alert-secondary">Geçersiz istek.</div>';
    }
    ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
