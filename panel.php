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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $hasta_adi = trim($_POST["hasta_adi"] ?? "");
    $hasta_dogum = trim($_POST["hasta_dogum"] ?? "");
    $hasta_kan = trim($_POST["hasta_kan"] ?? "");
    $hasta_ilac = trim($_POST["hasta_ilac"] ?? "");
    $hasta_notlar = trim($_POST["hasta_notlar"] ?? "");

    if (!$hasta_adi || !$hasta_dogum) {
        $error = "Hasta adı ve doğum tarihi zorunludur.";
    } else {
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
                <div class="col-md-6">
                  <label for="hasta_dogum" class="form-label">Doğum Tarihi</label>
                  <input type="date" id="hasta_dogum" name="hasta_dogum" class="form-control" required value="<?= htmlspecialchars($patient["hasta_dogum"] ?? "") ?>" />
                </div>
                <div class="col-md-4">
                  <label for="hasta_kan" class="form-label">Kan Grubu</label>
                  <input type="text" id="hasta_kan" name="hasta_kan" class="form-control" value="<?= htmlspecialchars($patient["hasta_kan"] ?? "") ?>" />
                </div>
                <div class="col-md-8">
                  <label for="hasta_ilac" class="form-label">İlaçlar</label>
                  <textarea id="hasta_ilac" name="hasta_ilac" class="form-control" rows="2"><?= htmlspecialchars($patient["hasta_ilac"] ?? "") ?></textarea>
                </div>
                <div class="col-12">
                  <label for="hasta_notlar" class="form-label">Notlar</label>
                  <textarea id="hasta_notlar" name="hasta_notlar" class="form-control" rows="3"><?= htmlspecialchars($patient["hasta_notlar"] ?? "") ?></textarea>
                </div>
              </div>
              <div class="mt-3">
                <button type="submit" class="btn btn-success">Kaydet</button>
              </div>
            </form>
          </div>
        </div>

        <?php if ($patient): ?>
          <div class="card bg-black border-0 shadow-sm">
            <div class="card-header border-0">
              <h5 class="mb-0">Özet</h5>
            </div>
            <div class="card-body">
              <ul class="list-group list-group-flush">
                <li class="list-group-item bg-black text-white"><strong>Adı Soyadı:</strong> <?= htmlspecialchars($patient["hasta_adi"]) ?></li>
                <li class="list-group-item bg-black text-white"><strong>Doğum Tarihi:</strong> <?= htmlspecialchars($patient["hasta_dogum"]) ?></li>
                <li class="list-group-item bg-black text-white"><strong>Kan Grubu:</strong> <?= htmlspecialchars($patient["hasta_kan"]) ?></li>
                <li class="list-group-item bg-black text-white"><strong>İlaçlar:</strong> <?= nl2br(htmlspecialchars($patient["hasta_ilac"])) ?></li>
                <li class="list-group-item bg-black text-white"><strong>Notlar:</strong> <?= nl2br(htmlspecialchars($patient["hasta_notlar"])) ?></li>
              </ul>
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
