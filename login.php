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
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <title>Giriş Yap - ARDİO</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-dark text-white d-flex justify-content-center align-items-center" style="height:100vh;">

  <div class="card bg-secondary p-4" style="width: 350px;">
    <h3 class="mb-4 text-center">Giriş Yap</h3>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="mb-3">
        <label for="email" class="form-label">E-posta</label>
        <input type="email" id="email" name="email" class="form-control" required autofocus />
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Şifre</label>
        <input type="password" id="password" name="password" class="form-control" required />
      </div>
      <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
    </form>

    <p class="mt-3 text-center">
      Hesabın yok mu? <a href="register.php" class="text-dark fw-bold">Kayıt Ol</a>
    </p>
  </div>

</body>
</html>