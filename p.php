<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lang.php';
setLangFromRequest();

$uid = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
$code = $_GET['code'] ?? '';

if ($uid <= 0 || !$code || !verifyPublicCode($uid, $code)) {
    http_response_code(400);
    echo 'Invalid link';
    exit;
}

function parseNotesRich(?string $notes): array {
    $meta = [
        'emergency_name' => '',
        'emergency_phone' => '',
        'address' => '',
        'doctor_name' => '',
        'doctor_phone' => '',
        'allergies' => '',
        'conditions' => '',
        'extra' => '',
        'gender' => '',
        'height_cm' => '',
        'weight_kg' => '',
        'diagnosis' => '',
        'last_visit' => '',
        'last_doctor' => '',
        'last_med' => '',
        'coords' => '',
    ];
    if (!$notes) return $meta;

    $lines = preg_split("/\r?\n/", $notes);
    foreach ($lines as $line) {
        $t = trim($line);
        if ($t === '') continue;
        // Anahtar:Değer biçimlerini yakala
        $pairs = [
            'Acil İletişim:' => 'emergency',
            'Acil Iletişim:' => 'emergency',
            'Adres:' => 'address',
            'Doktor:' => 'doctor',
            'Alerjiler:' => 'allergies',
            'Kronik Hastalıklar:' => 'conditions',
            'Ek Notlar:' => 'extra',
            'Cinsiyet:' => 'gender',
            'Boy:' => 'height_cm',
            'Kilo:' => 'weight_kg',
            'Tanı:' => 'diagnosis',
            'Tani:' => 'diagnosis',
            'En Son Hastane Ziyareti:' => 'last_visit',
            'Son Hastane:' => 'last_visit',
            'En Son Görüştüğü Doktor:' => 'last_doctor',
            'Son Doktor:' => 'last_doctor',
            'En Son Aldığı İlaç:' => 'last_med',
            'Son İlaç:' => 'last_med',
            'Koordinat:' => 'coords',
        ];
        foreach ($pairs as $prefix => $key) {
            if (stripos($t, $prefix) === 0) {
                $val = trim(substr($t, strlen($prefix)));
                if ($key === 'emergency') {
                    if (preg_match('/^(.*)\((.*)\)$/u', $val, $m)) {
                        $meta['emergency_name'] = trim($m[1]);
                        $meta['emergency_phone'] = trim($m[2]);
                    } else {
                        $meta['emergency_name'] = $val;
                    }
                } elseif ($key === 'doctor') {
                    if (preg_match('/^(.*)\((.*)\)$/u', $val, $m)) {
                        $meta['doctor_name'] = trim($m[1]);
                        $meta['doctor_phone'] = trim($m[2]);
                    } else {
                        $meta['doctor_name'] = $val;
                    }
                } else {
                    $meta[$key] = $val;
                }
                continue 2;
            }
        }
    }
    return $meta;
}

// Hasta verisini getir
$stmt = $pdo->prepare('SELECT p.hasta_adi, p.hasta_dogum, p.hasta_kan, p.hasta_ilac, p.hasta_notlar, u.name as user_name, u.email as user_email FROM patient_info p JOIN users u ON u.id = p.user_id WHERE p.user_id = ?');
$stmt->execute([$uid]);
$patient = $stmt->fetch();
$meta = parseNotesRich($patient['hasta_notlar'] ?? null);

// Yaş hesapla
$ageText = '';
if (!empty($patient['hasta_dogum'])) {
    try {
        $dob = new DateTime($patient['hasta_dogum']);
        $now = new DateTime('today');
        $age = $dob->diff($now)->y;
        $ageText = sprintf('%s (%d yaşında)', htmlspecialchars($patient['hasta_dogum']), (int)$age);
    } catch (Throwable $e) {
        $ageText = htmlspecialchars($patient['hasta_dogum']);
    }
}

// Dinamik linkler
$publicUrl = sprintf('%s://%s%s/p.php?uid=%d&code=%s',
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http',
    $_SERVER['HTTP_HOST'] ?? 'localhost',
    rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/.'),
    $uid,
    $code
);
$qrApi = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . urlencode($publicUrl);

$mapsQuery = $meta['coords'] ?: $meta['address'];
$mapsUrl = $mapsQuery ? ('https://maps.google.com/?q=' . urlencode($mapsQuery)) : '#';
$mapsEmbed = $mapsQuery ? ('https://maps.google.com/maps?q=' . urlencode($mapsQuery) . '&output=embed') : '';
$emPhone = preg_replace('/\D+/', '', $meta['emergency_phone']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARDİO Engelsiz Yaşam - Acil Sağlık Bilgileri</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
            color: #333;
        }
        .card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 8px 18px rgba(0,0,0,0.08);
            padding: 24px;
            max-width: 720px;
            margin: 0 auto;
        }
        .header { text-align: center; margin-bottom: 18px; color: #2c3e50; }
        .header img.logo { width: 72px; height: 72px; object-fit: contain; }
        .subtitle { color: #7f8c8d; margin-top: 4px; font-size: 14px; }
        .qr { border: 1px solid #eee; border-radius: 8px; padding: 6px; background: #fff; }
        .info-section { margin: 16px 0 20px; }
        .section-title { background-color: #3498db; color: white; padding: 8px 14px; border-radius: 6px; font-size: 15px; margin-bottom: 12px; display: inline-block; }
        .info-item { margin-bottom: 10px; border-bottom: 1px dashed #eee; padding-bottom: 8px; display: flex; gap: 12px; }
        .label { font-weight: 600; color: #3498db; width: 40%; min-width: 140px; }
        .value { color: #2c3e50; width: 60%; }
        .emergency { background-color: #ffecec; padding: 14px; border-radius: 8px; margin-top: 16px; border-left: 4px solid #e74c3c; }
        .action-buttons { display: flex; gap: 10px; margin-top: 12px; flex-wrap: wrap; }
        .btn { padding: 10px 14px; border-radius: 6px; border: none; font-weight: 700; cursor: pointer; flex-grow: 1; text-align: center; text-decoration: none; display: inline-block; }
        .btn-call { background-color: #e74c3c; color: white; }
        .btn-alert { background-color: #f39c12; color: white; }
        .btn-location { background-color: #2ecc71; color: white; }
        .map-container { margin-top: 12px; height: 220px; background-color: #eee; border-radius: 8px; overflow: hidden; position: relative; }
        .map-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #7f8c8d; }
        .footer { text-align: center; margin-top: 18px; font-size: 12px; color: #7f8c8d; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <img class="logo" src="https://via.placeholder.com/80x80?text=ARDIO" alt="ARDİO Logo">
            <h2>ARDİO Engelsiz Yaşam</h2>
            <h3>Acil Sağlık Bilgileri</h3>
            <div class="subtitle">Bu sayfa NFC/QR ile görüntülenmiştir.</div>
            <div class="mt-2"><img class="qr" src="<?= $qrApi ?>" alt="QR" width="120" height="120"></div>
        </div>

        <?php if ($patient): ?>
        <div class="info-section">
            <div class="section-title">Temel Bilgiler</div>
            <div class="info-item"><span class="label">Adı Soyadı:</span><span class="value"><?= htmlspecialchars($patient['hasta_adi'] ?: '-') ?></span></div>
            <div class="info-item"><span class="label">Doğum Tarihi:</span><span class="value"><?= $ageText ?: '-' ?></span></div>
            <?php if ($meta['gender']): ?><div class="info-item"><span class="label">Cinsiyet:</span><span class="value"><?= htmlspecialchars($meta['gender']) ?></span></div><?php endif; ?>
            <?php if ($meta['height_cm'] || $meta['weight_kg']): ?>
              <div class="info-item"><span class="label">Boy/Kilo:</span><span class="value"><?= htmlspecialchars(trim(($meta['height_cm']?($meta['height_cm'].' cm'):'') . ($meta['weight_kg']?(' / '.$meta['weight_kg'].' kg'):'') )) ?></span></div>
            <?php endif; ?>
        </div>

        <div class="info-section">
            <div class="section-title">Sağlık Bilgileri</div>
            <div class="info-item"><span class="label">Kan Grubu:</span><span class="value"><?= htmlspecialchars($patient['hasta_kan'] ?: '-') ?></span></div>
            <?php if ($meta['allergies']): ?><div class="info-item"><span class="label">Alerjileri:</span><span class="value"><?= htmlspecialchars($meta['allergies']) ?></span></div><?php endif; ?>
            <?php if (!empty($patient['hasta_ilac'])): ?><div class="info-item"><span class="label">Kullandığı İlaçlar:</span><span class="value"><?= nl2br(htmlspecialchars($patient['hasta_ilac'])) ?></span></div><?php endif; ?>
            <?php if ($meta['diagnosis'] ?: $meta['conditions']): ?><div class="info-item"><span class="label">Hastalığı/Tanısı:</span><span class="value"><?= htmlspecialchars($meta['diagnosis'] ?: $meta['conditions']) ?></span></div><?php endif; ?>
        </div>

        <?php if ($meta['address'] || $mapsEmbed): ?>
        <div class="info-section">
            <div class="section-title">Konum Bilgileri</div>
            <?php if ($meta['address']): ?><div class="info-item"><span class="label">Ev Adresi:</span><span class="value"><?= nl2br(htmlspecialchars($meta['address'])) ?></span></div><?php endif; ?>
            <div class="map-container">
                <?php if ($mapsEmbed): ?>
                    <iframe src="<?= htmlspecialchars($mapsEmbed) ?>" width="100%" height="100%" style="border:0;" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                <?php else: ?>
                    <div class="map-placeholder">[Harita için adres/koordinat bilgisi gereklidir]</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($meta['last_visit'] || $meta['last_doctor'] || $meta['last_med']): ?>
        <div class="info-section">
            <div class="section-title">Son Tıbbi Kayıtlar</div>
            <?php if ($meta['last_visit']): ?><div class="info-item"><span class="label">En Son Hastane Ziyareti:</span><span class="value"><?= htmlspecialchars($meta['last_visit']) ?></span></div><?php endif; ?>
            <?php if ($meta['last_doctor']): ?><div class="info-item"><span class="label">En Son Görüştüğü Doktor:</span><span class="value"><?= htmlspecialchars($meta['last_doctor']) ?></span></div><?php endif; ?>
            <?php if ($meta['last_med']): ?><div class="info-item"><span class="label">En Son Aldığı İlaç:</span><span class="value"><?= htmlspecialchars($meta['last_med']) ?></span></div><?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="emergency">
            <div class="section-title">ACİL DURUM</div>
            <div class="info-item">
                <span class="label">Acil Durum İletişim:</span>
                <span class="value">
                    <?php if ($meta['emergency_name'] || $meta['emergency_phone']): ?>
                        <div><?= htmlspecialchars(trim($meta['emergency_name'])) ?><?= $meta['emergency_phone']?' - '.htmlspecialchars($meta['emergency_phone']):'' ?></div>
                    <?php else: ?>
                        <div>Sahibi: <?= htmlspecialchars($patient['user_name']) ?> - <a href="mailto:<?= htmlspecialchars($patient['user_email']) ?>"><?= htmlspecialchars($patient['user_email']) ?></a></div>
                    <?php endif; ?>
                </span>
            </div>

            <div class="action-buttons">
                <a href="tel:112" class="btn btn-call">112'yi Ara</a>
                <?php if ($emPhone): ?><a href="tel:<?= htmlspecialchars($emPhone) ?>" class="btn btn-alert">Yakınına Bildirim Gönder</a><?php endif; ?>
                <?php if ($mapsUrl && $mapsUrl !== '#'): ?><a href="<?= htmlspecialchars($mapsUrl) ?>" class="btn btn-location" target="_blank">Konumu Göster</a><?php endif; ?>
            </div>
        </div>

        <div class="footer">
            <p>Bu bilgiler ARDİO Engelsiz Yaşam NFC bilekliğinde saklanmaktadır.</p>
            <p>Son Güncelleme: <?= date('d/m/Y H:i') ?></p>
            <?php if ($meta['coords']): ?><p>Konum: <?= htmlspecialchars($meta['coords']) ?></p><?php endif; ?>
            <p><a href="index.php" style="text-decoration:none;color:#3498db;">Ana Sayfa</a> · <a href="<?= htmlspecialchars($publicUrl) ?>" style="text-decoration:none;color:#3498db;">Bağlantı</a></p>
        </div>
        <?php else: ?>
            <div class="info-section"><div class="section-title">Kayıt bulunamadı</div></div>
        <?php endif; ?>
    </div>
</body>
</html>