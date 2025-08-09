<?php
session_start();
if (isset($_SESSION["user_id"])) {
    header("Location: panel.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";

    // Şifreyi SHA-256 ile hash'le
    $hashedPassword = hash("sha256", $password);

    $conn = new mysqli("localhost", "root", "147369", "hasta_sistemi");
    if ($conn->connect_error) {
        die("Bağlantı hatası: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT id, name, is_admin FROM users WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $hashedPassword);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $_SESSION["user_id"] = $row["id"];
        $_SESSION["user_name"] = $row["name"];
        $_SESSION["is_admin"] = $row["is_admin"];

        // Admin ise admin paneline yönlendir
        if ($row["is_admin"]) {
            header("Location: admin.php");
        } else {
            header("Location: panel.php");
        }
        exit;
    } else {
        $error = "Hatalı şifre veya e-posta.";
    }

    $stmt->close();
    $conn->close();
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
      Hesabın yok mu? <a href="register.php" class="text-info">Kayıt Ol</a>
    </p>
  </div>

</body>
</html>