<?php
require_once __DIR__ . '/config.php';

if (isset($_SESSION["user_id"])) {
    header("Location: panel.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    try {
        $stmt = $pdo->prepare("SELECT id, name, email, password, is_admin FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["name"];
            $_SESSION["is_admin"] = $user["is_admin"];

            if ((int)$user["is_admin"] === 1) {
                header("Location: admin.php");
            } else {
                header("Location: panel.php");
            }
            exit;
        } else {
            $error = "Hatalı şifre veya e-posta.";
        }
    } catch (Throwable $e) {
        $error = "Sunucu hatası. Lütfen daha sonra tekrar deneyin.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr" data-bs-theme="dark">
<head>
  <meta charset="UTF-8" />
  <title>Giriş Yap - ARDİO</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="assets/styles.css" rel="stylesheet" />
</head>
<body class="auth-bg d-flex align-items-center">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-5 col-lg-4">
        <div class="text-center mb-3">
          <button class="btn btn-sm btn-outline-info" onclick="toggleTheme()" type="button"><span data-theme-label>Aydınlık</span> Moda Geç</button>
        </div>
        <div class="text-center mb-4">
          <a class="navbar-brand text-white fs-3 text-decoration-none" href="index.php">ARDİO</a>
          <div class="text-white-50 small">Gençliğin Teknolojisi</div>
        </div>
        <div class="card card-glass text-white shadow-lg">
          <div class="card-body p-4">
            <h3 class="mb-3 text-center">Giriş Yap</h3>
            <?php if ($error): ?>
              <div class="alert alert-danger mb-3"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="login.php" novalidate>
              <div class="mb-3">
                <label for="email" class="form-label">E-posta</label>
                <input type="email" id="email" name="email" class="form-control" required autofocus />
              </div>
              <div class="mb-3">
                <label for="password" class="form-label">Şifre</label>
                <input type="password" id="password" name="password" class="form-control" required />
              </div>
              <button type="submit" class="btn btn-primary-gradient w-100 py-2">Giriş Yap</button>
            </form>
            <p class="mt-3 text-center text-white-50">
              Hesabın yok mu? <a href="register.php" class="text-info fw-semibold text-decoration-none">Kayıt Ol</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/theme.js"></script>
</body>
</html>