<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "147369", "hasta_sistemi");
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}

$user_id = $_SESSION["user_id"];

// Kullanıcı bilgilerini çek
$stmtUser = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$user = $resultUser->fetch_assoc();
$stmtUser->close();

$infoSaved = false;
$error = "";

// Hasta bilgileri form post işlemi
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $hasta_adi = trim($_POST["hasta_adi"] ?? "");
    $hasta_dogum = trim($_POST["hasta_dogum"] ?? "");
    $hasta_kan = trim($_POST["hasta_kan"] ?? "");
    $hasta_ilac = trim($_POST["hasta_ilac"] ?? "");
    $hasta_notlar = trim($_POST["hasta_notlar"] ?? "");

    if (!$hasta_adi || !$hasta_dogum) {
        $error = "Hasta adı ve doğum tarihi zorunludur.";
    } else {
        // Kayıt var mı kontrol et
        $stmtCheck = $conn->prepare("SELECT id FROM patient_info WHERE user_id = ?");
        $stmtCheck->bind_param("i", $user_id);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if ($stmtCheck->num_rows > 0) {
            // Güncelle
            $stmtUpdate = $conn->prepare("UPDATE patient_info SET hasta_adi=?, hasta_dogum=?, hasta_kan=?, hasta_ilac=?, hasta_notlar=? WHERE user_id=?");
            $stmtUpdate->bind_param("sssssi", $hasta_adi, $hasta_dogum, $hasta_kan, $hasta_ilac, $hasta_notlar, $user_id);
            $stmtUpdate->execute();
            $stmtUpdate->close();
        } else {
            // Yeni kayıt
            $stmtInsert = $conn->prepare("INSERT INTO patient_info (user_id, hasta_adi, hasta_dogum, hasta_kan, hasta_ilac, hasta_notlar) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtInsert->bind_param("isssss", $user_id, $hasta_adi, $hasta_dogum, $hasta_kan, $hasta_ilac, $hasta_notlar);
            $stmtInsert->execute();
            $stmtInsert->close();
        }
        $stmtCheck->close();

        $infoSaved = true;
    }
}

// Kaydedilmiş hasta bilgilerini çek
$stmtInfo = $conn->prepare("SELECT hasta_adi, hasta_dogum, hasta_kan, hasta_ilac, hasta_notlar FROM patient_info WHERE user_id = ?");
$stmtInfo->bind_param("i", $user_id);
$stmtInfo->execute();
$resultInfo = $stmtInfo->get_result();
$patient = $resultInfo->fetch_assoc();
$stmtInfo->close();

$conn->close();
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
        <li class="nav-item"><span class="nav-link">Hoşgeldin, <?= htmlspecialchars($user["name"]) ?></span></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Çıkış Yap</a></li>
      </ul>
    </div>
  </nav>

  <main class="container py-5">
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
  </main>

</body>
</html>
