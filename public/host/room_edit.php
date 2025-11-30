<?php
// public/host/room_edit.php
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';

if (!is_host()) {
    die("Host only");
}

$hostId = $_SESSION['user']['id'];
$roomId = (int)($_GET['id'] ?? 0);

// Ensure the room belongs to this host
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = :id AND owner_id = :host");
$stmt->execute([':id' => $roomId, ':host' => $hostId]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    die("Room not found or not owned by you.");
}

$uploadDir = __DIR__ . '/../uploads/room_photos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $price       = trim($_POST['price'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_active   = isset($_POST['is_active']) ? 1 : 0;
    $fileName    = $room['cover_image'];

    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if ($price === '') {
        $errors[] = 'Price is required.';
    }

    // Optional new cover image
    if (!empty($_FILES['cover']['name'])) {
        $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png'];

        if (!in_array($ext, $allowed, true)) {
            $errors[] = 'Invalid image type. Only JPG/PNG allowed.';
        } else {
            $safeName = 'room_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $target   = $uploadDir . $safeName;

            if (!move_uploaded_file($_FILES['cover']['tmp_name'], $target)) {
                $errors[] = 'Failed to upload image.';
            } else {
                $fileName = $safeName;
            }
        }
    }

    if (!$errors) {
        $upd = $pdo->prepare("
            UPDATE rooms
            SET name = :name,
                price_ksh = :price,
                location_text = :loc,
                description = :descr,
                cover_image = :img,
                is_active = :active
            WHERE id = :id AND owner_id = :host
        ");
        $upd->execute([
            ':name'   => $name,
            ':price'  => $price,
            ':loc'    => $location,
            ':descr'  => $description,
            ':img'    => $fileName,
            ':active' => $is_active,
            ':id'     => $roomId,
            ':host'   => $hostId
        ]);

        set_flash('success', 'Room updated successfully.');
        redirect('rooms.php');
    }
}

include __DIR__ . '/header.php';
?>

<div class="section-heading">
  <h2>Edit Room</h2>
</div>

<div class="form-card" style="max-width:520px;">
  <?php if ($errors): ?>
    <div class="alert error">
      <ul><?php foreach ($errors as $e): ?><li><?php echo e($e); ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <label>Name</label>
    <input type="text" name="name" value="<?php echo e($room['name']); ?>" required>

    <label>Price per night (Ksh)</label>
    <input type="number" name="price" value="<?php echo e($room['price_ksh']); ?>" required>

    <label>Location</label>
    <input type="text" name="location" value="<?php echo e($room['location_text']); ?>">

    <label>Description</label>
    <textarea name="description" rows="4"><?php echo e($room['description']); ?></textarea>

    <label>Current Cover</label><br>
    <?php if ($room['cover_image']): ?>
      <img src="../uploads/room_photos/<?php echo e($room['cover_image']); ?>" width="140" style="border-radius:10px;margin-bottom:10px;">
    <?php else: ?>
      <em>No cover image</em><br>
    <?php endif; ?>

    <label>Change Cover (optional)</label>
    <input type="file" name="cover">

    <label style="margin-top:10px;">
      <input type="checkbox" name="is_active" <?php echo $room['is_active'] ? 'checked' : ''; ?>>
      Active / visible to tenants
    </label>

    <button class="btn-primary" style="width:100%;margin-top:15px;">Save Changes</button>
  </form>
</div>

<?php include __DIR__ . '/footer.php'; ?>
