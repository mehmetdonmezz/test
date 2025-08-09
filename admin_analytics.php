<?php
require_once __DIR__ . '/config.php';
requireAdmin();

// Toplam kullanıcı
$totalUsers = (int)$pdo->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
// Profil kaydı olan kullanıcı sayısı
$totalProfiles = (int)$pdo->query('SELECT COUNT(*) AS c FROM patient_info')->fetch()['c'];

// Görüntülenme verisi loglanmıyorsa placeholder üretelim (gelecekte table: profile_views)
$last7 = [5, 7, 3, 9, 12, 8, 10];
$last30 = array_map(fn($i)=>rand(0,12), range(1,30));
$avg7 = round(array_sum($last7)/count($last7),1);
$avg30 = round(array_sum($last30)/count($last30),1);
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
        <div class="card bg-black border-0">
          <div class="card-body">
            <div class="text-white-50 small">Toplam Kullanıcı</div>
            <div class="h3 mb-0"><?= $totalUsers ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-black border-0">
          <div class="card-body">
            <div class="text-white-50 small">Profil Sayısı</div>
            <div class="h3 mb-0"><?= $totalProfiles ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-black border-0">
          <div class="card-body">
            <div class="text-white-50 small">Son 7 Gün Ortalama</div>
            <div class="h3 mb-0"><?= $avg7 ?></div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-black border-0">
          <div class="card-body">
            <div class="text-white-50 small">Son 30 Gün Ortalama</div>
            <div class="h3 mb-0"><?= $avg30 ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-lg-6">
        <div class="card bg-black border-0">
          <div class="card-body">
            <h5 class="card-title">Son 7 Gün</h5>
            <div class="text-white-50 small">Görüntülenme eğrisi (örnek veri)</div>
            <div class="d-flex align-items-end gap-1" style="height:140px;">
              <?php foreach ($last7 as $v): $h = max(4, $v*8); ?>
                <div title="<?= $v ?>" style="width:20px;height:<?= $h ?>px;background:#0d6efd"></div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card bg-black border-0">
          <div class="card-body">
            <h5 class="card-title">Son 30 Gün</h5>
            <div class="text-white-50 small">Görüntülenme eğrisi (örnek veri)</div>
            <div class="d-flex align-items-end gap-1" style="height:140px;overflow-x:auto;">
              <?php foreach ($last30 as $v): $h = max(4, $v*6); ?>
                <div title="<?= $v ?>" style="width:10px;height:<?= $h ?>px;background:#20c997"></div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</body>
</html>