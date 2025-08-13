<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lang.php';
setLangFromRequest();
requireAdmin();

$settings = getSiteSettings();
$galleryDir = __DIR__ . '/assets/gallery';
if (!is_dir($galleryDir)) @mkdir($galleryDir, 0775, true);

// Kaydet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $settings['hero_title'] = trim($_POST['hero_title'] ?? ($settings['hero_title'] ?? ''));
    $settings['hero_subtitle'] = trim($_POST['hero_subtitle'] ?? ($settings['hero_subtitle'] ?? ''));
    $settings['hero_title_en'] = trim($_POST['hero_title_en'] ?? ($settings['hero_title_en'] ?? ''));
    $settings['hero_subtitle_en'] = trim($_POST['hero_subtitle_en'] ?? ($settings['hero_subtitle_en'] ?? ''));
    $settings['contact_email'] = trim($_POST['contact_email'] ?? ($settings['contact_email'] ?? ''));
    $settings['social']['twitter'] = trim($_POST['twitter'] ?? '');
    $settings['social']['instagram'] = trim($_POST['instagram'] ?? '');
    $settings['social']['linkedin'] = trim($_POST['linkedin'] ?? '');
    $settings['social']['youtube'] = trim($_POST['youtube'] ?? '');
    saveSiteSettings($settings);
    header('Location: admin_site.php');
    exit;
}

// Galeri Yükleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_gallery']) && isset($_FILES['images'])) {
    foreach ($_FILES['images']['tmp_name'] as $idx => $tmp) {
        if (!is_uploaded_file($tmp)) continue;
        $info = @getimagesize($tmp);
        if (!$info) continue;
        $ext = image_type_to_extension($info[2], false) ?: 'jpg';
        $name = 'g_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
        @move_uploaded_file($tmp, $galleryDir . '/' . $name);
        $settings['gallery'][] = 'assets/gallery/' . $name;
    }
    saveSiteSettings($settings);
    header('Location: admin_site.php');
    exit;
}

// Galeri Sil
if (isset($_GET['del'])) {
    $del = $_GET['del'];
    $settings['gallery'] = array_values(array_filter($settings['gallery'] ?? [], function($p) use ($del){ return $p !== $del; }));
    saveSiteSettings($settings);
    $abs = __DIR__ . '/' . $del;
    if (strpos(realpath($abs), realpath(__DIR__)) === 0 && file_exists($abs)) @unlink($abs);
    header('Location: admin_site.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(getLang()) ?>" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Site Ayarları - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="assets/styles.css" rel="stylesheet" />
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
    <h1 class="h4 mb-3">Site Ayarları</h1>

    <div class="card bg-black border-0 shadow-sm mb-4">
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="save_settings" value="1" />
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Hero Başlığı (TR)</label>
              <input type="text" name="hero_title" value="<?= htmlspecialchars($settings['hero_title'] ?? '') ?>" class="form-control" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Hero Başlığı (EN)</label>
              <input type="text" name="hero_title_en" value="<?= htmlspecialchars($settings['hero_title_en'] ?? '') ?>" class="form-control" />
            </div>
            <div class="col-12">
              <label class="form-label">Hero Alt Metin (TR)</label>
              <textarea name="hero_subtitle" rows="2" class="form-control"><?= htmlspecialchars($settings['hero_subtitle'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Hero Alt Metin (EN)</label>
              <textarea name="hero_subtitle_en" rows="2" class="form-control"><?= htmlspecialchars($settings['hero_subtitle_en'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">İletişim E-postası</label>
              <input type="email" name="contact_email" value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>" class="form-control" />
            </div>
            <div class="col-12"><hr class="border-secondary" /></div>
            <div class="col-md-3">
              <label class="form-label">Twitter</label>
              <input type="url" name="twitter" value="<?= htmlspecialchars($settings['social']['twitter'] ?? '') ?>" class="form-control" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Instagram</label>
              <input type="url" name="instagram" value="<?= htmlspecialchars($settings['social']['instagram'] ?? '') ?>" class="form-control" />
            </div>
            <div class="col-md-3">
              <label class="form-label">LinkedIn</label>
              <input type="url" name="linkedin" value="<?= htmlspecialchars($settings['social']['linkedin'] ?? '') ?>" class="form-control" />
            </div>
            <div class="col-md-3">
              <label class="form-label">YouTube</label>
              <input type="url" name="youtube" value="<?= htmlspecialchars($settings['social']['youtube'] ?? '') ?>" class="form-control" />
            </div>
          </div>
          <div class="mt-3 d-flex gap-2">
            <button class="btn btn-success">Kaydet</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card bg-black border-0 shadow-sm">
      <div class="card-body">
        <h2 class="h5 mb-3">Galeri Görselleri</h2>
        <form method="post" enctype="multipart/form-data" class="mb-3">
          <input type="hidden" name="upload_gallery" value="1" />
          <div class="row g-2 align-items-center">
            <div class="col-md-8">
              <input class="form-control" type="file" name="images[]" multiple accept="image/*" />
            </div>
            <div class="col-md-4 text-md-end">
              <button class="btn btn-primary">Yükle</button>
            </div>
          </div>
        </form>
        <div class="row g-3">
          <?php foreach (($settings['gallery'] ?? []) as $img): ?>
            <div class="col-6 col-md-3">
              <div class="position-relative">
                <img src="<?= htmlspecialchars($img) ?>" class="img-fluid gallery-img" alt="galeri" />
                <a class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" href="?del=<?= urlencode($img) ?>" onclick="return confirm('Görseli silmek istediğinize emin misiniz?')">Sil</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>