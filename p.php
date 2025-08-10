<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lang.php';
setLangFromRequest();

$uid = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
$pid = isset($_GET['pid']) ? (int)$_GET['pid'] : 0; // çoklu profil desteği
$code = $_GET['code'] ?? '';

// Doğrulama: öncelik pid
if ($pid > 0) {
    if (!$code || !verifyPublicCodeForPatient($pid, $code)) {
        http_response_code(400);
        echo 'Invalid link';
        exit;
    }
} else {
    if ($uid <= 0 || !$code || !verifyPublicCode($uid, $code)) {
        http_response_code(400);
        echo 'Invalid link';
        exit;
    }
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
if ($pid > 0) {
    $stmt = $pdo->prepare('SELECT p.id as pid, p.user_id, p.hasta_adi, p.hasta_dogum, p.hasta_kan, p.hasta_ilac, p.hasta_notlar, u.name as user_name, u.email as user_email FROM patient_info p JOIN users u ON u.id = p.user_id WHERE p.id = ?');
    $stmt->execute([$pid]);
} else {
    $stmt = $pdo->prepare('SELECT p.id as pid, p.user_id, p.hasta_adi, p.hasta_dogum, p.hasta_kan, p.hasta_ilac, p.hasta_notlar, u.name as user_name, u.email as user_email FROM patient_info p JOIN users u ON u.id = p.user_id WHERE p.user_id = ? ORDER BY p.id ASC LIMIT 1');
    $stmt->execute([$uid]);
}
$patient = $stmt->fetch();
$meta = parseNotesRich($patient['hasta_notlar'] ?? null);

// Görüntüleme logu
if ($patient && !empty($patient['pid'])) {
    try {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        if ($ip && strpos($ip, ',') !== false) { $ip = trim(explode(',', $ip)[0]); }
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250);
        $ins = $pdo->prepare('INSERT INTO profile_views (patient_id, ip, ua) VALUES (?, ?, ?)');
        $ins->execute([(int)$patient['pid'], $ip, $ua]);
    } catch (Throwable $e) {
        // ignore
    }
}

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
if (!empty($patient['pid'])) {
    $publicUrl = sprintf('%s://%s%s/p.php?pid=%d&code=%s',
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http',
        $_SERVER['HTTP_HOST'] ?? 'localhost',
        rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/.'),
        (int)$patient['pid'],
        makePublicCodeForPatient((int)$patient['pid'])
    );
} else {
    $publicUrl = sprintf('%s://%s%s/p.php?uid=%d&code=%s',
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http',
        $_SERVER['HTTP_HOST'] ?? 'localhost',
        rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/.'),
        $uid,
        $code
    );
}
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
        :root {
            --primary: #2f80ed;
            --danger: #e74c3c;
            --warning: #f39c12;
            --success: #2ecc71;
            --text: #2c3e50;
            --muted: #7f8c8d;
            --paper: #ffffff;
            --bg: #f4f6f8;
            --border: #e6e9ef;
        }
        html, body { height: 100%; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 20px 16px;
        }
        .page {
            max-width: 640px;
            margin: 0 auto;
        }
        .card {
            background-color: var(--paper);
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
            padding: 20px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text);
            margin-bottom: 8px;
        }
        .brand .logo {
            width: 48px; height: 48px;
            border-radius: 12px;
            background: #eef4ff;
            display: grid; place-items: center;
            color: var(--primary);
            font-weight: 800;
        }
        .subtitle { color: var(--muted); font-size: 13px; }
        .header-row {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 16px;
            margin-top: 8px;
        }
        .person h2 { font-size: 22px; margin: 0 0 8px; color: var(--text); }
        .chips { display: flex; flex-wrap: wrap; gap: 8px; }
        .chip { font-size: 12px; padding: 6px 10px; border-radius: 999px; border: 1px solid var(--border); background: #fafbff; color: #334155; }
        .qr { border: 1px solid var(--border); border-radius: 10px; padding: 6px; background: #fff; }

        .section { margin-top: 18px; }
        .section-title {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--primary); color: #fff; padding: 8px 12px; border-radius: 8px;
            font-size: 14px; font-weight: 600; margin-bottom: 10px;
        }
        .info-list { background: var(--paper); border: 1px solid var(--border); border-radius: 10px; }
        .info-item {
            display: grid; gap: 8px; padding: 12px 14px; border-bottom: 1px dashed var(--border);
            grid-template-columns: 1fr;
        }
        .info-item:last-child { border-bottom: none; }
        .label { font-weight: 600; color: var(--primary); }
        .value { color: var(--text); word-wrap: break-word; overflow-wrap: anywhere; }

        @media (min-width: 540px) {
            .info-item { grid-template-columns: 180px 1fr; align-items: start; }
            .label { text-align: right; padding-right: 10px; }
        }

        .map-box { margin-top: 10px; border: 1px solid var(--border); border-radius: 10px; overflow: hidden; background: #f0f2f5; }
        .map-aspect { aspect-ratio: 16 / 9; width: 100%; display: block; border: 0; }

        .emergency { background: #fff5f5; border: 1px solid #ffd1d1; border-left: 4px solid var(--danger); border-radius: 10px; padding: 14px; }
        .actions { display: grid; grid-template-columns: 1fr; gap: 10px; margin-top: 12px; }
        @media (min-width: 520px) { .actions { grid-template-columns: 1fr 1fr 1fr; } }
        .btn { display: inline-block; text-align: center; font-weight: 700; padding: 10px 12px; border-radius: 10px; text-decoration: none; }
        .btn-call { background: var(--danger); color: #fff; }
        .btn-alert { background: var(--warning); color: #fff; }
        .btn-location { background: var(--success); color: #fff; }

        .footer { text-align: center; margin-top: 16px; font-size: 12px; color: var(--muted); }
        .footer a { color: var(--primary); text-decoration: none; }
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <div class="brand">
                <div class="logo">A</div>
                <div>
                    <div style="font-weight:700;">ARDİO Engelsiz Yaşam</div>
                    <div class="subtitle">Acil Sağlık Bilgileri · NFC/QR ile görüntülendi</div>
                </div>
            </div>

            <div class="header-row">
                <div class="person">
                    <h2><?= htmlspecialchars($patient['hasta_adi'] ?: 'Bilinmiyor') ?></h2>
                    <div class="chips">
                        <?php if ($ageText): ?><span class="chip"><?= $ageText ?></span><?php endif; ?>
                        <?php if (!empty($patient['hasta_kan'])): ?><span class="chip">Kan Grubu: <?= htmlspecialchars($patient['hasta_kan']) ?></span><?php endif; ?>
                    </div>
                </div>
                <div class="qr-wrap">
                    <img class="qr" src="<?= $qrApi ?>" alt="QR" width="120" height="120" />
                </div>
            </div>

            <?php if ($patient): ?>
            <div class="section">
                <div class="section-title">Temel Bilgiler</div>
                <div class="info-list">
                    <div class="info-item"><div class="label">Adı Soyadı</div><div class="value"><?= htmlspecialchars($patient['hasta_adi'] ?: '-') ?></div></div>
                    <div class="info-item"><div class="label">Doğum Tarihi</div><div class="value"><?= $ageText ?: '-' ?></div></div>
                    <?php if ($meta['gender']): ?><div class="info-item"><div class="label">Cinsiyet</div><div class="value"><?= htmlspecialchars($meta['gender']) ?></div></div><?php endif; ?>
                    <?php if ($meta['height_cm'] || $meta['weight_kg']): ?>
                        <div class="info-item"><div class="label">Boy/Kilo</div><div class="value"><?= htmlspecialchars(trim(($meta['height_cm']?($meta['height_cm'].' cm'):'') . ($meta['weight_kg']?(' / '.$meta['weight_kg'].' kg'):'') )) ?></div></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Sağlık Bilgileri</div>
                <div class="info-list">
                    <div class="info-item"><div class="label">Kan Grubu</div><div class="value"><?= htmlspecialchars($patient['hasta_kan'] ?: '-') ?></div></div>
                    <?php if ($meta['allergies']): ?><div class="info-item"><div class="label">Alerjileri</div><div class="value"><?= htmlspecialchars($meta['allergies']) ?></div></div><?php endif; ?>
                    <?php if (!empty($patient['hasta_ilac'])): ?><div class="info-item"><div class="label">Kullandığı İlaçlar</div><div class="value"><?= nl2br(htmlspecialchars($patient['hasta_ilac'])) ?></div></div><?php endif; ?>
                    <?php if ($meta['diagnosis'] ?: $meta['conditions']): ?><div class="info-item"><div class="label">Hastalığı/Tanısı</div><div class="value"><?= htmlspecialchars($meta['diagnosis'] ?: $meta['conditions']) ?></div></div><?php endif; ?>
                </div>
            </div>

            <?php if ($meta['address'] || $mapsEmbed): ?>
            <div class="section">
                <div class="section-title">Konum Bilgileri</div>
                <div class="info-list">
                    <?php if ($meta['address']): ?><div class="info-item"><div class="label">Ev Adresi</div><div class="value"><?= nl2br(htmlspecialchars($meta['address'])) ?></div></div><?php endif; ?>
                </div>
                <div class="map-box">
                    <?php if ($mapsEmbed): ?>
                        <iframe class="map-aspect" src="<?= htmlspecialchars($mapsEmbed) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($meta['last_visit'] || $meta['last_doctor'] || $meta['last_med']): ?>
            <div class="section">
                <div class="section-title">Son Tıbbi Kayıtlar</div>
                <div class="info-list">
                    <?php if ($meta['last_visit']): ?><div class="info-item"><div class="label">En Son Hastane Ziyareti</div><div class="value"><?= htmlspecialchars($meta['last_visit']) ?></div></div><?php endif; ?>
                    <?php if ($meta['last_doctor']): ?><div class="info-item"><div class="label">En Son Görüştüğü Doktor</div><div class="value"><?= htmlspecialchars($meta['last_doctor']) ?></div></div><?php endif; ?>
                    <?php if ($meta['last_med']): ?><div class="info-item"><div class="label">En Son Aldığı İlaç</div><div class="value"><?= htmlspecialchars($meta['last_med']) ?></div></div><?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="section">
                <div class="section-title">Acil Durum</div>
                <div class="emergency">
                    <div class="info-item" style="border:none; padding:0;">
                        <div class="label">Acil İletişim</div>
                        <div class="value">
                            <?php if ($meta['emergency_name'] || $meta['emergency_phone']): ?>
                                <div><?= htmlspecialchars(trim($meta['emergency_name'])) ?><?= $meta['emergency_phone']?' - '.htmlspecialchars($meta['emergency_phone']):'' ?></div>
                            <?php else: ?>
                                <div>Sahibi: <?= htmlspecialchars($patient['user_name']) ?> - <a href="mailto:<?= htmlspecialchars($patient['user_email']) ?>"><?= htmlspecialchars($patient['user_email']) ?></a></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="actions">
                        <a href="tel:112" class="btn btn-call">112'yi Ara</a>
                        <?php if ($emPhone): ?><a href="tel:<?= htmlspecialchars($emPhone) ?>" class="btn btn-alert">Yakınına Bildirim Gönder</a><?php endif; ?>
                        <?php if ($mapsUrl && $mapsUrl !== '#'): ?><a href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" class="btn btn-location">Konumu Göster</a><?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="footer">
                <div>Bu bilgiler ARDİO Engelsiz Yaşam NFC bilekliğinde saklanmaktadır.</div>
                <div>Son Güncelleme: <?= date('d/m/Y H:i') ?><?= $meta['coords'] ? ' · Konum: ' . htmlspecialchars($meta['coords']) : '' ?></div>
                <div><a href="index.php">Ana Sayfa</a> · <a href="<?= htmlspecialchars($publicUrl) ?>">Bağlantı</a></div>
            </div>
            <?php else: ?>
                <div class="section"><div class="section-title">Kayıt bulunamadı</div></div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>