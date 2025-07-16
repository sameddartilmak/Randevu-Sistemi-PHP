<?php
/** @var PDO $pdo */
// db.php dosyanızda PDO bağlantısı olmalı
require 'db.php';

// Doktor bilgilerini buraya yaz
$name = "Dr. Züleyha";
$email = "züleyha@example.com";
$plainPassword = "züleyha12";

// Şifreyi hashle
$passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'doctor')");
    $stmt->execute([$name, $email, $passwordHash]);
    echo "<div style='font-family: Poppins, sans-serif; background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; max-width: 400px; margin: 30px auto; text-align:center;'>
            <strong>Başarılı!</strong> Doktor başarıyla eklendi:<br> <em>$name</em> ($email)
          </div>";
} catch (PDOException $e) {
    echo "<div style='font-family: Poppins, sans-serif; background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; max-width: 400px; margin: 30px auto; text-align:center;'>
            <strong>Hata:</strong> " . htmlspecialchars($e->getMessage()) . "
          </div>";
}
