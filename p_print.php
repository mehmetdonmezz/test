<?php
require_once __DIR__ . '/config.php';

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

$stmt = $pdo->prepare('SELECT p.hasta_adi, p.hasta_dogum, p.hasta_kan, p.hasta_ilac, p.hasta_notlar FROM patient_info p WHERE p.user_id = ?');
$stmt->execute([$uid]);
$patient = $stmt->fetch();
$meta = parseNotes($patient['hasta_notlar'] ?? null);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Acil Bilgi Kartı - Yazdır</title>
<style>
  body { font-family: Arial, sans-serif; padding: 24px; color: #111; }
  .title { text-align: center; margin-bottom: 16px; }
  .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 16px; }
  .grid .label { font-weight: bold; }
  .section { border: 1px solid #ddd; padding: 12px; margin-bottom: 12px; border-radius: 6px; }
  @media print { .actions { display: none; } }
</style>
</head>
<body>
  <div class="actions" style="text-align:right;">
    <button onclick="window.print()">Yazdır</button>
  </div>
  <h1 class="title">Acil Bilgi Kartı</h1>

  <div class="section">
    <div class="grid">
      <div class="label">Ad Soyad</div><div><?= htmlspecialchars($patient['hasta_adi'] ?? '-') ?></div>
      <div class="label">Doğum Tarihi</div><div><?= htmlspecialchars($patient['hasta_dogum'] ?? '-') ?></div>
      <div class="label">Kan Grubu</div><div><?= htmlspecialchars($patient['hasta_kan'] ?? '-') ?></div>
    </div>
  </div>

  <div class="section">
    <div class="label">Acil İletişim</div>
    <div><?= htmlspecialchars(trim($meta['emergency_name'])) ?> <?= htmlspecialchars($meta['emergency_phone']) ?></div>
  </div>

  <?php if ($meta['address']): ?>
  <div class="section">
    <div class="label">Adres</div>
    <div><?= nl2br(htmlspecialchars($meta['address'])) ?></div>
  </div>
  <?php endif; ?>

  <?php if ($meta['doctor_name'] || $meta['doctor_phone']): ?>
  <div class="section">
    <div class="label">Doktor</div>
    <div><?= htmlspecialchars($meta['doctor_name']) ?> <?= htmlspecialchars($meta['doctor_phone']) ?></div>
  </div>
  <?php endif; ?>

  <?php if (!empty($patient['hasta_ilac'])): ?>
  <div class="section">
    <div class="label">İlaçlar</div>
    <div><?= nl2br(htmlspecialchars($patient['hasta_ilac'])) ?></div>
  </div>
  <?php endif; ?>

  <?php if ($meta['allergies'] || $meta['conditions'] || $meta['extra']): ?>
  <div class="section">
    <div class="label">Diğer Bilgiler</div>
    <div>
      <?php if ($meta['allergies']): ?><div><strong>Alerjiler:</strong> <?= htmlspecialchars($meta['allergies']) ?></div><?php endif; ?>
      <?php if ($meta['conditions']): ?><div><strong>Kronik Hastalıklar:</strong> <?= htmlspecialchars($meta['conditions']) ?></div><?php endif; ?>
      <?php if ($meta['extra']): ?><div><strong>Ek Notlar:</strong> <?= nl2br(htmlspecialchars($meta['extra'])) ?></div><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</body>
</html>