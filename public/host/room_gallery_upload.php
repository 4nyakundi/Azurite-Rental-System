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
    confirm_mutation('Delete this photo?');
    $photoId = (int)$_GET['delete'];
    $delStmt = $pdo->prepare("SELECT image_path FROM room_photos WHERE id = :id AND room_id = :room");
    $delStmt->execute([':id' => $photoId, ':room' => $roomId]);
    $photo = $delStmt->fetch(PDO::FETCH_ASSOC);

    if ($photo) {
        $file = __DIR__ . '/../uploads/room_photos/' . basename($photo['image_path']);
        if (is_file($file)) unlink($file);
        $pdo->prepare("DELETE FROM room_photos WHERE id = :id AND room_id = :room")->execute([':id' => $photoId, ':room' => $roomId]);
    }
    redirect("room_gallery.php?id=".$roomId);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = save_upload('photo', __DIR__ . '/../uploads/room_photos');
        if (!$name) throw new RuntimeException('Please choose a photo.');
        $pdo->prepare('INSERT INTO room_photos (room_id,image_path,sort_order) VALUES (?,?,0)')->execute([$roomId, $name]);
        redirect('room_gallery.php?id=' . $roomId);
    } catch (RuntimeException $exception) {
        $errors[] = $exception->getMessage();
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
<?php echo csrf_field(); ?>
  <label>Select Photo</label>
  <input type="file" name="photo" required>

  <button class="btn-primary" style="width:100%;margin-top:16px;">Upload</button>
</form>
</div>

<?php include __DIR__ . '/footer.php'; ?>
