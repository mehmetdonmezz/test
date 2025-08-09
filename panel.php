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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-dark text-white">

  <nav class="navbar navbar-expand-lg navbar-dark bg-primary px-4">
    <a class="navbar-brand" href="#">ARDİO</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><span class="nav-link">Hoşgeldin, <?= htmlspecialchars($user["name"] ?? "") ?></span></li>
        <?php if (isAdmin()): ?>
          <li class="nav-item"><a class="nav-link" href="admin.php">Admin</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link" href="logout.php">Çıkış Yap</a></li>
      </ul>
    </div>
  </nav>

  <main class="container py-5">
    <div class="row g-4">
      <div class="col-lg-8">
        <h2 class="mb-4">Hasta Bilgileri</h2>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($infoSaved): ?>
          <div class="alert alert-success">Bilgiler başarıyla kaydedildi.</div>
        <?php endif; ?>

        <form method="POST" action="panel.php" class="mb-5">
          <div class="mb-3">
            <label for="hasta_adi" class="form-label">Hasta Adı Soyadı</label>
            <input type="text" id="hasta_adi" name="hasta_adi" class="form-control" required value="<?= htmlspecialchars($patient["hasta_adi"] ?? "") ?>" />
          </div>
          <div class="mb-3">
            <label for="hasta_dogum" class="form-label">Doğum Tarihi</label>
            <input type="date" id="hasta_dogum" name="hasta_dogum" class="form-control" required value="<?= htmlspecialchars($patient["hasta_dogum"] ?? "") ?>" />
          </div>
          <div class="mb-3">
            <label for="hasta_kan" class="form-label">Kan Grubu</label>
            <input type="text" id="hasta_kan" name="hasta_kan" class="form-control" value="<?= htmlspecialchars($patient["hasta_kan"] ?? "") ?>" />
          </div>
          <div class="mb-3">
            <label for="hasta_ilac" class="form-label">İlaçlar</label>
            <textarea id="hasta_ilac" name="hasta_ilac" class="form-control" rows="3"><?= htmlspecialchars($patient["hasta_ilac"] ?? "") ?></textarea>
          </div>
          <div class="mb-3">
            <label for="hasta_notlar" class="form-label">Notlar</label>
            <textarea id="hasta_notlar" name="hasta_notlar" class="form-control" rows="3"><?= htmlspecialchars($patient["hasta_notlar"] ?? "") ?></textarea>
          </div>
          <button type="submit" class="btn btn-success">Kaydet</button>
        </form>

        <?php if ($patient): ?>
          <h3>Kaydedilmiş Hasta Bilgileri</h3>
          <ul class="list-group bg-secondary text-white p-3 rounded">
            <li><strong>Adı Soyadı:</strong> <?= htmlspecialchars($patient["hasta_adi"]) ?></li>
            <li><strong>Doğum Tarihi:</strong> <?= htmlspecialchars($patient["hasta_dogum"]) ?></li>
            <li><strong>Kan Grubu:</strong> <?= htmlspecialchars($patient["hasta_kan"]) ?></li>
            <li><strong>İlaçlar:</strong> <?= nl2br(htmlspecialchars($patient["hasta_ilac"])) ?></li>
            <li><strong>Notlar:</strong> <?= nl2br(htmlspecialchars($patient["hasta_notlar"])) ?></li>
          </ul>
        <?php endif; ?>

        <p class="mt-4 text-muted small">Kişisel hasta bilgilerinizi buradan güncelleyebilirsiniz.</p>
      </div>
      <div class="col-lg-4">
        <div class="card bg-secondary text-white">
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
            <a class="btn btn-outline-light btn-sm" href="<?= htmlspecialchars($publicUrl) ?>" target="_blank" rel="noopener">Acil Profili Aç</a>
          </div>
        </div>
      </div>
    </div>
  </main>

</body>
</html>
