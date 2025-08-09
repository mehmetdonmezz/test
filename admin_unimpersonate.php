<?php
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['impersonator_admin_id'])) {
    header('Location: index.php');
    exit;
}

$adminId = (int)$_SESSION['impersonator_admin_id'];
unset($_SESSION['impersonator_admin_id']);

$stmt = $pdo->prepare('SELECT id, name, is_admin FROM users WHERE id = ?');
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

if (!$admin || (int)$admin['is_admin'] !== 1) {
    // Güvence: admin yoksa normal çıkış
    header('Location: logout.php');
    exit;
}

$_SESSION['user_id'] = (int)$admin['id'];
$_SESSION['user_name'] = $admin['name'];
$_SESSION['is_admin'] = 1;

header('Location: admin.php');
exit;