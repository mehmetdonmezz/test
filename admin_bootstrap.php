<?php
require_once __DIR__ . '/config.php';
requireAuth();

// Eğer sistemde hiç admin yoksa, mevcut kullanıcıyı admin yap
$hasAdmin = (int)$pdo->query("SELECT COUNT(*) AS c FROM users WHERE is_admin = 1")->fetch()['c'];
if ($hasAdmin > 0) {
    http_response_code(403);
    echo 'Zaten bir admin kullanıcı mevcut.';
    exit;
}

$userId = (int)$_SESSION['user_id'];
$pdo->prepare('UPDATE users SET is_admin = 1 WHERE id = ?')->execute([$userId]);

header('Location: admin.php');
exit;