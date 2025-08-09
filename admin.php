<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lang.php';
setLangFromRequest();
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
<html lang="<?= htmlspecialchars(getLang()) ?>" data-bs-theme="dark">
<head>
<meta charset="UTF-8" />
<title><?= t('admin_panel') ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="assets/styles.css" rel="stylesheet" />
</head>
<body class="bg-dark text-white">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">ARDİO Admin</a>
    <div class="d-flex align-items-center gap-2">
      <a class="btn btn-outline-light btn-sm" href="admin_site.php"><?= t('site_settings') ?></a>
      <a class="btn btn-outline-light btn-sm" href="admin_analytics.php"><?= t('analytics') ?></a>
      <div class="dropdown">
        <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"><?= t('language') ?></button>
        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
          <li><a class="dropdown-item" href="?lang=tr"><?= t('turkish') ?></a></li>
          <li><a class="dropdown-item" href="?lang=en"><?= t('english') ?></a></li>
        </ul>
      </div>
      <button class="btn btn-outline-light btn-sm" onclick="toggleTheme()" type="button"><span data-theme-label>Aydınlık</span></button>
      <?php if (isset($_SESSION['impersonator_admin_id'])): ?>
        <a href="admin_unimpersonate.php" class="btn btn-warning btn-sm"><?= t('return_to_admin') ?></a>
      <?php endif; ?>
      <a href="logout.php" class="btn btn-outline-light btn-sm"><?= t('logout') ?></a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0"><?= t('users') ?></h1>
    <span class="badge badge-soft"><?= t('total') ?>: <?= $total ?></span>
    <div class="ms-auto d-flex gap-2">
      <a class="btn btn-secondary btn-sm" href="<?= q('export','csv') ?>"><?= t('export_csv') ?></a>
      <a class="btn btn-success btn-sm" href="admin_new_user.php"><?= t('new_user') ?></a>
    </div>
  </div>

  <form class="row g-2 mb-3 align-items-end" method="get">
    <div class="col-sm-4">
      <label class="form-label small"><?= t('search') ?></label>
      <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" class="form-control form-control-sm" placeholder="İsim, e-posta, hasta adı..." />
    </div>
    <div class="col-sm-2">
      <label class="form-label small"><?= t('role') ?></label>
      <select name="role" class="form-select form-select-sm">
        <option value="" <?= $role===''?'selected':'' ?>><?= t('all') ?></option>
        <option value="admin" <?= $role==='admin'?'selected':'' ?>>Admin</option>
        <option value="user" <?= $role==='user'?'selected':'' ?>>Kullanıcı</option>
      </select>
    </div>
    <div class="col-sm-2">
      <label class="form-label small"><?= t('data') ?></label>
      <select name="has" class="form-select form-select-sm">
        <option value="" <?= $hasInfo===''?'selected':'' ?>><?= t('all') ?></option>
        <option value="yes" <?= $hasInfo==='yes'?'selected':'' ?>><?= t('has_data') ?></option>
        <option value="no" <?= $hasInfo==='no'?'selected':'' ?>><?= t('no_data') ?></option>
      </select>
    </div>
    <div class="col-sm-2">
      <label class="form-label small"><?= t('page_size') ?></label>
      <select name="per" class="form-select form-select-sm">
        <?php foreach([10,20,50,100] as $pp): ?>
          <option value="<?= $pp ?>" <?= $perPage===$pp?'selected':'' ?>><?= $pp ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <button class="btn btn-primary btn-sm"><?= t('apply') ?></button>
      <a class="btn btn-secondary btn-sm" href="admin.php"><?= t('clear') ?></a>
    </div>
  </form>

  <form method="post" action="admin_bulk_action.php" onsubmit="return confirmBulk(this);">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
      <div class="input-group input-group-sm" style="max-width:420px;">
        <select name="action" class="form-select form-select-sm" required>
          <option value="" selected><?= t('bulk_action_select') ?></option>
          <option value="delete"><?= t('delete_selected') ?></option>
          <option value="make_admin"><?= t('make_admin_selected') ?></option>
          <option value="make_user"><?= t('make_user_selected') ?></option>
        </select>
        <button class="btn btn-outline-light" type="submit"><?= t('apply') ?></button>
      </div>
      <div class="form-check ms-2">
        <input class="form-check-input" type="checkbox" id="checkAll" onclick="toggleAll(this)" />
        <label class="form-check-label" for="checkAll"><?= t('select_all') ?></label>
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
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td>
              <?php if ($row['is_admin']): ?>
                <span class="badge bg-warning text-dark">Admin</span>
              <?php else: ?>
                <span class="badge bg-secondary">Kullanıcı</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($row['hasta_adi'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['hasta_dogum'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['hasta_kan'] ?? '') ?></td>
            <td class="text-truncate" style="max-width:240px;"><?= htmlspecialchars($row['hasta_ilac'] ?? '') ?></td>
            <td class="text-truncate" style="max-width:240px;"><?= htmlspecialchars($row['hasta_notlar'] ?? '') ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-light" href="admin_edit_user.php?id=<?= (int)$row['user_id'] ?>">Düzenle</a>
              <a class="btn btn-sm btn-outline-info" href="admin_impersonate.php?id=<?= (int)$row['user_id'] ?>">Giriş Yap</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <nav>
      <ul class="pagination pagination-sm">
        <?php for($i=1;$i<=$pages;$i++): ?>
          <li class="page-item <?= $i===$page?'active':'' ?>">
            <a class="page-link" href="<?= q('page',$i) ?>"><?= $i ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>

  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/theme.js"></script>
<script>
function toggleAll(el){
  const boxes=document.querySelectorAll('input[name="ids[]"]');
  boxes.forEach(b=>b.checked=el.checked);
}
function confirmBulk(f){
  return confirm('Onaylıyor musunuz?');
}
</script>
</body>
</html>
