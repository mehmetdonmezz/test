<?php
// Ortak yapılandırma ve PDO bağlantısı
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'hasta_sistemi';
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