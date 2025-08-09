<?php
require_once __DIR__ . '/config.php';
requireAdmin();

$keyword = trim($_GET['q'] ?? '');
$params = [];
$sql = "SELECT u.id as user_id, u.name, u.email, u.is_admin, p.hasta_adi, p.hasta_dogum, p.hasta_kan, p.hasta_ilac, p.hasta_notlar FROM users u LEFT JOIN patient_info p ON u.id = p.user_id";
if ($keyword !== '') {
    $sql .= " WHERE u.name LIKE :kw OR u.email LIKE :kw OR p.hasta_adi LIKE :kw";
    $params[':kw'] = "%{$keyword}%";
}
$sql .= " ORDER BY u.id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8" />
<title>Admin Paneli - ARDİO</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="assets/styles.css" rel="stylesheet" />
</head>
<body class="bg-dark text-white">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">ARDİO Admin</a>
    <div class="d-flex">
      <a href="logout.php" class="btn btn-outline-light btn-sm">Çıkış Yap</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex align-items-center mb-3">
    <h1 class="h3 mb-0">Kullanıcılar</h1>
    <span class="badge badge-soft ms-2">Toplam: <?= count($rows) ?></span>
  </div>
  <form class="row g-2 mb-3" method="get">
    <div class="col-sm-6 col-md-4">
      <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" class="form-control form-control-sm" placeholder="İsim, e-posta veya hasta adı..." />
    </div>
    <div class="col-auto">
      <button class="btn btn-primary btn-sm">Ara</button>
      <a class="btn btn-secondary btn-sm" href="admin.php">Temizle</a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-dark table-striped align-middle table-hover table-clean">
      <thead>
        <tr>
          <th>#</th>
          <th>Ad Soyad</th>
          <th>E-posta</th>
          <th>Rol</th>
          <th>Hasta Adı</th>
          <th>Doğum</th>
          <th>Kan</th>
          <th>İlaçlar</th>
          <th>Notlar</th>
          <th class="text-end">İşlemler</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($rows as $row): ?>
        <tr>
          <td><?= (int)$row['user_id'] ?></td>
          <td><?= htmlspecialchars($row['name']) ?></td>
          <td><a class="text-info text-decoration-none" href="mailto:<?= htmlspecialchars($row['email']) ?>"><?= htmlspecialchars($row['email']) ?></a></td>
          <td><?= $row['is_admin'] ? '<span class="badge bg-warning text-dark">Admin</span>' : '<span class="badge bg-secondary">Kullanıcı</span>' ?></td>
          <td><?= htmlspecialchars($row['hasta_adi'] ?? '-') ?></td>
          <td><?= htmlspecialchars($row['hasta_dogum'] ?? '-') ?></td>
          <td><?= htmlspecialchars($row['hasta_kan'] ?? '-') ?></td>
          <td style="max-width:220px" class="text-truncate" title="<?= htmlspecialchars($row['hasta_ilac'] ?? '-') ?>"><?= htmlspecialchars($row['hasta_ilac'] ?? '-') ?></td>
          <td style="max-width:220px" class="text-truncate" title="<?= htmlspecialchars($row['hasta_notlar'] ?? '-') ?>"><?= htmlspecialchars($row['hasta_notlar'] ?? '-') ?></td>
          <td class="text-end">
            <a href="admin_edit_user.php?id=<?= $row['user_id'] ?>" class="btn btn-sm btn-outline-warning">Düzenle</a>
            <a href="admin_delete_user.php?id=<?= $row['user_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Kullanıcıyı silmek istediğinize emin misiniz?')">Sil</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
