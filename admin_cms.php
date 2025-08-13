<?php
require_once __DIR__ . '/config.php';
requireAdmin();
$cms = getCms();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $lang = $_POST['lang'] ?? 'tr';
  if (isset($_POST['save_faq'])) {
    $faq = json_decode($_POST['faq_json'] ?? '[]', true);
    if (is_array($faq)) { $cms['faq'][$lang] = $faq; saveCms($cms); }
  } elseif (isset($_POST['save_ann'])) {
    $ann = json_decode($_POST['ann_json'] ?? '[]', true);
    if (is_array($ann)) { $cms['announcements'][$lang] = $ann; saveCms($cms); }
  }
  header('Location: admin_cms.php?lang=' . urlencode($lang));
  exit;
}

$lang = $_GET['lang'] ?? 'tr';
$faq = $cms['faq'][$lang] ?? [];
$ann = $cms['announcements'][$lang] ?? [];
?>
<!DOCTYPE html>
<html lang="tr" data-bs-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CMS - Admin</title>
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
    <h1 class="h4 mb-3">CMS</h1>

    <div class="mb-3">
      <a class="btn btn-sm btn-outline-light <?= $lang==='tr'?'active':'' ?>" href="?lang=tr">TR</a>
      <a class="btn btn-sm btn-outline-light <?= $lang==='en'?'active':'' ?>" href="?lang=en">EN</a>
    </div>

    <div class="row g-4">
      <div class="col-lg-6">
        <div class="card bg-black border-0">
          <div class="card-body">
            <h5 class="card-title">SSS (FAQ)</h5>
            <form method="post">
              <input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>" />
              <input type="hidden" name="save_faq" value="1" />
              <div class="mb-2 small text-white-50">JSON düzenleyin: [{"q":"soru","a":"cevap"}]</div>
              <textarea name="faq_json" rows="10" class="form-control" spellcheck="false"><?= htmlspecialchars(json_encode($faq, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></textarea>
              <div class="mt-2"><button class="btn btn-success btn-sm">Kaydet</button></div>
            </form>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card bg-black border-0">
          <div class="card-body">
            <h5 class="card-title">Duyurular</h5>
            <form method="post">
              <input type="hidden" name="lang" value="<?= htmlspecialchars($lang) ?>" />
              <input type="hidden" name="save_ann" value="1" />
              <div class="mb-2 small text-white-50">JSON düzenleyin: [{"t":"başlık","b":"metin"}]</div>
              <textarea name="ann_json" rows="10" class="form-control" spellcheck="false"><?= htmlspecialchars(json_encode($ann, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></textarea>
              <div class="mt-2"><button class="btn btn-success btn-sm">Kaydet</button></div>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
</body>
</html>