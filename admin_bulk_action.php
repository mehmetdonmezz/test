<?php
require_once __DIR__ . '/config.php';
requireAdmin();

$action = $_POST['action'] ?? '';
$ids = $_POST['ids'] ?? [];
if (!is_array($ids)) $ids = [];
$ids = array_map('intval', $ids);
$ids = array_values(array_unique(array_filter($ids, fn($v)=>$v>0)));

if (!$action || empty($ids)) {
    header('Location: admin.php');
    exit;
}

// Kendi hesabını silme/rol değiştirme koruması
$selfId = (int)$_SESSION['user_id'];

$pdo->beginTransaction();
try {
    if ($action === 'delete') {
        // Implicit: patient_info sil
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM patient_info WHERE user_id IN ($in)");
        $stmt->execute($ids);
        // Kendi idsini dışarıda bırak
        $idsNoSelf = array_values(array_filter($ids, fn($id)=>$id !== $selfId));
        if (!empty($idsNoSelf)) {
            $in2 = implode(',', array_fill(0, count($idsNoSelf), '?'));
            $stmt2 = $pdo->prepare("DELETE FROM users WHERE id IN ($in2)");
            $stmt2->execute($idsNoSelf);
        }
    } elseif ($action === 'make_admin') {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id IN ($in)");
        $stmt->execute($ids);
    } elseif ($action === 'make_user') {
        // Kendinin adminliğini kaldıramasın
        $idsNoSelf = array_values(array_filter($ids, fn($id)=>$id !== $selfId));
        if (!empty($idsNoSelf)) {
            $in = implode(',', array_fill(0, count($idsNoSelf), '?'));
            $stmt = $pdo->prepare("UPDATE users SET is_admin = 0 WHERE id IN ($in)");
            $stmt->execute($idsNoSelf);
        }
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
}

header('Location: admin.php');
exit;