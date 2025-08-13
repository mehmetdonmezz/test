<?php
require_once __DIR__ . '/config.php';
requireAdmin();

$totalUsers = (int)$pdo->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
$totalProfiles = (int)$pdo->query('SELECT COUNT(*) AS c FROM patient_info')->fetch()['c'];

// Son 7 gün görüntülenme
$rows7 = $pdo->query("SELECT DATE(created_at) d, COUNT(*) c FROM profile_views WHERE created_at >= CURDATE() - INTERVAL 6 DAY GROUP BY DATE(created_at)")->fetchAll();
$map7 = [];
for ($i=6; $i>=0; $i--) { $d = (new DateTime("-$i day"))->format('Y-m-d'); $map7[$d] = 0; }
foreach ($rows7 as $r) { $map7[$r['d']] = (int)$r['c']; }
$last7 = array_values($map7);
$avg7 = count($last7) ? round(array_sum($last7)/count($last7),1) : 0;

// Son 30 gün görüntülenme
$rows30 = $pdo->query("SELECT DATE(created_at) d, COUNT(*) c FROM profile_views WHERE created_at >= CURDATE() - INTERVAL 29 DAY GROUP BY DATE(created_at)")->fetchAll();
$map30 = [];
for ($i=29; $i>=0; $i--) { $d = (new DateTime("-$i day"))->format('Y-m-d'); $map30[$d] = 0; }
foreach ($rows30 as $r) { $map30[$r['d']] = (int)$r['c']; }
$last30 = array_values($map30);
$avg30 = count($last30) ? round(array_sum($last30)/count($last30),1) : 0;

// Huni: son 30 gün etkinlikleri
$views30 = array_sum($last30);
$eventCounts = [
  'call_112' => 0,
  'call_contact' => 0,
  'open_maps' => 0,
];
$stmtE = $pdo->query("SELECT event, COUNT(*) c FROM profile_events WHERE created_at >= CURDATE() - INTERVAL 29 DAY GROUP BY event");
foreach ($stmtE as $e) {
  if (isset($eventCounts[$e['event']])) { $eventCounts[$e['event']] = (int)$e['c']; }
}
function pct($num, $den) { return $den > 0 ? round(($num/$den)*100) : 0; }
?>
<!DOCTYPE html>
<html lang="tr" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Analitik - Admin</title>
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
    <h1 class="h4 mb-3">Analitik</h1>

    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card bg-black border-0"><div class="card-body"><div class="text-white-50 small">Toplam Kullanıcı</div><div class="h3 mb-0"><?= $totalUsers ?></div></div></div>
      </div>
      <div class="col-md-3">
        <div class="card bg-black border-0"><div class="card-body"><div class="text-white-50 small">Profil Sayısı</div><div class="h3 mb-0"><?= $totalProfiles ?></div></div></div>
      </div>
      <div class="col-md-3">
        <div class="card bg-black border-0"><div class="card-body"><div class="text-white-50 small">Son 7 Gün Ortalama</div><div class="h3 mb-0"><?= $avg7 ?></div></div></div>
      </div>
      <div class="col-md-3">
        <div class="card bg-black border-0"><div class="card-body"><div class="text-white-50 small">Son 30 Gün Ortalama</div><div class="h3 mb-0"><?= $avg30 ?></div></div></div>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-lg-6">
        <div class="card bg-black border-0"><div class="card-body">
          <h5 class="card-title">Son 7 Gün</h5>
          <div class="d-flex align-items-end gap-1" style="height:140px;">
            <?php foreach ($last7 as $v): $h = max(4, $v*10); ?>
              <div title="<?= $v ?>" style="width:20px;height:<?= $h ?>px;background:#0d6efd"></div>
            <?php endforeach; ?>
          </div>
        </div></div>
      </div>
      <div class="col-lg-6">
        <div class="card bg-black border-0"><div class="card-body">
          <h5 class="card-title">Son 30 Gün</h5>
          <div class="d-flex align-items-end gap-1" style="height:140px;overflow-x:auto;">
            <?php foreach ($last30 as $v): $h = max(4, $v*6); ?>
              <div title="<?= $v ?>" style="width:10px;height:<?= $h ?>px;background:#20c997"></div>
            <?php endforeach; ?>
          </div>
        </div></div>
      </div>
    </div>

    <div class="card bg-black border-0">
      <div class="card-body">
        <h5 class="card-title">Dönüşüm Hunisi (Son 30 Gün)</h5>
        <div class="row g-3">
          <div class="col-md-3"><div class="p-3 bg-dark rounded"><div class="text-white-50 small">Görüntülenme</div><div class="h4 mb-0"><?= $views30 ?></div></div></div>
          <div class="col-md-3"><div class="p-3 bg-dark rounded"><div class="text-white-50 small">Konum Açma</div><div class="h4 mb-0"><?= $eventCounts['open_maps'] ?> <span class="small text-white-50">(<?= pct($eventCounts['open_maps'],$views30) ?>%)</span></div></div></div>
          <div class="col-md-3"><div class="p-3 bg-dark rounded"><div class="text-white-50 small">Acili Ara</div><div class="h4 mb-0"><?= $eventCounts['call_112'] ?> <span class="small text-white-50">(<?= pct($eventCounts['call_112'],$views30) ?>%)</span></div></div></div>
          <div class="col-md-3"><div class="p-3 bg-dark rounded"><div class="text-white-50 small">Yakını Ara</div><div class="h4 mb-0"><?= $eventCounts['call_contact'] ?> <span class="small text-white-50">(<?= pct($eventCounts['call_contact'],$views30) ?>%)</span></div></div></div>
        </div>
      </div>
    </div>

  </div>
</body>
</html>