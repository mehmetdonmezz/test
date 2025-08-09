<?php
session_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ARDİO - Gençliğin Teknolojisi</title>
  <script src="3.4.16"></script>
  <link href="css2?family=Orbitron:wght@500&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Orbitron', sans-serif;
    }
  </style>
</head>
<body class="bg-gradient-to-br from-black via-gray-900 to-blue-900 text-white">

  <!-- HEADER -->
  <header class="p-6 flex justify-between items-center border-b border-gray-700">
    <h1 class="text-3xl font-bold text-blue-400">ARDİO</h1>
    <nav class="space-x-4 text-sm flex items-center">
      <a href="#about" class="hover:text-blue-300">Hakkında</a>
      <a href="#features" class="hover:text-blue-300">Özellikler</a>
      <a href="#faq" class="hover:text-blue-300">SSS</a>
      <a href="#contact" class="hover:text-blue-300">İletişim</a>

      <?php if (isset($_SESSION['user_name'])): ?>
        <span class="ml-6">Hoşgeldin, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
        <a href="logout.php" class="hover:text-red-400 ml-4">Çıkış</a>
      <?php else: ?>
        <a href="login.php" class="hover:text-green-400 ml-6">Giriş</a>
        <a href="register.php" class="hover:text-green-400 ml-4">Kayıt Ol</a>
      <?php endif; ?>
    </nav>
  </header>

  <!-- HERO -->
  <section class="text-center py-24 px-6">
    <h2 class="text-5xl md:text-6xl font-extrabold text-blue-400 mb-4">Genç Zihinlerden, Hayat Kurtaran Teknoloji</h2>
    <p class="text-xl text-gray-300 mb-6">ARDİO ile kaybolmak yok. Endişe yok. Sadece YAŞAM var.</p>
    <a href="#contact" class="bg-blue-500 text-white font-semibold px-8 py-3 rounded-full hover:bg-blue-600 transition">Bize Katıl</a>
  </section>

  <!-- ABOUT -->
  <section id="about" class="py-20 px-6 max-w-5xl mx-auto text-center">
    <h3 class="text-3xl font-bold text-blue-400 mb-4">ARDİO nedir?</h3>
    <p class="text-gray-300 text-lg">ARDİO, 17 yaşında bir girişimcinin hayaliyle doğan, gerçek ihtiyaçlara cevap veren akıllı bir bileklik teknolojisidir. Alzheimer ve zihinsel engelli bireyler için geliştirilmiştir. NFC ve QR ile acil bilgilere anında erişim sağlar.</p>
  </section>

  <!-- FEATURES -->
  <section id="features" class="bg-gray-900 py-20 px-6">
    <div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-8 text-center">
      <div class="bg-blue-800 p-6 rounded-lg shadow-xl">
        <h4 class="text-xl font-bold mb-2">Anında Tanıma</h4>
        <p>QR ve NFC ile saniyeler içinde kişi bilgilerine erişim.</p>
      </div>
      <div class="bg-blue-700 p-6 rounded-lg shadow-xl">
        <h4 class="text-xl font-bold mb-2">Hayati Veriler</h4>
        <p>Kan grubu, ilaçlar, hastane kayıtları, yakın bilgileri.</p>
      </div>
      <div class="bg-blue-600 p-6 rounded-lg shadow-xl">
        <h4 class="text-xl font-bold mb-2">Genç Ruh, Gerçek Teknoloji</h4>
        <p>Yenilikçi bir vizyon, sosyal faydayla buluşuyor.</p>
      </div>
    </div>
  </section>

  <!-- FAQ -->
  <section id="faq" class="py-20 px-6 max-w-4xl mx-auto">
    <h3 class="text-2xl font-bold text-blue-300 mb-6">Sık Sorulan Sorular</h3>
    <div class="space-y-6">
      <div>
        <h5 class="font-semibold">Bilgiler güvenli mi?</h5>
        <p class="text-gray-400">Evet, bilgiler sadece QR/NFC okutulduğunda görülür ve şifreli sunucularda saklanır.</p>
      </div>
      <div>
        <h5 class="font-semibold">Cihazı kimler kullanabilir?</h5>
        <p class="text-gray-400">Alzheimer hastaları, zihinsel engelliler ve yaşlı bireyler için uygundur.</p>
      </div>
    </div>
  </section>

  <!-- CONTACT -->
  <section id="contact" class="bg-blue-900 py-20 text-center px-6">
    <h3 class="text-3xl font-bold text-white mb-4">ARDİO ile tanışmak ister misin?</h3>
    <p class="text-gray-300 mb-6">Girişimimize destek olmak, ürünü denemek ya da sadece selam vermek için bize yaz.</p>
    <a href="mailto:merhaba@ardiodigital.com" class="bg-white text-blue-700 font-bold px-6 py-3 rounded-full hover:bg-gray-100">merhaba@ardiodigital.com</a>
  </section>

  <!-- FOOTER -->
  <footer class="text-center py-6 text-gray-400 text-sm">
    Genç zihinler, büyük işler: © 2025 ARDİO Teknoloji.
  </footer>

</body>
</html>
