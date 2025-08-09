<?php
session_start();
?>
<!DOCTYPE html>
<html lang="tr" data-bs-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ARDİO - Gençliğin Teknolojisi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .hero { background: linear-gradient(135deg, #000000 0%, #0f172a 50%, #0b3b8c 100%); }
  </style>
</head>
<body class="text-white bg-dark">

  <!-- HEADER -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-secondary">
    <div class="container">
      <a class="navbar-brand fw-bold text-primary" href="#">ARDİO</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbars" aria-controls="navbars" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbars">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
          <li class="nav-item"><a href="#about" class="nav-link">Hakkında</a></li>
          <li class="nav-item"><a href="#features" class="nav-link">Özellikler</a></li>
          <li class="nav-item"><a href="#faq" class="nav-link">SSS</a></li>
          <li class="nav-item"><a href="#contact" class="nav-link">İletişim</a></li>
          <li class="nav-item ms-2">
            <button class="btn btn-sm btn-outline-info" onclick="toggleTheme()" type="button"><span data-theme-label>Aydınlık</span> Moda Geç</button>
          </li>
          <?php if (isset($_SESSION['user_name'])): ?>
            <li class="nav-item ms-3 d-flex align-items-center text-white-50">Hoşgeldin, <strong class="ms-1 text-white"><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></li>
            <li class="nav-item"><a href="logout.php" class="nav-link text-danger">Çıkış</a></li>
          <?php else: ?>
            <li class="nav-item"><a href="login.php" class="nav-link text-success">Giriş</a></li>
            <li class="nav-item"><a href="register.php" class="nav-link text-success">Kayıt Ol</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <!-- HERO -->
  <section class="hero text-center py-5">
    <div class="container py-4">
      <h2 class="display-5 fw-bold text-info mb-3">Genç Zihinlerden, Hayat Kurtaran Teknoloji</h2>
      <p class="lead text-light">ARDİO ile kaybolmak yok. Endişe yok. Sadece YAŞAM var.</p>
      <a href="#contact" class="btn btn-primary btn-lg mt-3">Bize Katıl</a>
    </div>
  </section>

  <!-- ABOUT -->
  <section id="about" class="py-5 bg-dark">
    <div class="container text-center">
      <h3 class="h2 fw-bold text-primary mb-3">ARDİO nedir?</h3>
      <p class="text-white-50 fs-5">ARDİO, 17 yaşında bir girişimcinin hayaliyle doğan, gerçek ihtiyaçlara cevap veren akıllı bir bileklik teknolojisidir. Alzheimer ve zihinsel engelli bireyler için geliştirilmiştir. NFC ve QR ile acil bilgilere anında erişim sağlar.</p>
    </div>
  </section>

  <!-- FEATURES -->
  <section id="features" class="py-5 bg-black">
    <div class="container">
      <div class="row g-4 text-center">
        <div class="col-md-4">
          <div class="card bg-primary text-white h-100">
            <div class="card-body">
              <h4 class="card-title">Anında Tanıma</h4>
              <p class="card-text">QR ve NFC ile saniyeler içinde kişi bilgilerine erişim.</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card bg-info text-white h-100">
            <div class="card-body">
              <h4 class="card-title">Hayati Veriler</h4>
              <p class="card-text">Kan grubu, ilaçlar, hastane kayıtları, yakın bilgileri.</p>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card bg-success text-white h-100">
            <div class="card-body">
              <h4 class="card-title">Genç Ruh, Gerçek Teknoloji</h4>
              <p class="card-text">Yenilikçi bir vizyon, sosyal faydayla buluşuyor.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ -->
  <section id="faq" class="py-5 bg-dark">
    <div class="container">
      <h3 class="h3 fw-bold text-info mb-4">Sık Sorulan Sorular</h3>
      <div class="accordion" id="faqAccordion">
        <div class="accordion-item bg-secondary text-white">
          <h2 class="accordion-header" id="q1">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a1">Bilgiler güvenli mi?</button>
          </h2>
          <div id="a1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body">Evet, bilgiler sadece QR/NFC okutulduğunda görülür ve şifreli sunucularda saklanır.</div>
          </div>
        </div>
        <div class="accordion-item bg-secondary text-white mt-2">
          <h2 class="accordion-header" id="q2">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a2">Cihazı kimler kullanabilir?</button>
          </h2>
          <div id="a2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
            <div class="accordion-body">Alzheimer hastaları, zihinsel engelliler ve yaşlı bireyler için uygundur.</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CONTACT -->
  <section id="contact" class="py-5 bg-primary text-center">
    <div class="container">
      <h3 class="h2 fw-bold text-white mb-3">ARDİO ile tanışmak ister misin?</h3>
      <p class="text-white-75 mb-4">Girişimimize destek olmak, ürünü denemek ya da sadece selam vermek için bize yaz.</p>
      <a href="mailto:merhaba@ardiodigital.com" class="btn btn-light btn-lg">merhaba@ardiodigital.com</a>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="text-center py-4 text-white-50 bg-dark border-top border-secondary">
    Genç zihinler, büyük işler: © 2025 ARDİO Teknoloji.
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/theme.js"></script>
</body>
</html>
