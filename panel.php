<?php
require_once __DIR__ . '/config.php';
requireAuth();

$user_id = (int)$_SESSION["user_id"];

// Kullanıcı bilgileri
$stmtUser = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();

$infoSaved = false;
$error = "";

// Yardımcı: notlardan meta verileri çöz
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
    $rest = [];
    foreach ($lines as $line) {
        $t = trim($line);
        if ($t === '') continue;
        if (stripos($t, 'Acil İletişim:') === 0) {
            $val = trim(substr($t, strlen('Acil İletişim:')));
            // "Ad (Telefon)" biçiminden parçala
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
        } else {
            $rest[] = $t;
        }
    }
    // Eğer Ek Notlar boşsa ve rest var ise, birleştir
    if ($meta['extra'] === '' && !empty($rest)) {
        $meta['extra'] = implode("\n", $rest);
    }
    return $meta;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $hasta_adi = trim($_POST["hasta_adi"] ?? "");
    $hasta_dogum = trim($_POST["hasta_dogum"] ?? "");
    $hasta_kan = trim($_POST["hasta_kan"] ?? "");
    $hasta_ilac = trim($_POST["hasta_ilac"] ?? "");

    // Gelişmiş alanlar
    $emergency_name = trim($_POST['emergency_name'] ?? '');
    $emergency_phone = trim($_POST['emergency_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $doctor_name = trim($_POST['doctor_name'] ?? '');
    $doctor_phone = trim($_POST['doctor_phone'] ?? '');
    $allergies = trim($_POST['allergies'] ?? '');
    $conditions = trim($_POST['conditions'] ?? '');
    $extra_notes = trim($_POST['extra_notes'] ?? '');

    if (!$hasta_adi || !$hasta_dogum) {
        $error = "Hasta adı ve doğum tarihi zorunludur.";
    } else {
        // Notları yapılandırılmış biçimde derle
        $notesLines = [];
        if ($emergency_name || $emergency_phone) {
            $val = $emergency_name;
            if ($emergency_phone) $val .= ' (' . $emergency_phone . ')';
            $notesLines[] = 'Acil İletişim: ' . $val;
        }
        if ($address) $notesLines[] = 'Adres: ' . $address;
        if ($doctor_name || $doctor_phone) {
            $val = $doctor_name;
            if ($doctor_phone) $val .= ' (' . $doctor_phone . ')';
            $notesLines[] = 'Doktor: ' . $val;
        }
        if ($allergies) $notesLines[] = 'Alerjiler: ' . $allergies;
        if ($conditions) $notesLines[] = 'Kronik Hastalıklar: ' . $conditions;
        if ($extra_notes) $notesLines[] = 'Ek Notlar: ' . $extra_notes;
        $hasta_notlar = implode("\n", $notesLines);

        // Var mı?
        $stmtCheck = $pdo->prepare("SELECT id FROM patient_info WHERE user_id = ?");
        $stmtCheck->execute([$user_id]);
        $exists = $stmtCheck->fetch();

        if ($exists) {
            $stmtUpdate = $pdo->prepare("UPDATE patient_info SET hasta_adi=?, hasta_dogum=?, hasta_kan=?, hasta_ilac=?, hasta_notlar=? WHERE user_id=?");
            $stmtUpdate->execute([$hasta_adi, $hasta_dogum, $hasta_kan, $hasta_ilac, $hasta_notlar, $user_id]);
        } else {
            $stmtInsert = $pdo->prepare("INSERT INTO patient_info (user_id, hasta_adi, hasta_dogum, hasta_kan, hasta_ilac, hasta_notlar) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtInsert->execute([$user_id, $hasta_adi, $hasta_dogum, $hasta_kan, $hasta_ilac, $hasta_notlar]);
        }
        $infoSaved = true;
    }
}

$stmtInfo = $pdo->prepare("SELECT hasta_adi, hasta_dogum, hasta_kan, hasta_ilac, hasta_notlar FROM patient_info WHERE user_id = ?");
$stmtInfo->execute([$user_id]);
$patient = $stmtInfo->fetch();
$meta = parseNotes($patient['hasta_notlar'] ?? null);

// Public profil linki ve QR
$code = makePublicCode($user_id);
$publicUrl = sprintf('%s://%s%s/p.php?uid=%d&code=%s',
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http',
    $_SERVER['HTTP_HOST'] ?? 'localhost',
    rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/.'),
    $user_id,
    $code
);
$qrApi = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($publicUrl);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <title>Kullanıcı Paneli - ARDİO</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="assets/styles.css" rel="stylesheet" />
</head>
<body class="bg-dark text-white">

  <nav class="navbar navbar-expand-lg navbar-dark bg-primary px-4">
    <a class="navbar-brand" href="index.php">ARDİO</a>
    <div class="ms-auto small">
      <span class="me-3">Hoşgeldin, <strong><?= htmlspecialchars($user["name"] ?? "") ?></strong></span>
      <?php if (isAdmin()): ?>
        <a class="link-light me-3" href="admin.php">Admin</a>
      <?php endif; ?>
      <a class="btn btn-outline-light btn-sm" href="logout.php">Çıkış Yap</a>
    </div>
  </nav>

  <main class="container py-5">
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="d-flex align-items-center mb-3">
          <h2 class="mb-0">Hasta Bilgileri</h2>
          <span class="badge badge-soft ms-2">Kişisel</span>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($infoSaved): ?>
          <div class="alert alert-success">Bilgiler başarıyla kaydedildi.</div>
        <?php endif; ?>

        <div class="card bg-black border-0 shadow-sm mb-4">
          <div class="card-body">
            <form method="POST" action="panel.php" class="mb-1">
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="hasta_adi" class="form-label">Hasta Adı Soyadı</label>
                  <input type="text" id="hasta_adi" name="hasta_adi" class="form-control" required value="<?= htmlspecialchars($patient["hasta_adi"] ?? "") ?>" />
                </div>
                <div class="col-md-3">
                  <label for="hasta_dogum" class="form-label">Doğum Tarihi</label>
                  <input type="date" id="hasta_dogum" name="hasta_dogum" class="form-control" required value="<?= htmlspecialchars($patient["hasta_dogum"] ?? "") ?>" />
                </div>
                <div class="col-md-3">
                  <label for="hasta_kan" class="form-label">Kan Grubu</label>
                  <input type="text" id="hasta_kan" name="hasta_kan" class="form-control" placeholder="Örn: 0 Rh+" value="<?= htmlspecialchars($patient["hasta_kan"] ?? "") ?>" />
                </div>

                <div class="col-12">
                  <hr class="border-secondary" />
                  <h5>İleri Seviye Bilgiler</h5>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Acil İletişim Adı</label>
                  <input type="text" name="emergency_name" class="form-control" value="<?= htmlspecialchars($meta['emergency_name']) ?>" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Acil İletişim Telefonu</label>
                  <input type="text" name="emergency_phone" class="form-control" placeholder="05xx..." value="<?= htmlspecialchars($meta['emergency_phone']) ?>" />
                </div>

                <div class="col-12">
                  <label class="form-label">Adres</label>
                  <textarea name="address" class="form-control" rows="2" placeholder="İkamet adresi..."><?= htmlspecialchars($meta['address']) ?></textarea>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Doktor Adı</label>
                  <input type="text" name="doctor_name" class="form-control" value="<?= htmlspecialchars($meta['doctor_name']) ?>" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Doktor Telefonu</label>
                  <input type="text" name="doctor_phone" class="form-control" placeholder="0xxx..." value="<?= htmlspecialchars($meta['doctor_phone']) ?>" />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Alerjiler</label>
                  <input type="text" name="allergies" class="form-control" placeholder="Virgülle ayırın: Penisilin, Yer fıstığı" value="<?= htmlspecialchars($meta['allergies']) ?>" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Kronik Hastalıklar</label>
                  <input type="text" name="conditions" class="form-control" placeholder="Virgülle ayırın: Diyabet, Hipertansiyon" value="<?= htmlspecialchars($meta['conditions']) ?>" />
                </div>

                <div class="col-12">
                  <label for="hasta_ilac" class="form-label">İlaçlar</label>
                  <textarea id="hasta_ilac" name="hasta_ilac" class="form-control" rows="2" placeholder="Düzenli kullanılan ilaçlar, dozlar vb."><?= htmlspecialchars($patient["hasta_ilac"] ?? "") ?></textarea>
                </div>

                <div class="col-12">
                  <label for="extra_notes" class="form-label">Ek Notlar</label>
                  <textarea id="extra_notes" name="extra_notes" class="form-control" rows="3" placeholder="Diğer önemli bilgiler (alerji taşı, implant, cihaz vb.)"><?= htmlspecialchars($meta['extra']) ?></textarea>
                </div>
              </div>
              <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-success">Kaydet</button>
                <a class="btn btn-outline-light" href="<?= htmlspecialchars($publicUrl) ?>" target="_blank" rel="noopener">Acil Profili Gör</a>
              </div>
            </form>
          </div>
        </div>

        <?php if ($patient): ?>
          <div class="card bg-black border-0 shadow-sm">
            <div class="card-header border-0 d-flex align-items-center justify-content-between">
              <h5 class="mb-0">Özet</h5>
              <a class="btn btn-sm btn-outline-light" href="<?= htmlspecialchars($publicUrl) ?>" target="_blank">Acil Kartı (QR)</a>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6"><strong>Adı Soyadı:</strong> <?= htmlspecialchars($patient["hasta_adi"]) ?></div>
                <div class="col-md-3"><strong>Doğum:</strong> <?= htmlspecialchars($patient["hasta_dogum"]) ?></div>
                <div class="col-md-3"><strong>Kan:</strong> <?= htmlspecialchars($patient["hasta_kan"]) ?></div>
                <?php if ($meta['emergency_name'] || $meta['emergency_phone']): ?>
                  <div class="col-12"><strong>Acil İletişim:</strong> <?= htmlspecialchars(trim($meta['emergency_name'].' '.$meta['emergency_phone'])) ?></div>
                <?php endif; ?>
                <?php if ($meta['address']): ?>
                  <div class="col-12"><strong>Adres:</strong> <?= nl2br(htmlspecialchars($meta['address'])) ?></div>
                <?php endif; ?>
                <?php if ($meta['doctor_name'] || $meta['doctor_phone']): ?>
                  <div class="col-12"><strong>Doktor:</strong> <?= htmlspecialchars(trim($meta['doctor_name'].' '.$meta['doctor_phone'])) ?></div>
                <?php endif; ?>
                <?php if ($meta['allergies']): ?>
                  <div class="col-12"><strong>Alerjiler:</strong> <?= htmlspecialchars($meta['allergies']) ?></div>
                <?php endif; ?>
                <?php if ($meta['conditions']): ?>
                  <div class="col-12"><strong>Kronik Hastalıklar:</strong> <?= htmlspecialchars($meta['conditions']) ?></div>
                <?php endif; ?>
                <?php if (!empty($patient['hasta_ilac'])): ?>
                  <div class="col-12"><strong>İlaçlar:</strong><br/><?= nl2br(htmlspecialchars($patient['hasta_ilac'])) ?></div>
                <?php endif; ?>
                <?php if ($meta['extra']): ?>
                  <div class="col-12"><strong>Ek Notlar:</strong><br/><?= nl2br(htmlspecialchars($meta['extra'])) ?></div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
      <div class="col-lg-4">
        <div class="card card-glass text-white">
          <div class="card-body">
            <h5 class="card-title">NFC/QR Acil Profil</h5>
            <p class="small">Bu QR kodu NFC bilekliği/etiketi ile eşleyin. Kod, hastanın acil profil sayfasına yönlendirir.</p>
            <div class="text-center my-3">
              <img src="<?= $qrApi ?>" alt="QR" class="img-fluid rounded border border-1 border-light p-1 bg-white" />
            </div>
            <div class="mb-2">
              <label class="form-label small">Bağlantı</label>
              <input type="text" class="form-control form-control-sm" readonly value="<?= htmlspecialchars($publicUrl) ?>" />
            </div>
            <a class="btn btn-primary-gradient btn-sm w-100" href="<?= htmlspecialchars($publicUrl) ?>" target="_blank" rel="noopener">Acil Profili Aç</a>
          </div>
        </div>
      </div>
    </div>
  </main>

</body>
</html>
