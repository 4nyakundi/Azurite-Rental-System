<?php
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';

if (!is_host()) die("Host only");

$hostId = $_SESSION['user']['id'];
$roomId = (int)($_GET['id'] ?? 0);

// Validate room ownership
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = :id AND owner_id = :host");
$stmt->execute([':id' => $roomId, ':host' => $hostId]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$room) die("Room not found.");

$photosStmt = $pdo->prepare("SELECT * FROM room_photos WHERE room_id = :id ORDER BY sort_order ASC");
$photosStmt->execute([':id' => $roomId]);
$photos = $photosStmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<div class="section-heading"><h2>Manage Photos - <?php echo e($room['name']); ?></h2></div>

<a href="room_gallery_upload.php?id=<?php echo $roomId; ?>" class="btn-primary" style="margin-bottom: 16px;display:inline-block;">
  + Upload New Photo
</a>

<div class="card-grid">
<?php foreach ($photos as $p): ?>
  <div class="card">
    <div class="card-image">
      <img src="../uploads/room_photos/<?php echo e($p['image_path']); ?>" style="width:100%;border-radius:8px;">
    </div>
    <div class="card-body">
      <a href="room_gallery_upload.php?delete=<?php echo $p['id']; ?>&room_id=<?php echo $roomId; ?>"
         class="btn-outline"
         onclick="return confirm('Delete this photo?');">Delete</a>
    </div>
  </div>
<?php endforeach; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>
