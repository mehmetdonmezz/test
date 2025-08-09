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

function parseNotes(?string $notes): array {
    $meta = [
        'emergency_name' => '',
        'emergency_phone' => '',
        'address' => '',
        'doctor_name' => '',
        'doctor_phone' => '',
        'allergies' => '',
        'conditions' => '',
        'extra' => ''
    ];
    if (!$notes) return $meta;
    $lines = preg_split("/\r?\n/", $notes);
    foreach ($lines as $line) {
        $t = trim($line);
        if ($t === '') continue;
        if (stripos($t, 'Acil İletişim:') === 0) {
            $val = trim(substr($t, strlen('Acil İletişim:')));
            if (preg_match('/^(.*)\((.*)\)$/u', $val, $m)) {
                $meta['emergency_name'] = trim($m[1]);
                $meta['emergency_phone'] = trim($m[2]);
            } else {
                $meta['emergency_name'] = $val;
            }
        } elseif (stripos($t, 'Adres:') === 0) {
            $meta['address'] = trim(substr($t, strlen('Adres:')));
        } elseif (stripos($t, 'Doktor:') === 0) {
            $val = trim(substr($t, strlen('Doktor:')));
            if (preg_match('/^(.*)\((.*)\)$/u', $val, $m)) {
                $meta['doctor_name'] = trim($m[1]);
                $meta['doctor_phone'] = trim($m[2]);
            } else {
                $meta['doctor_name'] = $val;
            }
        } elseif (stripos($t, 'Alerjiler:') === 0) {
            $meta['allergies'] = trim(substr($t, strlen('Alerjiler:')));
        } elseif (stripos($t, 'Kronik Hastalıklar:') === 0) {
            $meta['conditions'] = trim(substr($t, strlen('Kronik Hastalıklar:')));
        } elseif (stripos($t, 'Ek Notlar:') === 0) {
            $meta['extra'] = trim(substr($t, strlen('Ek Notlar:')));
        }
    }
    return $meta;
}

// Hasta bilgilerini getir
$stmt = $pdo->prepare('SELECT p.hasta_adi, p.hasta_dogum, p.hasta_kan, p.hasta_ilac, p.hasta_notlar, u.name as user_name, u.email as user_email FROM patient_info p JOIN users u ON u.id = p.user_id WHERE p.user_id = ?');
$stmt->execute([$uid]);
$patient = $stmt->fetch();
$meta = parseNotes($patient['hasta_notlar'] ?? null);

$publicUrl = sprintf('%s://%s%s/p.php?uid=%d&code=%s',
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http',
    $_SERVER['HTTP_HOST'] ?? 'localhost',
    rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/.'),
    $uid,
    $code
);
$qrApi = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . urlencode($publicUrl);
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
        <div class="row g-3 mb-4 align-items-center">
          <div class="col-auto">
            <img src="<?= $qrApi ?>" alt="QR" class="rounded border" />
          </div>
          <div class="col">
            <div class="fw-bold"><?= htmlspecialchars($patient['hasta_adi']) ?></div>
            <div class="text-muted small">Doğum: <?= htmlspecialchars($patient['hasta_dogum']) ?> | Kan: <?= htmlspecialchars($patient['hasta_kan']) ?></div>
          </div>
          <div class="col-auto">
            <a class="btn btn-outline-secondary btn-sm" target="_blank" href="p_print.php?uid=<?= $uid ?>&code=<?= urlencode($code) ?>">PDF olarak Yazdır</a>
          </div>
        </div>

        <div class="card shadow-sm mb-3">
          <div class="card-body">
            <h5 class="card-title">Acil İletişim</h5>
            <?php if ($meta['emergency_name'] || $meta['emergency_phone']): ?>
              <p class="mb-1"><strong>Kişi:</strong> <?= htmlspecialchars(trim($meta['emergency_name'])) ?></p>
              <?php if ($meta['emergency_phone']): ?><p class="mb-1"><strong>Telefon:</strong> <a href="tel:<?= htmlspecialchars($meta['emergency_phone']) ?>"><?= htmlspecialchars($meta['emergency_phone']) ?></a></p><?php endif; ?>
            <?php else: ?>
              <p class="mb-1">Kayıt sahibi: <strong><?= htmlspecialchars($patient['user_name']) ?></strong></p>
              <p class="mb-0">E-posta: <a href="mailto:<?= htmlspecialchars($patient['user_email']) ?>"><?= htmlspecialchars($patient['user_email']) ?></a></p>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($meta['address']): ?>
        <div class="card shadow-sm mb-3">
          <div class="card-body">
            <h5 class="card-title">Adres</h5>
            <p class="mb-0"><?= nl2br(htmlspecialchars($meta['address'])) ?></p>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($meta['doctor_name'] || $meta['doctor_phone']): ?>
        <div class="card shadow-sm mb-3">
          <div class="card-body">
            <h5 class="card-title">Doktor Bilgisi</h5>
            <p class="mb-1"><strong>Doktor:</strong> <?= htmlspecialchars($meta['doctor_name']) ?></p>
            <?php if ($meta['doctor_phone']): ?><p class="mb-0"><strong>Telefon:</strong> <a href="tel:<?= htmlspecialchars($meta['doctor_phone']) ?>"><?= htmlspecialchars($meta['doctor_phone']) ?></a></p><?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($patient['hasta_ilac'])): ?>
        <div class="card shadow-sm mb-3">
          <div class="card-body">
            <h5 class="card-title">İlaçlar</h5>
            <p class="mb-0"><?= nl2br(htmlspecialchars($patient['hasta_ilac'])) ?></p>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($meta['allergies'] || $meta['conditions'] || $meta['extra']): ?>
        <div class="card shadow-sm mb-3">
          <div class="card-body">
            <h5 class="card-title">Diğer Bilgiler</h5>
            <?php if ($meta['allergies']): ?><p class="mb-1"><strong>Alerjiler:</strong> <?= htmlspecialchars($meta['allergies']) ?></p><?php endif; ?>
            <?php if ($meta['conditions']): ?><p class="mb-1"><strong>Kronik Hastalıklar:</strong> <?= htmlspecialchars($meta['conditions']) ?></p><?php endif; ?>
            <?php if ($meta['extra']): ?><p class="mb-0"><strong>Ek Notlar:</strong><br/><?= nl2br(htmlspecialchars($meta['extra'])) ?></p><?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      <?php endif; ?>

      <div class="text-center mt-4">
        <a href="index.php" class="btn btn-secondary">ARDİO Ana Sayfa</a>
      </div>
    </div>
  </div>
</body>
</html>