<?php
session_start();
$admin_email = "admin@site.com";
$admin_password = "sifre123";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_POST["email"] === $admin_email && $_POST["password"] === $admin_password) {
        $_SESSION["admin"] = true;
        header("Location: admin_panel.php");
        exit;
    } else {
        $error = "Hatalı giriş!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Admin Giriş</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="card shadow">
          <div class="card-body">
            <h3 class="text-center mb-4">Admin Giriş</h3>
            <?php if (isset($error)): ?>
              <div class="alert alert-danger text-center"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST">
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Şifre</label>
                <input type="password" name="password" class="form-control" required>
              </div>
              <button type="submit" class="btn btn-danger w-100">Giriş Yap</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
