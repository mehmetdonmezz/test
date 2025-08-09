<?php
require_once __DIR__ . '/config.php';

if (isset($_SESSION["user_id"])) {
    header("Location: panel.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $password_confirm = $_POST["password_confirm"] ?? "";

    if (!$name || !$email || !$password || !$password_confirm) {
        $error = "Lütfen tüm alanları doldurun.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Geçerli bir e-posta girin.";
    } elseif ($password !== $password_confirm) {
        $error = "Şifreler uyuşmuyor.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Bu e-posta zaten kayıtlı.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, 0)");
                if ($insert->execute([$name, $email, $hashed_password])) {
                    $success = "Kayıt başarılı! Giriş yapabilirsiniz.";
                } else {
                    $error = "Kayıt sırasında bir hata oluştu.";
                }
            }
        } catch (Throwable $e) {
            $error = "Sunucu hatası. Lütfen daha sonra tekrar deneyin.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <title>Kayıt Ol - ARDİO</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="assets/styles.css" rel="stylesheet" />
</head>
<body class="auth-bg d-flex align-items-center">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="text-center mb-4">
          <a class="navbar-brand text-white fs-3 text-decoration-none" href="index.php">ARDİO</a>
          <div class="text-white-50 small">Gençliğin Teknolojisi</div>
        </div>
        <div class="card card-glass text-white shadow-lg">
          <div class="card-body p-4">
            <h3 class="mb-3 text-center">Kayıt Ol</h3>
            <?php if ($error): ?>
              <div class="alert alert-danger mb-3"><?= htmlspecialchars($error) ?></div>
            <?php elseif ($success): ?>
              <div class="alert alert-success mb-3"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <form method="POST" action="register.php" novalidate>
              <div class="mb-3">
                <label for="name" class="form-label">Ad Soyad</label>
                <input type="text" id="name" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" />
              </div>
              <div class="mb-3">
                <label for="email" class="form-label">E-posta</label>
                <input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
              </div>
              <div class="mb-3">
                <label for="password" class="form-label">Şifre</label>
                <input type="password" id="password" name="password" class="form-control" required />
              </div>
              <div class="mb-3">
                <label for="password_confirm" class="form-label">Şifre Tekrar</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control" required />
              </div>
              <button type="submit" class="btn btn-primary-gradient w-100 py-2">Kayıt Ol</button>
            </form>
            <p class="mt-3 text-center text-white-50">
              Hesabın var mı? <a href="login.php" class="text-info fw-semibold text-decoration-none">Giriş Yap</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
