<?php
require_once __DIR__ . '/config.php';

$pid = (int)($_GET['pid'] ?? 0);
$code = $_GET['code'] ?? '';

if ($pid <= 0 || !$code || !verifyPublicCodeForPatient($pid, $code)) {
    http_response_code(204);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$event = isset($data['event']) ? substr(preg_replace('/[^a-zA-Z0-9_\-]/','',$data['event']), 0, 32) : '';
$meta = isset($data['meta']) ? substr((string)$data['meta'], 0, 255) : null;

if ($event) {
    $stmt = $pdo->prepare('INSERT INTO profile_events (patient_id, event, meta) VALUES (?, ?, ?)');
    $stmt->execute([$pid, $event, $meta]);
}

http_response_code(204);