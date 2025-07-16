<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Hasta Randevu Sistemi</title>
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
            color: #333;
        }

        .card {
            border: none;
            border-radius: 20px;
        }

        .btn-lg {
            padding: 0.75rem 1.25rem;
            font-size: 1.1rem;
            border-radius: 12px;
        }

        .title {
            font-weight: 600;
            color: #2c3e50;
        }

        .card p {
            font-size: 1rem;
        }
    </style>
</head>
<body>

<div class="container vh-100 d-flex flex-column justify-content-center align-items-center">
    <div class="card shadow-lg p-5 bg-white" style="max-width: 420px; width: 100%;">
        <h1 class="mb-3 text-center title">Hasta Randevu Sistemi</h1>
        <p class="text-center mb-4">Hoş geldiniz! Lütfen bir seçim yapın:</p>
        <div class="d-grid gap-3">
            <a href="login.php" class="btn btn-primary btn-lg">
                <i class="bi bi-box-arrow-in-right me-2"></i>Giriş Yap
            </a>
            <a href="register_patient.php" class="btn btn-outline-primary btn-lg">
                <i class="bi bi-person-plus me-2"></i>Kayıt Ol
            </a>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
