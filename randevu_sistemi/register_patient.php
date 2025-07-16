<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <!-- Responsive tasarım için viewport ayarı -->
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Hasta Kayıt</title>

    <!-- Google Fonts: Poppins fontu -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />

    <!-- Bootstrap CSS: Hazır stil ve grid sistemi için -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Bootstrap Icons: Butonlardaki ikonlar için -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />

    <style>
        /* Sayfa genel fontu ve arka plan renk geçişi */
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #6fb1fc, #4364f7); /* Mavi degrade */
            margin: 0;
            padding: 0;
        }

        /* Kart görünümü için stil (beyaz zemin, yuvarlak köşeler) */
        .card {
            border: none;
            border-radius: 20px;
        }

        /* Buton büyük ve yuvarlak köşeli yapılıyor */
        .btn-lg {
            padding: 0.75rem 1.25rem;
            font-size: 1.1rem;
            border-radius: 12px;
        }

        /* Kart başlığı kalın ve koyu renk */
        .card-title {
            font-weight: 600;
            color: #2c3e50;
        }

        /* Form label yazıları orta kalınlıkta */
        .form-label {
            font-weight: 500;
        }

        /* Hata mesajının kırmızı renk ve küçük font olması */
        .error-message {
            color: red;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

<!-- Sayfa içeriği, dikey ve yatay ortalanmış container -->
<div class="container d-flex justify-content-center align-items-center vh-100">
    <!-- Kayıt formunun bulunduğu beyaz kart -->
    <div class="card shadow-lg p-4 bg-white" style="max-width: 420px; width: 100%;">
        <div class="card-body">
            <h2 class="card-title mb-4 text-center">Hasta Kayıt</h2>

            <!-- Hata mesajlarının gösterileceği boş div -->
            <div id="error" class="error-message"></div>

            <!-- Kayıt formu: process_register.php dosyasına POST metodu ile gönderilir -->
            <form id="registerForm" action="process_register.php" method="POST" novalidate>
                <!-- Ad Soyad alanı -->
                <div class="mb-3">
                    <label for="name" class="form-label">Ad Soyad:</label>
                    <input type="text" class="form-control" id="name" name="name" required autofocus />
                </div>

                <!-- Email alanı -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" required />
                </div>

                <!-- Şifre alanı -->
                <div class="mb-3">
                    <label for="password" class="form-label">Şifre:</label>
                    <input type="password" class="form-control" id="password" name="password" required />
                </div>

                <!-- Gönder butonu, ikonlu ve tam genişlikte -->
                <button type="submit" class="btn btn-success btn-lg w-100 mb-3">
                    <i class="bi bi-person-plus-fill me-2"></i>Kayıt Ol
                </button>
            </form>

            <!-- Ana menüye dönmek için buton -->
            <a href="index.php" class="btn btn-secondary btn-lg w-100">
                <i class="bi bi-house-door me-2"></i>Ana Menüye Dön
            </a>
        </div>
    </div>
</div>

<!-- Bootstrap JS (modal, dropdown vb. için) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Form gönderilmeden önce basit frontend doğrulama yapılacak
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        // Form alanlarının değerlerini al, boşlukları kırp
        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();

        // Hata mesajlarının gösterileceği alanı temizle
        const errorDiv = document.getElementById('error');
        errorDiv.textContent = '';

        // Ad Soyad boşsa, form gönderimini engelle ve hata göster
        if (name === '') {
            e.preventDefault();
            errorDiv.textContent = 'Ad Soyad boş bırakılamaz.';
            return false;
        }

        // Email boşsa, engelle ve uyar
        if (email === '') {
            e.preventDefault();
            errorDiv.textContent = 'Email boş bırakılamaz.';
            return false;
        }

        // Email formatını basit regex ile kontrol et
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            e.preventDefault();
            errorDiv.textContent = 'Geçerli bir email giriniz.';
            return false;
        }

        // Şifre boşsa, engelle ve uyar
        if (password === '') {
            e.preventDefault();
            errorDiv.textContent = 'Şifre boş bırakılamaz.';
            return false;
        }

        // Şifre çok kısaysa, en az 6 karakter olmalı uyarısı
        if (password.length < 6) {
            e.preventDefault();
            errorDiv.textContent = 'Şifre en az 6 karakter olmalı.';
            return false;
        }
    });
</script>

</body>
</html>
