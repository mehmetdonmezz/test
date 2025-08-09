<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
$conn = new mysqli("localhost", "root", "147369", "hasta_sistemi");

$user_id = $_SESSION["user_id"];
$stmt = $conn->prepare("SELECT * FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
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
          <p><strong>Adı:</strong> <?= htmlspecialchars($data["patient_name"]) ?></p>
          <p><strong>Doğum Tarihi:</strong> <?= $data["birth_date"] ?></p>
          <p><strong>Durumu:</strong> <?= nl2br(htmlspecialchars($data["condition"])) ?></p>
        <?php else: ?>
          <div class="alert alert-warning text-center">Kayıtlı hasta bilgisi bulunamadı.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>
