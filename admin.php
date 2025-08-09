<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "147369", "hasta_sistemi");
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}

// Admin kontrolü
$user_id = $_SESSION["user_id"];
$stmtAdmin = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmtAdmin->bind_param("i", $user_id);
$stmtAdmin->execute();
$resultAdmin = $stmtAdmin->get_result();
$userAdmin = $resultAdmin->fetch_assoc();
$stmtAdmin->close();

if (!$userAdmin || $userAdmin['is_admin'] != 1) {
    echo "Bu sayfaya erişim yetkiniz yok.";
    exit;
}

// Kullanıcı + hasta bilgilerini çek
$sql = "
SELECT u.id as user_id, u.name, u.email, u.is_admin, 
       p.hasta_adi, p.hasta_dogum, p.hasta_kan, p.hasta_ilac, p.hasta_notlar
FROM users u
LEFT JOIN patient_info p ON u.id = p.user_id
ORDER BY u.id ASC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8" />
<title>Admin Paneli - Hasta ve Kullanıcı Yönetimi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">

<div class="container mt-5">
    <h1>Admin Paneli</h1>
    <p><a href="logout.php" class="btn btn-danger btn-sm">Çıkış Yap</a></p>

    <table class="table table-bordered table-striped">
        <thead class="table-primary">
            <tr>
                <th>Kullanıcı ID</th>
                <th>Ad Soyad</th>
                <th>E-posta</th>
                <th>Admin</th>
                <th>Hasta Adı</th>
                <th>Doğum Tarihi</th>
                <th>Kan Grubu</th>
                <th>İlaçlar</th>
                <th>Notlar</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['user_id']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= $row['is_admin'] ? 'Evet' : 'Hayır' ?></td>
                <td><?= htmlspecialchars($row['hasta_adi'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['hasta_dogum'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['hasta_kan'] ?? '-') ?></td>
                <td><?= nl2br(htmlspecialchars($row['hasta_ilac'] ?? '-')) ?></td>
                <td><?= nl2br(htmlspecialchars($row['hasta_notlar'] ?? '-')) ?></td>
                <td>
                    <a href="admin_edit_user.php?id=<?= $row['user_id'] ?>" class="btn btn-sm btn-warning">Düzenle</a>
                    <a href="admin_delete_user.php?id=<?= $row['user_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Kullanıcıyı silmek istediğinize emin misiniz?')">Sil</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>

<?php
$conn->close();
?>
