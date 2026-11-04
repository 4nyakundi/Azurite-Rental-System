<?php
// public/admin/room_edit.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
if (!is_admin()) { die('Admin only'); }

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = :id");
$stmt->execute([':id' => $id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$room) die('Room not found.');

$uploadDir = __DIR__ . '/../uploads/room_photos/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $price       = trim($_POST['price'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_active   = isset($_POST['is_active']) ? 1 : 0;
    $fileName    = $room['cover_image'];

    if ($name === '')  $errors[] = 'Name required';
    if (!is_numeric($price) || (float)$price <= 0 || (float)$price > 99999999) $errors[] = 'Price required';

    if (!$errors) {
        try {
            $fileName = save_upload('cover', $uploadDir) ?? $fileName;
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare("
            UPDATE rooms
            SET name = :name,
                price_ksh = :price,
                location_text = :loc,
                description = :descr,
                cover_image = :img,
                is_active = :active
            WHERE id = :id
        ");
        $stmt->execute([
            ':name'   => $name,
            ':price'  => $price,
            ':loc'    => $location,
            ':descr'  => $description,
            ':img'    => $fileName,
            ':active' => $is_active,
            ':id'     => $id
        ]);

        set_flash('success', 'Room updated.');
        header('Location: rooms.php');
        exit;
    }
}

include __DIR__ . '/../../includes/admin_header.php';
?>

<div class="section-heading"><h2>Edit Room</h2></div>

<div class="form-card">
  <?php if ($errors): ?>
    <div class="alert error">
      <ul><?php foreach ($errors as $e): ?><li><?php echo e($e); ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
<?php echo csrf_field(); ?>
    <label>Name</label>
    <input type="text" name="name" value="<?php echo e($room['name']); ?>">

    <label>Price per night (Ksh)</label>
    <input type="number" name="price" value="<?php echo e($room['price_ksh']); ?>">

    <label>Location</label>
    <input type="text" name="location" value="<?php echo e($room['location_text']); ?>">

    <label>Description</label>
    <textarea name="description" rows="4"><?php echo e($room['description']); ?></textarea>

    <label>Current Image</label><br>
    <?php if ($room['cover_image']): ?>
      <img src="../uploads/room_photos/<?php echo e($room['cover_image']); ?>" width="120" style="border-radius:10px;margin-bottom:10px;">
    <?php else: ?>
      <em>No image</em><br>
    <?php endif; ?>

    <label>Change Image (optional)</label>
    <input type="file" name="cover">

    <label>
      <input type="checkbox" name="is_active" <?php echo $room['is_active'] ? 'checked' : ''; ?>>
      Active / visible on site
    </label>

    <button class="btn-primary" style="width:100%;margin-top:10px;">Save Changes</button>
  </form>
</div>

<?php include __DIR__ . '/../../includes/admin_footer.php'; ?>
