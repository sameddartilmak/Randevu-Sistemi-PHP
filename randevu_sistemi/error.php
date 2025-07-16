<?php
// error.php
$error_message = $_GET['msg'] ?? 'Bilinmeyen bir hata oluştu.';
?>
<!DOCTYPE html>
<html>
<head><title>Hata</title></head>
<body>
<h2>Hata!</h2>
<p><?= htmlspecialchars($error_message) ?></p>
<p><a href="dashboard_doctor.php">Doktor Paneline Dön</a></p>
</body>
</html>
