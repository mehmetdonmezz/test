<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lang.php';
setLangFromRequest();

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
    $accept_terms = isset($_POST['accept_terms']);
    $accept_privacy = isset($_POST['accept_privacy']);

    if (!$name || !$email || !$password || !$password_confirm) {
        $error = t('fill_all_fields');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = t('invalid_email');
    } elseif ($password !== $password_confirm) {
        $error = t('passwords_mismatch');
    } elseif (!$accept_terms || !$accept_privacy) {
        $error = 'Lütfen kullanım şartları ve gizlilik sözleşmesini kabul edin.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = t('email_exists');
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, 0)");
                if ($insert->execute([$name, $email, $hashed_password])) {
                    $userId = (int)$pdo->lastInsertId();
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
                    if ($ip && strpos($ip, ',') !== false) { $ip = trim(explode(',', $ip)[0]); }
                    $insC = $pdo->prepare("INSERT INTO user_consents (user_id, terms_accepted_at, privacy_accepted_at, ip) VALUES (?, NOW(), NOW(), ?)");
                    $insC->execute([$userId, $ip]);
                    $success = t('register_success');
                } else {
                    $error = t('server_error');
                }
            }
        } catch (Throwable $e) {
            $error = t('server_error');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars(getLang()) ?>" data-bs-theme="dark">
<head>
  <meta charset="UTF-8" />
  <title><?= t('register_title') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="assets/styles.css" rel="stylesheet" />
</head>
<body class="auth-bg d-flex align-items-center">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <button class="btn btn-sm btn-outline-info" onclick="toggleTheme()" type="button"><span data-theme-label>Aydınlık</span> Moda Geç</button>
          <div class="dropdown">
            <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown"><?= t('language') ?></button>
            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
              <li><a class="dropdown-item" href="?lang=tr"><?= t('turkish') ?></a></li>
              <li><a class="dropdown-item" href="?lang=en"><?= t('english') ?></a></li>
            </ul>
          </div>
        </div>
        <div class="text-center mb-4">
          <a class="navbar-brand text-white fs-3 text-decoration-none" href="index.php">ARDİO</a>
          <div class="text-white-50 small">Gençliğin Teknolojisi</div>
        </div>
        <div class="card card-glass text-white shadow-lg">
          <div class="card-body p-4">
            <h3 class="mb-3 text-center"><?= t('sign_up') ?></h3>
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
                <label for="email" class="form-label"><?= t('email') ?></label>
                <input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
              </div>
              <div class="mb-3">
                <label for="password" class="form-label"><?= t('password') ?></label>
                <input type="password" id="password" name="password" class="form-control" required />
              </div>
              <div class="mb-3">
                <label for="password_confirm" class="form-label"><?= t('password_confirm') ?></label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control" required />
              </div>
              <div class="form-check text-white-50 small mb-2">
                <input class="form-check-input" type="checkbox" id="accept_terms" name="accept_terms" required />
                <label class="form-check-label" for="accept_terms">Kullanım Şartları'nı okudum ve kabul ediyorum (<a href="terms.php" class="link-info" target="_blank">oku</a>).</label>
              </div>
              <div class="form-check text-white-50 small mb-3">
                <input class="form-check-input" type="checkbox" id="accept_privacy" name="accept_privacy" required />
                <label class="form-check-label" for="accept_privacy">Gizlilik Sözleşmesi'ni okudum ve kabul ediyorum (<a href="privacy.php" class="link-info" target="_blank">oku</a>).</label>
              </div>
              <button type="submit" class="btn btn-primary-gradient w-100 py-2"><?= t('sign_up') ?></button>
            </form>
            <p class="mt-3 text-center text-white-50">
              <?= t('have_account') ?> <a href="login.php" class="text-info fw-semibold text-decoration-none"><?= t('sign_in') ?></a>
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
