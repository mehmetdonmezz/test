<?php
session_start();
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
        $conn = new mysqli("localhost", "root", "147369", "hasta_sistemi");
        if ($conn->connect_error) {
            die("Bağlantı hatası: " . $conn->connect_error);
        }

        // E-posta daha önce kayıtlı mı kontrol et
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Bu e-posta zaten kayıtlı.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $insert = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $insert->bind_param("sss", $name, $email, $hashed_password);

            if ($insert->execute()) {
                $success = "Kayıt başarılı! Giriş yapabilirsiniz.";
            } else {
                $error = "Kayıt sırasında bir hata oluştu.";
            }
            $insert->close();
        }
        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <title>Kayıt Ol - ARDİO</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-dark text-white d-flex justify-content-center align-items-center" style="height:100vh;">

  <div class="card bg-secondary p-4" style="width: 350px;">
    <h3 class="mb-4 text-center">Kayıt Ol</h3>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
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
      <button type="submit" class="btn btn-primary w-100">Kayıt Ol</button>
    </form>

    <p class="mt-3 text-center">
      Hesabın var mı? <a href="login.php" class="text-info">Giriş Yap</a>
    </p>
  </div>

</body>
</html>
