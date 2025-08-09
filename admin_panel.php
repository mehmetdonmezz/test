<?php
session_start();
if (!isset($_SESSION["admin"])) {
    header("Location: admin_login.php");
    exit;
}
$conn = new mysqli("localhost", "root", "147369", "hasta_sistemi");

$sql = "SELECT u.name, u.email, p.patient_name, p.birth_date, p.condition
        FROM users u
        LEFT JOIN patients p ON u.id = p.user_id
        ORDER BY u.id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Admin Paneli</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-white">
  <div class="container mt-5">
    <h2 class="text-center mb-4">Admin Paneli</h2>
    <table class="table table-bordered table-striped shadow">
      <thead class="table-dark">
        <tr>
          <th>Kullanıcı Adı</th>
          <th>Email</th>
          <th>Hasta Adı</th>
          <th>Doğum Tarihi</th>
          <th>Durumu</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['patient_name']) ?></td>
            <td><?= $row['birth_date'] ?></td>
            <td><?= nl2br(htmlspecialchars($row['condition'])) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
