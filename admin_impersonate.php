<?php
require_once __DIR__ . '/config.php';
requireAdmin();

if (!isset($_GET['id'])) {
    header('Location: admin.php');
    exit;
}

$targetId = (int)$_GET['id'];

$stmt = $pdo->prepare('SELECT id, name, is_admin FROM users WHERE id = ?');
$stmt->execute([$targetId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: admin.php');
    exit;
}

// Mevcut admin için impersonate flag saklayalım (geri dönüş için kullanılabilir)
$_SESSION['impersonator_admin_id'] = $_SESSION['user_id'];

// Hedef kullanıcı olarak oturum aç
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['is_admin'] = (int)$user['is_admin'];

header('Location: panel.php');
exit;