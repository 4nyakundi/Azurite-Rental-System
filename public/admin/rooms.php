<?php
// public/admin/rooms.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (!is_admin()) {
    die("Admin only");
}

$uploadDir = __DIR__ . '/../uploads/room_photos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_room'])) {
    $name        = trim($_POST['name'] ?? '');
    $price       = trim($_POST['price'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $fileName    = '';

    if ($name === '') $errors[] = "Name required";
    if (!is_numeric($price) || (float)$price <= 0 || (float)$price > 99999999) $errors[] = "Price required";

    if (!$errors) {
        try {
            $fileName = save_upload('cover', $uploadDir) ?? $fileName;
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }
    }

    if (!$errors) {
        $ownerId = $_SESSION['user']['id']; // admin as owner for now
        $stmt = $pdo->prepare("
            INSERT INTO rooms (owner_id, name, price_ksh, location_text, description, cover_image, is_active, created_at)
            VALUES (:owner, :name, :price, :loc, :descr, :img, 1, NOW())
        ");
        $stmt->execute([
            ':owner' => $ownerId,
            ':name'  => $name,
            ':price' => $price,
            ':loc'   => $location,
            ':descr' => $description,
            ':img'   => $fileName
        ]);

        set_flash('success', 'Room added.');
        header("Location: rooms.php");
        exit;
    }
}

$rooms = $pdo->query("
    SELECT r.*, u.full_name AS owner_name
    FROM rooms r
    LEFT JOIN users u ON u.id = r.owner_id
    ORDER BY r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/admin_header.php';
?>

<div class="section-heading"><h2>Manage Rooms</h2></div>

<div class="form-card">
  <h3>Add New Room</h3>

  <?php if ($errors): ?>
    <div class="alert error">
      <ul><?php foreach ($errors as $e): ?><li><?php echo e($e); ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
<?php echo csrf_field(); ?>
    <input type="hidden" name="create_room" value="1">

    <label>Name</label>
    <input type="text" name="name" required>

    <label>Price per night (Ksh)</label>
    <input type="number" name="price" required>

    <label>Location</label>
    <input type="text" name="location">

    <label>Description</label>
    <textarea name="description" rows="3"></textarea>

    <label>Cover Image</label>
    <input type="file" name="cover">

    <button class="btn-primary" style="width:100%;">Add Room</button>
  </form>
</div>

<h3 style="margin-top:30px;">Existing Rooms</h3>

<table class="table">
  <thead>
    <tr>
      <th>Image</th>
      <th>Name</th>
      <th>Owner</th>
      <th>Price</th>
      <th>Location</th>
      <th>Created</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rooms as $r): ?>
    <tr>
      <td>
        <?php if ($r['cover_image']): ?>
          <img src="../uploads/room_photos/<?php echo e($r['cover_image']); ?>" width="70" style="border-radius:10px;">
        <?php else: ?>
          <small>No image</small>
        <?php endif; ?>
      </td>
      <td><?php echo e($r['name']); ?></td>
      <td><?php echo e($r['owner_name'] ?: '—'); ?></td>
      <td><?php echo format_ksh($r['price_ksh']); ?></td>
      <td><?php echo e($r['location_text']); ?></td>
      <td><?php echo e($r['created_at']); ?></td>
      <td>
        <a href="room_view.php?id=<?php echo (int)$r['id']; ?>">View</a> |
        <a href="room_edit.php?id=<?php echo (int)$r['id']; ?>">Edit</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/../../includes/admin_footer.php'; ?>
