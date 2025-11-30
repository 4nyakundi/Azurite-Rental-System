<?php
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';

if (!is_host()) die("Host only");

$hostId = $_SESSION['user']['id'];
$roomId = (int)($_GET['id'] ?? ($_GET['room_id'] ?? 0));

// Validate room ownership
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = :id AND owner_id = :host");
$stmt->execute([':id' => $roomId, ':host' => $hostId]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$room) die("Room not found.");

// DELETE photo
if (isset($_GET['delete'])) {
    $photoId = (int)$_GET['delete'];
    $delStmt = $pdo->prepare("SELECT image_path FROM room_photos WHERE id = :id");
    $delStmt->execute([':id' => $photoId]);
    $photo = $delStmt->fetch(PDO::FETCH_ASSOC);

    if ($photo) {
        unlink(__DIR__ . '/../uploads/room_photos/' . $photo['image_path']);
        $pdo->prepare("DELETE FROM room_photos WHERE id = :id")->execute([':id' => $photoId]);
    }
    redirect("room_gallery.php?id=".$roomId);
}

// UPLOAD photo
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['photo']['name'])) {
        $errors[] = "Please select a photo to upload.";
    } else {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png'];

        if (!in_array($ext, $allowed)) {
            $errors[] = "Invalid image type. Only JPG/PNG allowed.";
        } else {
            $safeName = "gallery_" . time() . "_" . rand(1000, 9999) . "." . $ext;
            $target   = __DIR__ . '/../uploads/room_photos/' . $safeName;

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
                $errors[] = "Failed to upload file.";
            } else {
                $pdo->prepare("
                    INSERT INTO room_photos (room_id, image_path, sort_order, created_at)
                    VALUES (:room, :img, 0, NOW())
                ")->execute([
                    ':room' => $roomId,
                    ':img'  => $safeName
                ]);

                redirect("room_gallery.php?id=".$roomId);
            }
        }
    }
}

include __DIR__ . '/header.php';
?>

<div class="section-heading"><h2>Upload Photo – <?php echo e($room['name']); ?></h2></div>

<div class="form-card" style="max-width:480px;">
<?php if ($errors): ?>
  <div class="alert error">
    <ul><?php foreach ($errors as $e): ?><li><?php echo e($e); ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <label>Select Photo</label>
  <input type="file" name="photo" required>

  <button class="btn-primary" style="width:100%;margin-top:16px;">Upload</button>
</form>
</div>

<?php include __DIR__ . '/footer.php'; ?>
