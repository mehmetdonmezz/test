<?php
require_once __DIR__ . '/config.php';

// Beklenen parametreler: uid ve code (HMAC)
$uid = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
$code = $_GET['code'] ?? '';

if ($uid <= 0 || !$code || !verifyPublicCode($uid, $code)) {
    http_response_code(400);
    echo 'Geçersiz bağlantı.';
    exit;
}

// Hasta bilgilerini getir
$stmt = $pdo->prepare('SELECT p.hasta_adi, p.hasta_dogum, p.hasta_kan, p.hasta_ilac, p.hasta_notlar, u.name as user_name, u.email as user_email FROM patient_info p JOIN users u ON u.id = p.user_id WHERE p.user_id = ?');
$stmt->execute([$uid]);
$patient = $stmt->fetch();

?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Acil Profil - ARDİO</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="mx-auto" style="max-width:720px;">
      <div class="text-center mb-4">
        <h1 class="h3 text-danger">ACİL PROFİL</h1>
        <p class="text-muted">Bu sayfa NFC/QR ile görüntülenmiştir.</p>
      </div>

      <?php if (!$patient): ?>
        <div class="alert alert-warning">Kayıt bulunamadı.</div>
      <?php else: ?>
        <div class="card shadow-sm mb-4">
          <div class="card-body">
            <h3 class="h4 mb-3">Hasta Bilgileri</h3>
            <div class="row mb-2">
              <div class="col-5 fw-bold">Ad Soyad</div>
              <div class="col-7"><?= htmlspecialchars($patient['hasta_adi']) ?></div>
            </div>
            <div class="row mb-2">
              <div class="col-5 fw-bold">Doğum Tarihi</div>
              <div class="col-7"><?= htmlspecialchars($patient['hasta_dogum']) ?></div>
            </div>
            <div class="row mb-2">
              <div class="col-5 fw-bold">Kan Grubu</div>
              <div class="col-7"><?= htmlspecialchars($patient['hasta_kan']) ?></div>
            </div>
            <div class="row mb-2">
              <div class="col-5 fw-bold">İlaçlar</div>
              <div class="col-7"><?= nl2br(htmlspecialchars($patient['hasta_ilac'])) ?></div>
            </div>
            <div class="row mb-2">
              <div class="col-5 fw-bold">Notlar</div>
              <div class="col-7"><?= nl2br(htmlspecialchars($patient['hasta_notlar'])) ?></div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-body">
            <h3 class="h4 mb-3">Acil İletişim</h3>
            <p class="mb-1">Sahibi: <strong><?= htmlspecialchars($patient['user_name']) ?></strong></p>
            <p class="mb-3">E-posta: <a href="mailto:<?= htmlspecialchars($patient['user_email']) ?>"><?= htmlspecialchars($patient['user_email']) ?></a></p>
            <div class="alert alert-info">Lütfen en yakın sağlık kuruluşuna ve/veya hasta yakınına haber veriniz.</div>
          </div>
        </div>
      <?php endif; ?>

      <div class="text-center mt-4">
        <a href="index.php" class="btn btn-secondary">ARDİO Ana Sayfa</a>
      </div>
    </div>
  </div>
</body>
</html>