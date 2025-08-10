<?php
// Ortak yapılandırma ve PDO bağlantısı
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'u378682624_arda';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '147369';

$pdo = new PDO("mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4", $DB_USER, $DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

function isAuthenticated(): bool {
    return isset($_SESSION['user_id']);
}

function requireAuth(): void {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

function isAdmin(): bool {
    return isset($_SESSION['is_admin']) && intval($_SESSION['is_admin']) === 1;
}

function requireAdmin(): void {
    if (!isAuthenticated() || !isAdmin()) {
        http_response_code(403);
        echo 'Bu sayfaya erişim yetkiniz yok.';
        exit;
    }
}

// Public NFC/QR profil bağlantısı için HMAC tabanlı kod
$PUBLIC_LINK_SECRET = getenv('PUBLIC_LINK_SECRET') ?: 'change-this-secret-please-ardio';

function makePublicCode(int $userId): string {
    global $PUBLIC_LINK_SECRET;
    return hash_hmac('sha256', (string)$userId, $PUBLIC_LINK_SECRET);
}

function verifyPublicCode(int $userId, string $code): bool {
    $expected = makePublicCode($userId);
    return hash_equals($expected, $code);
}

// Hasta (patient_info.id) bazlı kod üretimi
function makePublicCodeForPatient(int $patientId): string {
    global $PUBLIC_LINK_SECRET;
    return hash_hmac('sha256', 'pid:' . (string)$patientId, $PUBLIC_LINK_SECRET);
}

function verifyPublicCodeForPatient(int $patientId, string $code): bool {
    $expected = makePublicCodeForPatient($patientId);
    return hash_equals($expected, $code);
}

// Site ayarları (JSON tabanlı basit storage)
function siteSettingsPath(): string {
    $path = __DIR__ . '/assets/site.json';
    $dir = dirname($path);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    return $path;
}

function getSiteSettings(): array {
    $path = siteSettingsPath();
    if (!file_exists($path)) {
        $defaults = [
            'hero_title' => 'Kaybolmayı İmkânsız Kılan Bileklik',
            'hero_subtitle' => 'ARDİO, Alzheimer ve zihinsel engelli bireyler için akıllı bileklik ve acil bilgi platformu.',
            'hero_title_en' => 'The Bracelet That Makes Getting Lost Impossible',
            'hero_subtitle_en' => "ARDİO is a smart bracelet and emergency info platform for Alzheimer’s and cognitively impaired individuals.",
            'contact_email' => 'merhaba@ardiodigital.com',
            'social' => [
                'twitter' => '', 'instagram' => '', 'linkedin' => '', 'youtube' => ''
            ],
            'gallery' => []
        ];
        file_put_contents($path, json_encode($defaults, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        return $defaults;
    }
    $json = json_decode(file_get_contents($path), true);
    return is_array($json) ? $json : [];
}

function saveSiteSettings(array $settings): bool {
    $path = siteSettingsPath();
    $json = json_encode($settings, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    return (bool)file_put_contents($path, $json, LOCK_EX);
}

// Ek tabloları oluştur
function ensureExtraTables(PDO $pdo): void {
    // Profil görüntüleme logları
    $pdo->exec("CREATE TABLE IF NOT EXISTS profile_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        ip VARCHAR(64) DEFAULT NULL,
        ua VARCHAR(255) DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (patient_id),
        INDEX (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Kullanıcı onayları (sözleşme/gizlilik)
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_consents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        terms_accepted_at DATETIME DEFAULT NULL,
        privacy_accepted_at DATETIME DEFAULT NULL,
        ip VARCHAR(64) DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Profil etkinlikleri (çağrı, konum açma, yazdırma vb.)
    $pdo->exec("CREATE TABLE IF NOT EXISTS profile_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        event VARCHAR(32) NOT NULL,
        meta VARCHAR(255) DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX(patient_id), INDEX(event), INDEX(created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

ensureExtraTables($pdo);

// Çoklu dil desteği için başlangıç
require_once __DIR__ . '/lang.php';