<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lang.php';
setLangFromRequest();
requireAuth();

$user_id = (int)$_SESSION["user_id"];

// Kullanıcı bilgileri
$stmtUser = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();

// Avatar yardımcıları
$avatarDir = __DIR__ . '/assets/avatars';
if (!is_dir($avatarDir)) { @mkdir($avatarDir, 0775, true); }
function currentAvatar(int $uid, string $dir): array {
    $exts = ['png','jpg','jpeg','gif'];
    foreach ($exts as $ext) {
        $p = "$dir/user_{$uid}.{$ext}";
        if (file_exists($p)) {
            return ['path' => $p, 'url' => "assets/avatars/user_{$uid}.{$ext}"];
        }
    }
    return ['path' => "$dir/user_{$uid}.png", 'url' => "assets/avatars/user_{$uid}.png"]; // varsayılan hedef
}
$avatar = currentAvatar($user_id, $avatarDir);

if (isset($_POST['__avatar_upload']) && isset($_FILES['avatar']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
    $info = @getimagesize($_FILES['avatar']['tmp_name']);
    if ($info && in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF], true)) {
        $ext = image_type_to_extension($info[2], false);
        $targetPng = $avatarDir . "/user_{$user_id}.png";
        $targetRaw = $avatarDir . "/user_{$user_id}.{$ext}";
        if (function_exists('imagecreatefromstring') && function_exists('imagecreatetruecolor') && function_exists('imagecopyresampled') && function_exists('imagepng')) {
            $img = @imagecreatefromstring(file_get_contents($_FILES['avatar']['tmp_name']));
            if ($img) {
                $w = imagesx($img); $h = imagesy($img); $size = min($w,$h);
                $srcX = (int)(($w - $size)/2); $srcY = (int)(($h - $size)/2);
                $dst = imagecreatetruecolor(256,256);
                imagecopyresampled($dst, $img, 0,0, $srcX,$srcY, 256,256, $size,$size);
                @imagepng($dst, $targetPng, 8);
                @imagedestroy($dst);
                @imagedestroy($img);
                foreach (['jpg','jpeg','gif'] as $e) { $old = $avatarDir . "/user_{$user_id}.{$e}"; if (file_exists($old)) @unlink($old); }
            } else {
                @move_uploaded_file($_FILES['avatar']['tmp_name'], $targetRaw);
                if ($ext !== 'png' && file_exists($targetPng)) @unlink($targetPng);
            }
        } else {
            @move_uploaded_file($_FILES['avatar']['tmp_name'], $targetRaw);
            if ($ext !== 'png' && file_exists($targetPng)) @unlink($targetPng);
        }
    }
    header('Location: panel.php');
    exit;
}

$infoSaved = false;
$error = "";

// Tüm profilleri getir
$stmtProfiles = $pdo->prepare("SELECT id, hasta_adi, hasta_dogum FROM patient_info WHERE user_id = ? ORDER BY id DESC");
$stmtProfiles->execute([$user_id]);
$profiles = $stmtProfiles->fetchAll();
$currentPid = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;
if ($currentPid === 0 && !empty($profiles)) { $currentPid = (int)$profiles[0]['id']; }
$validPid = in_array($currentPid, array_map(fn($p)=> (int)$p['id'], $profiles), true);
if (!$validPid) { $currentPid = 0; }

// CRUD işlemleri
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['__avatar_upload'])) {
    if (isset($_POST['__create_profile'])) {
        $pdo->prepare("INSERT INTO patient_info (user_id, hasta_adi, hasta_dogum, hasta_kan, hasta_ilac, hasta_notlar) VALUES (?, '', NULL, '', '', '')")->execute([$user_id]);
        $newId = (int)$pdo->lastInsertId();
        header('Location: panel.php?pid=' . $newId);
        exit;
    }
    if (isset($_POST['__delete_profile']) && $validPid) {
        $del = $pdo->prepare("DELETE FROM patient_info WHERE id = ? AND user_id = ?");
        $del->execute([$currentPid, $user_id]);
        header('Location: panel.php');
        exit;
    }

    // Kaydet
    $hasta_adi = trim($_POST["hasta_adi"] ?? "");
    $hasta_dogum = trim($_POST["hasta_dogum"] ?? "");
    $hasta_kan = trim($_POST["hasta_kan"] ?? "");
    $hasta_ilac = trim($_POST["hasta_ilac"] ?? "");

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

        if ($validPid) {
            $stmtUpdate = $pdo->prepare("UPDATE patient_info SET hasta_adi=?, hasta_dogum=?, hasta_kan=?, hasta_ilac=?, hasta_notlar=? WHERE id=? AND user_id=?");
            $stmtUpdate->execute([$hasta_adi, $hasta_dogum, $hasta_kan, $hasta_ilac, $hasta_notlar, $currentPid, $user_id]);
        } else {
            $stmtInsert = $pdo->prepare("INSERT INTO patient_info (user_id, hasta_adi, hasta_dogum, hasta_kan, hasta_ilac, hasta_notlar) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtInsert->execute([$user_id, $hasta_adi, $hasta_dogum, $hasta_kan, $hasta_ilac, $hasta_notlar]);
            $currentPid = (int)$pdo->lastInsertId();
        }
        $infoSaved = true;
        header('Location: panel.php?pid=' . (int)$currentPid);
        exit;
    }
}

// Mevcut profil ve meta
define('NL', "\n");
function parseNotes(?string $notes): array {
    $meta = [
        'emergency_name' => '', 'emergency_phone' => '', 'address' => '',
        'doctor_name' => '', 'doctor_phone' => '', 'allergies' => '', 'conditions' => '', 'extra' => ''
    ];
    if (!$notes) return $meta;
    $lines = preg_split("/\r?\n/", $notes);
    $rest = [];
    foreach ($lines as $line) {
        $t = trim($line);
        if ($t === '') continue;
        if (stripos($t, 'Acil İletişim:') === 0) {
            $val = trim(substr($t, strlen('Acil İletişim:')));
            if (preg_match('/^(.*)\((.*)\)$/u', $val, $m)) { $meta['emergency_name']=trim($m[1]); $meta['emergency_phone']=trim($m[2]); } else { $meta['emergency_name']=$val; }
        } elseif (stripos($t, 'Adres:') === 0) { $meta['address'] = trim(substr($t, strlen('Adres:')));
        } elseif (stripos($t, 'Doktor:') === 0) {
            $val = trim(substr($t, strlen('Doktor:')));
            if (preg_match('/^(.*)\((.*)\)$/u', $val, $m)) { $meta['doctor_name']=trim($m[1]); $meta['doctor_phone']=trim($m[2]); } else { $meta['doctor_name']=$val; }
        } elseif (stripos($t, 'Alerjiler:') === 0) { $meta['allergies'] = trim(substr($t, strlen('Alerjiler:')));
        } elseif (stripos($t, 'Kronik Hastalıklar:') === 0) { $meta['conditions'] = trim(substr($t, strlen('Kronik Hastalıklar:')));
        } elseif (stripos($t, 'Ek Notlar:') === 0) { $meta['extra'] = trim(substr($t, strlen('Ek Notlar:')));
        } else { $rest[] = $t; }
    }
    if ($meta['extra'] === '' && !empty($rest)) { $meta['extra'] = implode("\n", $rest); }
    return $meta;
}

$currentPatient = null;
if ($currentPid > 0) {
    $stmtInfo = $pdo->prepare("SELECT * FROM patient_info WHERE id = ? AND user_id = ?");
    $stmtInfo->execute([$currentPid, $user_id]);
    $currentPatient = $stmtInfo->fetch();
}
$patient = $currentPatient ?: null;
$meta = parseNotes($patient['hasta_notlar'] ?? null);

// Public profil linki ve QR (pid tabanlı)
if ($patient) {
    $publicUrl = sprintf('%s://%s%s/p.php?pid=%d&code=%s',
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http',
        $_SERVER['HTTP_HOST'] ?? 'localhost',
        rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/.'),
        (int)$patient['id'],
        makePublicCodeForPatient((int)$patient['id'])
    );
    $qrApi = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($publicUrl);
}

// Avatar için baş harfler
$initials = '';
if (!empty($user['name'])) {
  $parts = preg_split('/\s+/', trim($user['name']));
  $initials = strtoupper(mb_substr($parts[0] ?? '', 0, 1) . mb_substr(end($parts) ?: '', 0, 1));
}
$hasAvatar = file_exists($avatar['path']);
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars(getLang()) ?>" data-bs-theme="dark">
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
    <div class="ms-auto small d-flex align-items-center gap-3">
      <div class="dropdown">
        <a class="d-flex align-items-center gap-2 text-decoration-none text-white" href="#" id="avatarMenu" data-bs-toggle="dropdown" aria-expanded="false">
          <div class="rounded-circle bg-info d-inline-flex justify-content-center align-items-center overflow-hidden" style="width:36px;height:36px;">
            <?php if ($hasAvatar): ?>
              <img src="<?= htmlspecialchars($avatar['url']) ?>" alt="avatar" style="width:36px;height:36px;object-fit:cover;" />
            <?php else: ?>
              <span class="fw-bold text-dark"><?= htmlspecialchars($initials ?: 'U') ?></span>
            <?php endif; ?>
          </div>
          <div class="d-none d-md-block text-start">
            <div class="fw-semibold small mb-0"><?= htmlspecialchars($user['name'] ?? '') ?></div>
            <div class="text-white-50 small"><?= htmlspecialchars($user['email'] ?? '') ?></div>
          </div>
        </a>
        <div class="dropdown-menu dropdown-menu-end p-3" aria-labelledby="avatarMenu" style="min-width:260px;">
          <form method="post" action="panel.php" enctype="multipart/form-data">
            <input type="hidden" name="__avatar_upload" value="1" />
            <div class="mb-2 small text-muted">Avatar yükle (kare görsel önerilir):</div>
            <input class="form-control form-control-sm mb-2" type="file" name="avatar" accept="image/*" />
            <button class="btn btn-sm btn-primary w-100">Yükle</button>
          </form>
        </div>
      </div>
      <div class="dropdown">
        <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"><?= t('language') ?></button>
        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
          <li><a class="dropdown-item" href="?lang=tr"><?= t('turkish') ?></a></li>
          <li><a class="dropdown-item" href="?lang=en"><?= t('english') ?></a></li>
        </ul>
      </div>
      <?php if (isAdmin()): ?>
        <a class="link-light me-1" href="admin.php"><?= t('admin') ?></a>
      <?php endif; ?>
      <a class="btn btn-outline-light btn-sm" href="logout.php"><?= t('logout') ?></a>
    </div>
  </nav>

  <main class="container py-5">
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="d-flex align-items-center mb-3">
          <h2 class="mb-0 fw-semibold">Hasta Bilgileri</h2>
          <span class="badge badge-soft ms-2">Kişisel</span>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($infoSaved): ?>
          <div class="alert alert-success">Bilgiler başarıyla kaydedildi.</div>
        <?php endif; ?>

        <div class="card bg-black border-0 shadow-sm mb-4">
          <div class="card-body">
            <form method="POST" action="panel.php?pid=<?= (int)$currentPid ?>" class="mb-1" enctype="multipart/form-data">
              <ul class="nav nav-tabs" id="infoTabs" role="tablist">
                <li class="nav-item" role="presentation">
                  <button class="nav-link active" id="genel-tab" data-bs-toggle="tab" data-bs-target="#genel" type="button" role="tab">Genel</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="saglik-tab" data-bs-toggle="tab" data-bs-target="#saglik" type="button" role="tab">Sağlık</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="iletisim-tab" data-bs-toggle="tab" data-bs-target="#iletisim" type="button" role="tab">İletişim</button>
                </li>
              </ul>
              <div class="tab-content pt-3">
                <div class="tab-pane fade show active" id="genel" role="tabpanel">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label for="hasta_adi" class="form-label fw-semibold">Hasta Adı Soyadı</label>
                      <input type="text" id="hasta_adi" name="hasta_adi" class="form-control" required value="<?= htmlspecialchars($patient["hasta_adi"] ?? "") ?>" />
                    </div>
                    <div class="col-md-3">
                      <label for="hasta_dogum" class="form-label fw-semibold">Doğum Tarihi</label>
                      <input type="date" id="hasta_dogum" name="hasta_dogum" class="form-control" required value="<?= htmlspecialchars($patient["hasta_dogum"] ?? "") ?>" />
                    </div>
                    <div class="col-md-3">
                      <label for="hasta_kan" class="form-label fw-semibold">Kan Grubu</label>
                      <input type="text" id="hasta_kan" name="hasta_kan" class="form-control" placeholder="Örn: 0 Rh+" value="<?= htmlspecialchars($patient["hasta_kan"] ?? "") ?>" />
                    </div>
                    <div class="col-12">
                      <label class="form-label fw-semibold">Adres</label>
                      <textarea name="address" class="form-control" rows="2" placeholder="İkamet adresi...">&nbsp;<?= htmlspecialchars($meta['address']) ?></textarea>
                    </div>
                  </div>
                </div>
                <div class="tab-pane fade" id="saglik" role="tabpanel">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Alerjiler</label>
                      <input type="text" name="allergies" class="form-control" placeholder="Virgülle ayırın: Penisilin, Yer fıstığı" value="<?= htmlspecialchars($meta['allergies']) ?>" />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Kronik Hastalıklar</label>
                      <input type="text" name="conditions" class="form-control" placeholder="Virgülle ayırın: Diyabet, Hipertansiyon" value="<?= htmlspecialchars($meta['conditions']) ?>" />
                    </div>
                    <div class="col-12">
                      <label for="hasta_ilac" class="form-label fw-semibold">İlaçlar</label>
                      <textarea id="hasta_ilac" name="hasta_ilac" class="form-control" rows="2" placeholder="Düzenli kullanılan ilaçlar, dozlar vb."><?= htmlspecialchars($patient["hasta_ilac"] ?? "") ?></textarea>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Doktor Adı</label>
                      <input type="text" name="doctor_name" class="form-control" value="<?= htmlspecialchars($meta['doctor_name']) ?>" />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Doktor Telefonu</label>
                      <input type="text" name="doctor_phone" class="form-control" placeholder="0xxx..." value="<?= htmlspecialchars($meta['doctor_phone']) ?>" />
                    </div>
                    <div class="col-12">
                      <label for="extra_notes" class="form-label fw-semibold">Ek Notlar</label>
                      <textarea id="extra_notes" name="extra_notes" class="form-control" rows="3" placeholder="Diğer önemli bilgiler (alerji taşı, implant, cihaz vb.)"><?= htmlspecialchars($meta['extra']) ?></textarea>
                    </div>
                  </div>
                </div>
                <div class="tab-pane fade" id="iletisim" role="tabpanel">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Acil İletişim Adı</label>
                      <input type="text" name="emergency_name" class="form-control" value="<?= htmlspecialchars($meta['emergency_name']) ?>" />
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Acil İletişim Telefonu</label>
                      <input type="text" name="emergency_phone" class="form-control" placeholder="05xx..." value="<?= htmlspecialchars($meta['emergency_phone']) ?>" />
                    </div>
                    <div class="col-12">
                      <div class="small text-white-50">Acil durumda bu kişiye ulaşılması önerilir.</div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-success">Kaydet</button>
                <?php if ($patient): ?><a class="btn btn-outline-light" href="<?= htmlspecialchars($publicUrl) ?>" target="_blank" rel="noopener">Acil Profili Gör</a><?php endif; ?>
              </div>
            </form>
          </div>
        </div>

        <?php if ($patient): ?>
          <div class="card bg-black border-0 shadow-sm">
            <div class="card-header border-0 d-flex align-items-center justify-content-between">
              <h5 class="mb-0 fw-semibold">Özet</h5>
              <a class="btn btn-sm btn-outline-light" href="<?= htmlspecialchars($publicUrl) ?>" target="_blank">Acil Kartı (QR)</a>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6"><strong>Adı Soyadı:</strong> <span class="text-light"><?= htmlspecialchars($patient["hasta_adi"] ?? '') ?></span></div>
                <div class="col-md-3"><strong>Doğum:</strong> <span class="text-light"><?= htmlspecialchars($patient["hasta_dogum"] ?? '') ?></span></div>
                <div class="col-md-3"><strong>Kan:</strong> <span class="text-light"><?= htmlspecialchars($patient["hasta_kan"] ?? '') ?></span></div>
                <?php if ($meta['emergency_name'] || $meta['emergency_phone']): ?>
                  <div class="col-12"><strong>Acil İletişim:</strong> <span class="text-light"><?= htmlspecialchars(trim(($meta['emergency_name']??'') . ' ' . ($meta['emergency_phone']??''))) ?></span></div>
                <?php endif; ?>
                <?php if ($meta['address']): ?>
                  <div class="col-12"><strong>Adres:</strong> <span class="text-light"><?= nl2br(htmlspecialchars($meta['address'])) ?></span></div>
                <?php endif; ?>
                <?php if ($meta['doctor_name'] || $meta['doctor_phone']): ?>
                  <div class="col-12"><strong>Doktor:</strong> <span class="text-light"><?= htmlspecialchars(trim(($meta['doctor_name']??'') . ' ' . ($meta['doctor_phone']??''))) ?></span></div>
                <?php endif; ?>
                <?php if ($meta['allergies']): ?>
                  <div class="col-12"><strong>Alerjiler:</strong> <span class="text-light"><?= htmlspecialchars($meta['allergies']) ?></span></div>
                <?php endif; ?>
                <?php if ($meta['conditions']): ?>
                  <div class="col-12"><strong>Kronik Hastalıklar:</strong> <span class="text-light"><?= htmlspecialchars($meta['conditions']) ?></span></div>
                <?php endif; ?>
                <?php if (!empty($patient['hasta_ilac'])): ?>
                  <div class="col-12"><strong>İlaçlar:</strong><br/><span class="text-light"><?= nl2br(htmlspecialchars($patient['hasta_ilac'])) ?></span></div>
                <?php endif; ?>
                <?php if ($meta['extra']): ?>
                  <div class="col-12"><strong>Ek Notlar:</strong><br/><span class="text-light"><?= nl2br(htmlspecialchars($meta['extra'])) ?></span></div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
      <div class="col-lg-4">
        <div class="card card-glass text-white mb-3">
          <div class="card-body">
            <h5 class="card-title">Profillerim</h5>
            <div class="list-group list-group-flush">
              <?php foreach ($profiles as $pr): ?>
                <a class="list-group-item list-group-item-action <?= (int)$pr['id']===$currentPid?'active':'' ?>" href="panel.php?pid=<?= (int)$pr['id'] ?>">
                  <div class="d-flex justify-content-between align-items-center">
                    <span><?= htmlspecialchars($pr['hasta_adi'] ?: ('Profil #' . (int)$pr['id'])) ?></span>
                    <small class="text-white-50 ms-2"><?= htmlspecialchars($pr['hasta_dogum'] ?? '') ?></small>
                  </div>
                </a>
              <?php endforeach; ?>
              <?php if (empty($profiles)): ?>
                <div class="list-group-item text-white-50">Henüz profil yok</div>
              <?php endif; ?>
            </div>
            <form method="post" class="mt-2 d-flex gap-2">
              <button class="btn btn-success btn-sm" name="__create_profile" value="1">Yeni Profil</button>
              <?php if ($currentPid): ?><button class="btn btn-outline-danger btn-sm" name="__delete_profile" value="1" onclick="return confirm('Bu profili silmek istiyor musunuz?')">Sil</button><?php endif; ?>
            </form>
          </div>
        </div>
        <?php if ($patient): ?>
        <div class="card card-glass text-white">
          <div class="card-body">
            <h5 class="card-title">NFC/QR Acil Profil</h5>
            <p class="small">Bu QR kodu NFC bilekliği/etiketi ile eşleyin.</p>
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
        <?php endif; ?>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/validate.js"></script>
</body>
</html>
