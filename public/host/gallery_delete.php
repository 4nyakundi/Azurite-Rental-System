<?php
// public/host/gallery_delete.php
require_once "../../includes/db.php";
require_once "../../includes/helpers.php";

if (!is_host()) {
    die("Unauthorized");
}

$host = current_user();
$photoId = (int)($_GET['id'] ?? 0);
$roomId  = (int)($_GET['room_id'] ?? 0);

// Find photo + room owner
$stmt = $pdo->prepare("
    SELECT p.image_path, r.owner_id
    FROM room_photos p
    JOIN rooms r ON r.id = p.room_id
    WHERE p.id = ?
");
$stmt->execute([$photoId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row && $row['owner_id'] == $host['id']) {
    // Delete record
    $del = $pdo->prepare("DELETE FROM room_photos WHERE id = ?");
    $del->execute([$photoId]);

    // Delete file
    $file = "../uploads/room_photos/" . $row['image_path'];
    if (is_file($file)) unlink($file);
}

header("Location: room_gallery_upload.php?room_id=".$roomId);
exit;
