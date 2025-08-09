<?php
require_once __DIR__ . '/config.php';
requireAuth();

$user_id = (int)$_SESSION["user_id"];
$stmt = $pdo->prepare("SELECT hasta_adi, hasta_dogum, hasta_kan, hasta_ilac, hasta_notlar FROM patient_info WHERE user_id = ?");
$stmt->execute([$user_id]);
$data = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Hasta Bilgileri</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container mt-5">
    <div class="card shadow mx-auto" style="max-width: 600px;">
      <div class="card-body">
        <h3 class="card-title text-center mb-4">Hasta Bilgileri</h3>
        <?php if ($data): ?>
          <p><strong>Adı:</strong> <?= htmlspecialchars($data["hasta_adi"]) ?></p>
          <p><strong>Doğum Tarihi:</strong> <?= htmlspecialchars($data["hasta_dogum"]) ?></p>
          <p><strong>Kan Grubu:</strong> <?= htmlspecialchars($data["hasta_kan"]) ?></p>
          <p><strong>İlaçlar:</strong> <?= nl2br(htmlspecialchars($data["hasta_ilac"])) ?></p>
          <p><strong>Notlar:</strong> <?= nl2br(htmlspecialchars($data["hasta_notlar"])) ?></p>
        <?php else: ?>
          <div class="alert alert-warning text-center">Kayıtlı hasta bilgisi bulunamadı.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>
