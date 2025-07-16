<?php

// Veritabanı bağlantısı için gerekli parametreler
$host = "localhost";            // Veritabanı sunucusunun adresi (genelde localhost)
$dbname = "randevusistemi";    // Bağlanılacak veritabanı adı
$username = "root";             // Veritabanı kullanıcı adı (genellikle localhost için root)
$password = "";                 // Veritabanı şifresi (şifre yoksa boş bırakılır)

try {
    // PDO ile veritabanı bağlantısı kuruluyor.
    // charset=utf8mb4 ifadesi, Türkçe karakterler ve emoji gibi geniş karakter setlerini desteklemek için önemli.
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

    // PDO hata modunu istisna (exception) olarak ayarlıyoruz.
    // Böylece hata olursa PDOException fırlatılır ve kod daha güvenli ve kontrol edilebilir olur.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // Eğer bağlantı sırasında hata olursa buraya düşer.
    // Hata mesajı kullanıcıya gösterilir ve script çalışması durdurulur.
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
