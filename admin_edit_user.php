<?php
require_once __DIR__ . '/config.php';
requireAdmin();

if (!isset($_GET["id"])) {
    header("Location: admin.php");
    exit;
}

$user_id = (int)($_GET["id"]);

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $is_admin = isset($_POST["is_admin"]) ? 1 : 0;

    if (!$name || !$email) {
        $error = "İsim ve e-posta zorunludur.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, is_admin = ? WHERE id = ?");
        if ($stmt->execute([$name, $email, $is_admin, $user_id])) {
            $success = "Kullanıcı başarıyla güncellendi.";
        } else {
            $error = "Güncelleme sırasında hata oluştu.";
        }
    }
}

$stmt = $pdo->prepare("SELECT name, email, is_admin FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: admin.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <title>Kullanıcı Düzenle - Admin Paneli</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">

<div class="container py-5">
  <h2>Kullanıcı Düzenle</h2>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="mb-3">
      <label for="name" class="form-label">İsim</label>
      <input type="text" id="name" name="name" class="form-control" required value="<?= htmlspecialchars($user["name"]) ?>" />
    </div>
    <div class="mb-3">
      <label for="email" class="form-label">E-posta</label>
      <input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($user["email"]) ?>" />
    </div>
    <div class="mb-3 form-check">
      <input type="checkbox" id="is_admin" name="is_admin" class="form-check-input" <?= $user["is_admin"] ? "checked" : "" ?> />
      <label for="is_admin" class="form-check-label">Admin Yetkisi</label>
    </div>
    <button type="submit" class="btn btn-primary">Güncelle</button>
    <a href="admin.php" class="btn btn-secondary ms-2">Geri Dön</a>
  </form>
</div>

</body>
</html>
