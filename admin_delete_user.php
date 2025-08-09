<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["is_admin"] != 1) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET["id"])) {
    header("Location: admin.php");
    exit;
}

$conn = new mysqli("localhost", "root", "147369", "hasta_sistemi");
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}

$user_id = intval($_GET["id"]);

// Kendini silmesini engelle
if ($user_id == $_SESSION["user_id"]) {
    header("Location: admin.php");
    exit;
}

$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

$conn->close();

header("Location: admin.php");
exit;
