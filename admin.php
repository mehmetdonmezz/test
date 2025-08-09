<?php
require_once __DIR__ . '/config.php';
requireAdmin();

// Params
$keyword = trim($_GET['q'] ?? '');
$role = $_GET['role'] ?? ''; // '', 'admin', 'user'
$hasInfo = $_GET['has'] ?? ''; // '', 'yes', 'no'
$sort = $_GET['sort'] ?? 'user_id';
$dir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(100, max(5, (int)($_GET['per'] ?? 10)));
$export = ($_GET['export'] ?? '') === 'csv';

// Whitelist sorting columns
$sortable = [
  'user_id' => 'u.id',
  'name' => 'u.name',
  'email' => 'u.email',
  'hasta_adi' => 'p.hasta_adi',
  'hasta_dogum' => 'p.hasta_dogum',
  'hasta_kan' => 'p.hasta_kan'
];
$orderBy = $sortable[$sort] ?? 'u.id';

// Build where
$where = [];
$params = [];
if ($keyword !== '') {
  $where[] = '(u.name LIKE :kw OR u.email LIKE :kw OR p.hasta_adi LIKE :kw)';
  $params[':kw'] = "%{$keyword}%";
}
if ($role === 'admin') {
  $where[] = 'u.is_admin = 1';
} elseif ($role === 'user') {
  $where[] = 'u.is_admin = 0';
}
if ($hasInfo === 'yes') {
  $where[] = 'p.user_id IS NOT NULL';
} elseif ($hasInfo === 'no') {
  $where[] = 'p.user_id IS NULL';
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Count
$countSql = "SELECT COUNT(*) AS c FROM users u LEFT JOIN patient_info p ON u.id = p.user_id {$whereSql}";
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$total = (int)$stmtCount->fetch()['c'];
$pages = max(1, (int)ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

// Data
$dataSql = "SELECT u.id as user_id, u.name, u.email, u.is_admin, p.hasta_adi, p.hasta_dogum, p.hasta_kan, p.hasta_ilac, p.hasta_notlar
            FROM users u
            LEFT JOIN patient_info p ON u.id = p.user_id
            {$whereSql}
            ORDER BY {$orderBy} {$dir}
            LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($dataSql);
foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

// CSV export
if ($export) {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="users_export.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['ID', 'Ad Soyad', 'E-posta', 'Rol', 'Hasta Adı', 'Doğum', 'Kan', 'İlaçlar', 'Notlar']);
  foreach ($rows as $r) {
    fputcsv($out, [
      $r['user_id'], $r['name'], $r['email'], $r['is_admin'] ? 'Admin' : 'Kullanıcı',
      $r['hasta_adi'], $r['hasta_dogum'], $r['hasta_kan'], $r['hasta_ilac'], $r['hasta_notlar']
    ]);
  }
  fclose($out);
  exit;
}

function q($k, $v) {
  $now = $_GET;
  $now[$k] = $v;
  return htmlspecialchars('?' . http_build_query($now));
}
?>

<!DOCTYPE html>
<html lang="tr" data-bs-theme="dark">
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
    <a class="navbar-brand" href="index.php">ARDİO Admin</a>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-light btn-sm" href="admin_site.php">Site Ayarları</a>
      <a class="btn btn-outline-light btn-sm" href="admin_analytics.php">Analitik</a>
      <button class="btn btn-outline-light btn-sm" onclick="toggleTheme()" type="button"><span data-theme-label>Aydınlık</span></button>
      <?php if (isset($_SESSION['impersonator_admin_id'])): ?>
        <a href="admin_unimpersonate.php" class="btn btn-warning btn-sm">Admin’e Geri Dön</a>
      <?php endif; ?>
      <a href="logout.php" class="btn btn-outline-light btn-sm">Çıkış Yap</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Kullanıcılar</h1>
    <span class="badge badge-soft">Toplam: <?= $total ?></span>
    <div class="ms-auto d-flex gap-2">
      <a class="btn btn-secondary btn-sm" href="<?= q('export','csv') ?>">CSV Dışa Aktar</a>
      <a class="btn btn-success btn-sm" href="admin_new_user.php">Yeni Kullanıcı</a>
    </div>
  </div>

  <form class="row g-2 mb-3 align-items-end" method="get">
    <div class="col-sm-4">
      <label class="form-label small">Ara</label>
      <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" class="form-control form-control-sm" placeholder="İsim, e-posta, hasta adı..." />
    </div>
    <div class="col-sm-2">
      <label class="form-label small">Rol</label>
      <select name="role" class="form-select form-select-sm">
        <option value="" <?= $role===''?'selected':'' ?>>Hepsi</option>
        <option value="admin" <?= $role==='admin'?'selected':'' ?>>Admin</option>
        <option value="user" <?= $role==='user'?'selected':'' ?>>Kullanıcı</option>
      </select>
    </div>
    <div class="col-sm-2">
      <label class="form-label small">Veri</label>
      <select name="has" class="form-select form-select-sm">
        <option value="" <?= $hasInfo===''?'selected':'' ?>>Hepsi</option>
        <option value="yes" <?= $hasInfo==='yes'?'selected':'' ?>>Hasta verisi var</option>
        <option value="no" <?= $hasInfo==='no'?'selected':'' ?>>Hasta verisi yok</option>
      </select>
    </div>
    <div class="col-sm-2">
      <label class="form-label small">Sayfa boyutu</label>
      <select name="per" class="form-select form-select-sm">
        <?php foreach([10,20,50,100] as $pp): ?>
          <option value="<?= $pp ?>" <?= $perPage===$pp?'selected':'' ?>><?= $pp ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <button class="btn btn-primary btn-sm">Uygula</button>
      <a class="btn btn-secondary btn-sm" href="admin.php">Temizle</a>
    </div>
  </form>

  <form method="post" action="admin_bulk_action.php" onsubmit="return confirmBulk(this);">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
      <div class="input-group input-group-sm" style="max-width:420px;">
        <select name="action" class="form-select form-select-sm" required>
          <option value="" selected>Toplu işlem seçin…</option>
          <option value="delete">Seçilenleri Sil</option>
          <option value="make_admin">Seçilenleri Admin Yap</option>
          <option value="make_user">Seçilenleri Kullanıcı Yap</option>
        </select>
        <button class="btn btn-outline-light" type="submit">Uygula</button>
      </div>
      <div class="form-check ms-2">
        <input class="form-check-input" type="checkbox" id="checkAll" onclick="toggleAll(this)" />
        <label class="form-check-label" for="checkAll">Tümünü Seç</label>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-dark table-striped align-middle table-hover table-clean">
        <thead>
          <tr>
            <th></th>
            <th><a class="link-light" href="<?= q('sort','user_id') . '&dir=' . ($sort==='user_id' && $dir==='ASC' ? 'desc' : 'asc') ?>">#</a></th>
            <th><a class="link-light" href="<?= q('sort','name') . '&dir=' . ($sort==='name' && $dir==='ASC' ? 'desc' : 'asc') ?>">Ad Soyad</a></th>
            <th><a class="link-light" href="<?= q('sort','email') . '&dir=' . ($sort==='email' && $dir==='ASC' ? 'desc' : 'asc') ?>">E-posta</a></th>
            <th>Rol</th>
            <th><a class="link-light" href="<?= q('sort','hasta_adi') . '&dir=' . ($sort==='hasta_adi' && $dir==='ASC' ? 'desc' : 'asc') ?>">Hasta Adı</a></th>
            <th><a class="link-light" href="<?= q('sort','hasta_dogum') . '&dir=' . ($sort==='hasta_dogum' && $dir==='ASC' ? 'desc' : 'asc') ?>">Doğum</a></th>
            <th><a class="link-light" href="<?= q('sort','hasta_kan') . '&dir=' . ($sort==='hasta_kan' && $dir==='ASC' ? 'desc' : 'asc') ?>">Kan</a></th>
            <th>İlaçlar</th>
            <th>Notlar</th>
            <th class="text-end">İşlemler</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($rows as $row): ?>
          <tr>
            <td><input class="form-check-input" type="checkbox" name="ids[]" value="<?= (int)$row['user_id'] ?>" /></td>
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
              <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#viewModal" data-row='<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>'>Görüntüle</button>
              <a href="admin_edit_user.php?id=<?= $row['user_id'] ?>" class="btn btn-sm btn-outline-warning">Düzenle</a>
              <a href="admin_impersonate.php?id=<?= $row['user_id'] ?>" class="btn btn-sm btn-outline-light" onclick="return confirm('Bu kullanıcı olarak giriş yapılacak. Devam edilsin mi?')">Giriş Yap</a>
              <a href="admin_delete_user.php?id=<?= $row['user_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Kullanıcıyı silmek istediğinize emin misiniz?')">Sil</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </form>

  <nav aria-label="Sayfalama" class="mt-3">
    <ul class="pagination pagination-sm">
      <?php for($p=1;$p<=$pages;$p++): ?>
        <li class="page-item <?= $p===$page?'active':'' ?>">
          <a class="page-link" href="<?= q('page',$p) ?>"><?= $p ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header">
        <h5 class="modal-title">Kullanıcı Detayı</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="view-content">Yükleniyor...</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/theme.js"></script>
<script>
const viewModal = document.getElementById('viewModal');
viewModal.addEventListener('show.bs.modal', event => {
  const button = event.relatedTarget;
  const data = JSON.parse(button.getAttribute('data-row'));
  const html = `
    <div class="row g-3">
      <div class="col-md-6"><strong>ID:</strong> ${data.user_id}</div>
      <div class="col-md-6"><strong>Rol:</strong> ${data.is_admin ? 'Admin' : 'Kullanıcı'}</div>
      <div class="col-md-6"><strong>Ad:</strong> ${escapeHtml(data.name || '')}</div>
      <div class="col-md-6"><strong>E-posta:</strong> ${escapeHtml(data.email || '')}</div>
      <hr/>
      <div class="col-md-6"><strong>Hasta Adı:</strong> ${escapeHtml(data.hasta_adi || '-')}</div>
      <div class="col-md-6"><strong>Doğum:</strong> ${escapeHtml(data.hasta_dogum || '-')}</div>
      <div class="col-md-6"><strong>Kan:</strong> ${escapeHtml(data.hasta_kan || '-')}</div>
      <div class="col-12"><strong>İlaçlar:</strong><br/> ${escapeHtml(data.hasta_ilac || '-')}</div>
      <div class="col-12"><strong>Notlar:</strong><br/> ${escapeHtml(data.hasta_notlar || '-')}</div>
    </div>`;
  document.getElementById('view-content').innerHTML = html;
});
function escapeHtml(unsafe){
  return String(unsafe)
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'",'&#039;');
}
function toggleAll(cb){
  document.querySelectorAll('input[name="ids[]"]').forEach(el=>{ el.checked = cb.checked; });
}
function confirmBulk(form){
  const action = form.action.value || form.querySelector('select[name="action"]').value;
  const checked = Array.from(form.querySelectorAll('input[name="ids[]"]:checked')).length;
  if (!checked) { alert('Önce en az bir kullanıcı seçiniz.'); return false; }
  if (!action) { alert('Bir toplu işlem seçiniz.'); return false; }
  if (action==='delete') return confirm('Seçilen kullanıcıları silmek istediğinize emin misiniz?');
  return true;
}
</script>
</body>
</html>
