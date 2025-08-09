<?php
session_start();
require_once __DIR__ . '/config.php';
$site = getSiteSettings();
$heroTitle = $site['hero_title'] ?? 'Kaybolmayı İmkânsız Kılan Bileklik';
$heroSubtitle = $site['hero_subtitle'] ?? '';
$contactEmail = $site['contact_email'] ?? 'merhaba@ardiodigital.com';
$social = $site['social'] ?? ['twitter'=>'','instagram'=>'','linkedin'=>'','youtube'=>''];
$gallery = $site['gallery'] ?? [];
?>
<!DOCTYPE html>
<html lang="tr" data-bs-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ARDİO - Gençliğin Teknolojisi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <link href="assets/styles.css" rel="stylesheet" />
</head>
<body class="text-white bg-dark">

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-secondary">
    <div class="container">
      <a class="navbar-brand fw-bold text-primary" href="#">ARDİO</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbars" aria-controls="navbars" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbars">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-lg-2">
          <li class="nav-item"><a href="#features" class="nav-link">Özellikler</a></li>
          <li class="nav-item"><a href="#how" class="nav-link">Nasıl Çalışır?</a></li>
          <li class="nav-item"><a href="#gallery" class="nav-link">Görseller</a></li>
          <li class="nav-item"><a href="#contact" class="nav-link">İletişim</a></li>
          <li class="nav-item ms-2">
            <button class="btn btn-sm btn-outline-info" onclick="toggleTheme()" type="button"><span data-theme-label>Aydınlık</span> Moda Geç</button>
          </li>
          <?php if (isset($_SESSION['user_name'])): ?>
            <?php if (isset($_SESSION['is_admin']) && (int)$_SESSION['is_admin'] === 1): ?>
              <li class="nav-item"><a href="admin.php" class="btn btn-sm btn-outline-warning ms-lg-2">Admin</a></li>
            <?php endif; ?>
            <li class="nav-item"><a href="panel.php" class="btn btn-sm btn-outline-light ms-lg-2">Panel</a></li>
            <li class="nav-item"><a href="logout.php" class="nav-link text-danger">Çıkış</a></li>
          <?php else: ?>
            <li class="nav-item"><a href="login.php" class="btn btn-sm btn-primary ms-lg-2">Giriş</a></li>
            <li class="nav-item"><a href="register.php" class="btn btn-sm btn-outline-light ms-lg-2">Kayıt Ol</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <!-- HERO -->
  <section class="hero-landing py-5">
    <div class="container py-4">
      <div class="row align-items-center g-4">
        <div class="col-lg-7">
          <h1 class="display-5 fw-bold mb-3"><?= htmlspecialchars($heroTitle) ?></h1>
          <p class="lead mb-4 opacity-75"><?= htmlspecialchars($heroSubtitle) ?></p>
          <div class="d-flex flex-wrap gap-2">
            <a href="#how" class="btn btn-primary btn-lg"><i class="bi bi-lightning-charge-fill me-1"></i>Hemen İncele</a>
            <a href="#contact" class="btn btn-outline-light btn-lg"><i class="bi bi-envelope me-1"></i>İletişime Geç</a>
          </div>
          <div class="mt-3 small text-white-50">Gizlilik ve güvenlik önceliğimizdir.</div>
        </div>
        <div class="col-lg-5 text-center">
          <div class="card card-glass p-4">
            <div class="text-center mb-3"><i class="bi bi-smartwatch fs-1 text-info"></i></div>
            <h3 class="h5">Acil Bilgi Kartı</h3>
            <p class="text-white-50">Bileklik üzerindeki NFC/QR ile acil durum kartı anında görüntülenir.</p>
            <ul class="list-unstyled text-start small text-white-50">
              <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i>İlaçlar, alerjiler, kronik hastalıklar</li>
              <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i>Acil iletişim ve adres bilgileri</li>
              <li class="mb-1"><i class="bi bi-check2 text-success me-1"></i>Doktor bilgileri</li>
            </ul>
            <a href="#features" class="btn btn-primary-gradient w-100">Daha Fazla</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FEATURES -->
  <section id="features" class="section-muted py-5">
    <div class="container">
      <div class="text-center mb-4">
        <span class="badge rounded-pill bg-primary-subtle text-primary border">Özellikler</span>
        <h2 class="h2 mt-2">Her Şey Düşünüldü</h2>
        <p class="text-white-50">Kullanıcı dostu, güvenli ve hızlı.</p>
      </div>
      <div class="row g-4">
        <div class="col-md-4">
          <div class="card card-feature h-100 p-3">
            <div class="icon-pill bg-primary-subtle text-primary mb-3"><i class="bi bi-qr-code"></i></div>
            <h3 class="h5">NFC &amp; QR Entegrasyonu</h3>
            <p class="text-white-50 small">Her akıllı telefonla uyumlu; internet bağlantısı olan herkes görebilir.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card card-feature h-100 p-3">
            <div class="icon-pill bg-success-subtle text-success mb-3"><i class="bi bi-shield-lock"></i></div>
            <h3 class="h5">Güvenlik Önceliği</h3>
            <p class="text-white-50 small">HMAC ile imzalanmış bağlantılar ve yetkisiz erişime karşı koruma.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card card-feature h-100 p-3">
            <div class="icon-pill bg-info-subtle text-info mb-3"><i class="bi bi-ui-checks-grid"></i></div>
            <h3 class="h5">Kolay Yönetim</h3>
            <p class="text-white-50 small">Kullanıcı panelinden bilgileri hızla güncelleyin, QR’ı yazdırın.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- HOW IT WORKS -->
  <section id="how" class="py-5">
    <div class="container">
      <div class="text-center mb-4">
        <span class="badge rounded-pill bg-secondary">Nasıl Çalışır?</span>
        <h2 class="h2 mt-2">3 Adımda Güven</h2>
      </div>
      <div class="row g-4">
        <div class="col-md-4">
          <div class="card h-100 bg-black border-0 p-3">
            <div class="icon-pill bg-primary-subtle text-primary mb-3"><i class="bi bi-person-badge"></i></div>
            <h3 class="h6">1. Bilgileri Kaydet</h3>
            <p class="text-white-50 small">Hasta bilgilerini panelden güvenle doldurun.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card h-100 bg-black border-0 p-3">
            <div class="icon-pill bg-info-subtle text-info mb-3"><i class="bi bi-upc-scan"></i></div>
            <h3 class="h6">2. NFC/QR’ı Eşle</h3>
            <p class="text-white-50 small">Üretilen link/QR’ı bilekliğe/etikete koyun.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card h-100 bg-black border-0 p-3">
            <div class="icon-pill bg-success-subtle text-success mb-3"><i class="bi bi-geo"></i></div>
            <h3 class="h6">3. Acil Durumda Erişim</h3>
            <p class="text-white-50 small">QR/NFC okutan kişi acil profile ulaşır.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- GALLERY -->
  <section id="gallery" class="py-5 section-muted">
    <div class="container">
      <div class="text-center mb-4">
        <span class="badge rounded-pill bg-light text-dark">Görseller</span>
        <h2 class="h2 mt-2">Ürün Görselleri</h2>
        <p class="text-white-50">Bileklik, QR etiket ve acil profil ekranından görüntüler.</p>
      </div>
      <?php if (!empty($gallery)): ?>
        <div class="row g-3">
          <?php foreach ($gallery as $img): ?>
            <div class="col-6 col-md-4">
              <img src="<?= htmlspecialchars($img) ?>" class="img-fluid gallery-img" alt="galeri" />
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="text-center text-white-50">Henüz galeri görseli eklenmemiş. Admin panelinden yükleyebilirsiniz.</div>
      <?php endif; ?>
    </div>
  </section>

  <!-- CONTACT -->
  <section id="contact" class="py-5 section-muted">
    <div class="container text-center">
      <h3 class="h2 fw-bold text-primary mb-3">ARDİO ile tanışmak ister misin?</h3>
      <p class="text-white-50 mb-4">Girişimimize destek olmak, ürünü denemek ya da sadece selam vermek için bize yaz.</p>
      <a href="mailto:<?= htmlspecialchars($contactEmail) ?>" class="btn btn-light btn-lg"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($contactEmail) ?></a>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="py-5 bg-dark border-top border-secondary">
    <div class="container">
      <div class="row g-3 align-items-start">
        <div class="col-md-4">
          <div class="fw-bold text-primary mb-2">ARDİO</div>
          <div class="text-white-50 small">Genç zihinler, büyük işler. Acil durumlarda doğru bilgiye anında erişim.</div>
        </div>
        <div class="col-6 col-md-4">
          <div class="fw-semibold mb-2">Hızlı Linkler</div>
          <ul class="list-unstyled small">
            <li><a class="footer-link" href="#features">Özellikler</a></li>
            <li><a class="footer-link" href="#how">Nasıl Çalışır?</a></li>
            <li><a class="footer-link" href="#gallery">Görseller</a></li>
            <li><a class="footer-link" href="#contact">İletişim</a></li>
            <?php if (!isset($_SESSION['user_name'])): ?>
              <li><a class="footer-link" href="login.php">Giriş Yap</a></li>
              <li><a class="footer-link" href="register.php">Kayıt Ol</a></li>
            <?php else: ?>
              <li><a class="footer-link" href="panel.php">Panel</a></li>
              <?php if (isset($_SESSION['is_admin']) && (int)$_SESSION['is_admin'] === 1): ?>
                <li><a class="footer-link" href="admin.php">Admin</a></li>
              <?php endif; ?>
            <?php endif; ?>
          </ul>
        </div>
        <div class="col-6 col-md-4">
          <div class="fw-semibold mb-2">Sosyal</div>
          <div class="d-flex gap-2">
            <?php if (!empty($social['twitter'])): ?><a class="btn btn-outline-light btn-sm" href="<?= htmlspecialchars($social['twitter']) ?>" aria-label="Twitter" target="_blank"><i class="bi bi-twitter"></i></a><?php endif; ?>
            <?php if (!empty($social['instagram'])): ?><a class="btn btn-outline-light btn-sm" href="<?= htmlspecialchars($social['instagram']) ?>" aria-label="Instagram" target="_blank"><i class="bi bi-instagram"></i></a><?php endif; ?>
            <?php if (!empty($social['linkedin'])): ?><a class="btn btn-outline-light btn-sm" href="<?= htmlspecialchars($social['linkedin']) ?>" aria-label="LinkedIn" target="_blank"><i class="bi bi-linkedin"></i></a><?php endif; ?>
            <?php if (!empty($social['youtube'])): ?><a class="btn btn-outline-light btn-sm" href="<?= htmlspecialchars($social['youtube']) ?>" aria-label="YouTube" target="_blank"><i class="bi bi-youtube"></i></a><?php endif; ?>
          </div>
        </div>
      </div>
      <div class="text-center text-white-50 small mt-4">© 2025 ARDİO Teknoloji</div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/theme.js"></script>
</body>
</html>
