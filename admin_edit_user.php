<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["is_admin"] != 1) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "147369", "hasta_sistemi");
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}

$error = "";
$success = "";

// ID ile kullanıcıyı al
if (!isset($_GET["id"])) {
    header("Location: admin.php");
    exit;
}

$user_id = intval($_GET["id"]);

// Düzenleme işlemi
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $is_admin = isset($_POST["is_admin"]) ? 1 : 0;

    if (!$name || !$email) {
        $error = "İsim ve e-posta zorunludur.";
    } else {
        // Kullanıcıyı güncelle
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, is_admin = ? WHERE id = ?");
        $stmt->bind_param("ssii", $name, $email, $is_admin, $user_id);
        if ($stmt->execute()) {
            $success = "Kullanıcı başarıyla güncellendi.";
        } else {
            $error = "Güncelleme sırasında hata oluştu.";
        }
        $stmt->close();
    }
}

// Güncel kullanıcı bilgilerini çek
$stmt = $conn->prepare("SELECT name, email, is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

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
