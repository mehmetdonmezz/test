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
$qrApi = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($publicUrl);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Acil Profil - ARDİO</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    body { background: #f5f7fb; }
    .hero { background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%); color: #fff; }
    .badge-soft { background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.35); }
    .section-card { border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
    .label { color: #6c757d; font-weight: 600; }
  </style>
</head>
<body>
  <header class="hero py-4">
    <div class="container">
      <div class="d-flex flex-wrap align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle bg-light text-primary d-flex justify-content-center align-items-center" style="width:56px;height:56px;">
            <i class="bi bi-person-fill" style="font-size:28px;"></i>
          </div>
          <div>
            <h1 class="h3 mb-1">Acil Profil</h1>
            <div class="small opacity-75">Bu sayfa NFC/QR ile görüntülendi</div>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a class="btn btn-outline-light btn-sm" target="_blank" href="p_print.php?uid=<?= $uid ?>&code=<?= urlencode($code) ?>"><i class="bi bi-printer me-1"></i>PDF Yazdır</a>
          <a class="btn btn-light btn-sm" href="<?= htmlspecialchars($publicUrl) ?>" target="_blank"><i class="bi bi-link-45deg me-1"></i>Bağlantı</a>
        </div>
      </div>
    </div>
  </header>

  <main class="container py-4">
    <?php if (!$patient): ?>
      <div class="alert alert-warning">Kayıt bulunamadı.</div>
    <?php else: ?>
      <div class="row g-4">
        <div class="col-lg-8">
          <div class="card section-card mb-3">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                  <h2 class="h4 mb-1"><?= htmlspecialchars($patient['hasta_adi']) ?></h2>
                  <div class="d-flex flex-wrap gap-2 small">
                    <span class="badge bg-primary-subtle text-primary border"><i class="bi bi-calendar2-heart me-1"></i><?= htmlspecialchars($patient['hasta_dogum']) ?></span>
                    <span class="badge bg-danger-subtle text-danger border"><i class="bi bi-droplet-half me-1"></i><?= htmlspecialchars($patient['hasta_kan'] ?: '-') ?></span>
                  </div>
                </div>
                <div class="text-center">
                  <img src="<?= $qrApi ?>" alt="QR" class="rounded border bg-white p-1" />
                  <div class="small text-muted mt-1">QR ile doğrulandı</div>
                </div>
              </div>
            </div>
          </div>

          <div class="card section-card mb-3">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2"><i class="bi bi-telephone-outbound me-2 text-danger"></i><h3 class="h6 m-0">Acil İletişim</h3></div>
              <?php if ($meta['emergency_name'] || $meta['emergency_phone']): ?>
                <p class="mb-1"><span class="label">Kişi:</span> <?= htmlspecialchars(trim($meta['emergency_name'])) ?></p>
                <?php if ($meta['emergency_phone']): ?>
                  <p class="mb-2"><span class="label">Telefon:</span> <a class="text-decoration-none" href="tel:<?= htmlspecialchars($meta['emergency_phone']) ?>"><?= htmlspecialchars($meta['emergency_phone']) ?></a></p>
                  <a href="tel:<?= htmlspecialchars($meta['emergency_phone']) ?>" class="btn btn-danger btn-sm"><i class="bi bi-telephone-fill me-1"></i>Hemen Ara</a>
                <?php endif; ?>
              <?php else: ?>
                <p class="mb-1">Kayıt sahibi: <strong><?= htmlspecialchars($patient['user_name']) ?></strong></p>
                <p class="mb-0">E-posta: <a class="text-decoration-none" href="mailto:<?= htmlspecialchars($patient['user_email']) ?>"><?= htmlspecialchars($patient['user_email']) ?></a></p>
              <?php endif; ?>
            </div>
          </div>

          <?php if ($meta['address']): ?>
          <div class="card section-card mb-3">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2"><i class="bi bi-geo-alt me-2 text-primary"></i><h3 class="h6 m-0">Adres</h3></div>
              <p class="mb-2"><?= nl2br(htmlspecialchars($meta['address'])) ?></p>
              <a class="btn btn-outline-primary btn-sm" target="_blank" href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($meta['address']) ?>"><i class="bi bi-map me-1"></i>Haritada Aç</a>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($meta['doctor_name'] || $meta['doctor_phone']): ?>
          <div class="card section-card mb-3">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2"><i class="bi bi-hospital me-2 text-success"></i><h3 class="h6 m-0">Doktor Bilgisi</h3></div>
              <p class="mb-1"><span class="label">Doktor:</span> <?= htmlspecialchars($meta['doctor_name']) ?></p>
              <?php if ($meta['doctor_phone']): ?><p class="mb-0"><span class="label">Telefon:</span> <a class="text-decoration-none" href="tel:<?= htmlspecialchars($meta['doctor_phone']) ?>"><?= htmlspecialchars($meta['doctor_phone']) ?></a></p><?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php if (!empty($patient['hasta_ilac'])): ?>
          <div class="card section-card mb-3">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2"><i class="bi bi-capsule-pill me-2 text-warning"></i><h3 class="h6 m-0">İlaçlar</h3></div>
              <p class="mb-0"><?= nl2br(htmlspecialchars($patient['hasta_ilac'])) ?></p>
            </div>
          </div>
          <?php endif; ?>

          <?php if ($meta['allergies'] || $meta['conditions'] || $meta['extra']): ?>
          <div class="card section-card mb-3">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2"><i class="bi bi-info-circle me-2 text-secondary"></i><h3 class="h6 m-0">Diğer Bilgiler</h3></div>
              <?php if ($meta['allergies']): ?><p class="mb-1"><span class="label">Alerjiler:</span> <?= htmlspecialchars($meta['allergies']) ?></p><?php endif; ?>
              <?php if ($meta['conditions']): ?><p class="mb-1"><span class="label">Kronik Hastalıklar:</span> <?= htmlspecialchars($meta['conditions']) ?></p><?php endif; ?>
              <?php if ($meta['extra']): ?><p class="mb-0"><span class="label">Ek Notlar:</span><br/><?= nl2br(htmlspecialchars($meta['extra'])) ?></p><?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <div class="col-lg-4">
          <div class="card section-card">
            <div class="card-body text-center">
              <div class="mb-2"><span class="badge rounded-pill badge-soft">ACİL</span></div>
              <div class="small text-muted mb-2">Bu kişi yardıma ihtiyaç duyuyor olabilir.</div>
              <a href="#" class="btn btn-danger w-100 mb-2" onclick="window.print()"><i class="bi bi-printer me-1"></i>Yazdır</a>
              <a href="<?= htmlspecialchars($publicUrl) ?>" target="_blank" class="btn btn-outline-secondary w-100"><i class="bi bi-link-45deg me-1"></i>Bağlantıyı Aç</a>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="text-center mt-4">
      <a href="index.php" class="btn btn-secondary"><i class="bi bi-house-door me-1"></i>ARDİO Ana Sayfa</a>
    </div>
  </main>
</body>
</html>