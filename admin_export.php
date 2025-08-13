<?php
require_once __DIR__ . '/config.php';
requireAdmin();

if (isset($_GET['download']) && $_GET['download']==='users_csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="users.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['id','name','email','is_admin','created_at']);
  foreach ($pdo->query('SELECT id,name,email,is_admin,created_at FROM users') as $r) {
    fputcsv($out, [$r['id'],$r['name'],$r['email'],$r['is_admin'],$r['created_at'] ?? '']);
  }
  fclose($out); exit;
}
if (isset($_GET['download']) && $_GET['download']==='patients_csv') {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="patient_info.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['id','user_id','hasta_adi','hasta_dogum','hasta_kan','hasta_ilac','hasta_notlar']);
  foreach ($pdo->query('SELECT id,user_id,hasta_adi,hasta_dogum,hasta_kan,hasta_ilac,hasta_notlar FROM patient_info') as $r) {
    fputcsv($out, [$r['id'],$r['user_id'],$r['hasta_adi'],$r['hasta_dogum'],$r['hasta_kan'],$r['hasta_ilac'],$r['hasta_notlar']]);
  }
  fclose($out); exit;
}
if (isset($_GET['download']) && $_GET['download']==='backup_json') {
  header('Content-Type: application/json');
  header('Content-Disposition: attachment; filename="backup.json"');
  $data = [
    'users' => $pdo->query('SELECT id,name,email,is_admin,created_at FROM users')->fetchAll(),
    'patient_info' => $pdo->query('SELECT * FROM patient_info')->fetchAll(),
    'profile_views' => $pdo->query('SELECT * FROM profile_views ORDER BY id DESC LIMIT 10000')->fetchAll(),
    'profile_events' => $pdo->query('SELECT * FROM profile_events ORDER BY id DESC LIMIT 10000')->fetchAll(),
  ];
  echo json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE); exit;
}

$importMsg = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['import_json']) && isset($_FILES['json'])) {
  if (is_uploaded_file($_FILES['json']['tmp_name'])) {
    $json = json_decode(file_get_contents($_FILES['json']['tmp_name']), true);
    if (is_array($json)) {
      $pdo->beginTransaction();
      try {
        if (!empty($json['users'])) {
          foreach ($json['users'] as $u) {
            $stmt = $pdo->prepare('REPLACE INTO users (id,name,email,password,is_admin,created_at) VALUES (?,?,?,?,?,?)');
            $stmt->execute([ $u['id'], $u['name'], $u['email'], $u['password'] ?? password_hash('ChangeMe123!', PASSWORD_DEFAULT), (int)($u['is_admin']??0), $u['created_at'] ?? date('Y-m-d H:i:s') ]);
          }
        }
        if (!empty($json['patient_info'])) {
          foreach ($json['patient_info'] as $p) {
            $stmt = $pdo->prepare('REPLACE INTO patient_info (id,user_id,hasta_adi,hasta_dogum,hasta_kan,hasta_ilac,hasta_notlar) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([ $p['id'],$p['user_id'],$p['hasta_adi'],$p['hasta_dogum'],$p['hasta_kan'],$p['hasta_ilac'],$p['hasta_notlar'] ]);
          }
        }
        $pdo->commit();
        $importMsg = 'İçe aktarma tamamlandı.';
      } catch (Throwable $e) {
        $pdo->rollBack();
        $importMsg = 'Hata: içe aktarma başarısız.';
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="tr" data-bs-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dışa/İçe Aktar - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-dark text-white">
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
      <a class="navbar-brand" href="admin.php">ARDİO Admin</a>
      <div class="d-flex gap-2">
        <a href="admin.php" class="btn btn-outline-light btn-sm">Listeye Dön</a>
        <a href="logout.php" class="btn btn-outline-light btn-sm">Çıkış Yap</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <h1 class="h4 mb-3">Dışa/İçe Aktar ve Yedekleme</h1>

    <div class="row g-3">
      <div class="col-md-4">
        <div class="card bg-black border-0"><div class="card-body">
          <h5 class="card-title">CSV Dışa Aktar</h5>
          <a class="btn btn-secondary btn-sm d-block mb-2" href="?download=users_csv">Kullanıcılar (CSV)</a>
          <a class="btn btn-secondary btn-sm d-block" href="?download=patients_csv">Hasta Bilgileri (CSV)</a>
        </div></div>
      </div>
      <div class="col-md-4">
        <div class="card bg-black border-0"><div class="card-body">
          <h5 class="card-title">JSON Yedek</h5>
          <a class="btn btn-success btn-sm d-block" href="?download=backup_json">Tam Yedek (JSON)</a>
        </div></div>
      </div>
      <div class="col-md-4">
        <div class="card bg-black border-0"><div class="card-body">
          <h5 class="card-title">JSON İçe Aktar</h5>
          <?php if ($importMsg): ?><div class="alert alert-info py-1 small"><?= htmlspecialchars($importMsg) ?></div><?php endif; ?>
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="import_json" value="1" />
            <input class="form-control form-control-sm mb-2" type="file" name="json" accept="application/json" required />
            <button class="btn btn-warning btn-sm">İçe Aktar</button>
          </form>
        </div></div>
      </div>
    </div>

    <div class="text-white-50 small mt-3">Not: JSON içe aktarım REPLACE kullanır. Şifre alanı yedekte yoksa varsayılan parola atanır.</div>

  </div>
</body>
</html>