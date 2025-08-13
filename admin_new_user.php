<?php
require_once __DIR__ . '/config.php';
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    if (!$name || !$email || !$password) {
        $error = 'Lütfen tüm zorunlu alanları doldurun.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta girin.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Bu e-posta zaten kayıtlı.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $ins = $pdo->prepare('INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, ?)');
                if ($ins->execute([$name, $email, $hashed, $is_admin])) {
                    $success = 'Kullanıcı oluşturuldu.';
                } else {
                    $error = 'Kullanıcı oluşturulamadı.';
                }
            }
        } catch (Throwable $e) {
            $error = 'Sunucu hatası, daha sonra tekrar deneyin.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Yeni Kullanıcı - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="assets/styles.css" rel="stylesheet" />
</head>
<body class="bg-dark text-white">
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
      <a class="navbar-brand" href="admin.php">ARDİO Admin</a>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-light btn-sm" onclick="toggleTheme()" type="button"><span data-theme-label>Aydınlık</span></button>
        <a href="logout.php" class="btn btn-outline-light btn-sm">Çıkış Yap</a>
      </div>
    </div>
  </nav>

  <div class="container py-4" style="max-width:720px;">
    <h1 class="h4 mb-3">Yeni Kullanıcı Oluştur</h1>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="card bg-black border-0 shadow-sm">
      <div class="card-body">
        <form method="post" action="">
          <div class="mb-3">
            <label class="form-label">Ad Soyad</label>
            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" />
          </div>
          <div class="mb-3">
            <label class="form-label">E-posta</label>
            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
          </div>
          <div class="mb-3">
            <label class="form-label">Şifre</label>
            <input type="password" name="password" class="form-control" required />
          </div>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="is_admin" name="is_admin" <?= isset($_POST['is_admin']) ? 'checked' : '' ?> />
            <label class="form-check-label" for="is_admin">Admin Yetkisi</label>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-success" type="submit">Oluştur</button>
            <a class="btn btn-secondary" href="admin.php">Geri Dön</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/theme.js"></script>
</body>
</html>