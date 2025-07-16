<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Giriş Yap</title>
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

        .card-title {
            font-weight: 600;
            color: #2c3e50;
        }

        .form-label {
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow-lg p-4 bg-white" style="max-width: 420px; width: 100%;">
        <div class="card-body">
            <h2 class="card-title mb-4 text-center">Giriş Yap</h2>
            <form action="process_login.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" required autofocus />
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Şifre:</label>
                    <input type="password" class="form-control" id="password" name="password" required />
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me" />
                    <label class="form-check-label" for="remember_me">Beni Hatırla</label>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Giriş Yap
                </button>
            </form>

            <a href="index.php" class="btn btn-secondary btn-lg w-100">
                <i class="bi bi-house-door me-2"></i>Ana Menüye Dön
            </a>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
