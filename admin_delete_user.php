<?php
require_once __DIR__ . '/config.php';
requireAdmin();

if (!isset($_GET["id"])) {
    header("Location: admin.php");
    exit;
}

$user_id = (int)($_GET["id"]);

// Kendini silmesini engelle
if ($user_id === (int)$_SESSION["user_id"]) {
    header("Location: admin.php");
    exit;
}

// İlişkili kayıtları sil ve kullanıcıyı kaldır
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("DELETE FROM patient_info WHERE user_id = ?");
    $stmt->execute([$user_id]);

    $stmt2 = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt2->execute([$user_id]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
}

header("Location: admin.php");
exit;
