<?php
// public/host/rooms.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
if (!is_host()) die('Host only');

$hostId = $_SESSION['user']['id'];

$uploadDir = __DIR__ . '/../uploads/room_photos/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_room'])) {
    $name        = trim($_POST['name'] ?? '');
    $price       = trim($_POST['price'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $fileName    = '';

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
            INSERT INTO rooms (owner_id, name, price_ksh, location_text, description, cover_image, is_active, created_at)
            VALUES (:owner, :name, :price, :loc, :descr, :img, 1, NOW())
        ");
        $stmt->execute([
            ':owner' => $hostId,
            ':name'  => $name,
            ':price' => $price,
            ':loc'   => $location,
            ':descr' => $description,
            ':img'   => $fileName
        ]);

        set_flash('success', 'Room added.');
        header('Location: rooms.php');
        exit;
    }
}

$stmt = $pdo->prepare("SELECT * FROM rooms WHERE owner_id = :h ORDER BY created_at DESC");
$stmt->execute([':h'=>$hostId]);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/host_header.php';
?>

<div class="section-heading">
  <h2>My Rooms</h2>
</div>

<div class="form-card">
  <h3>Add New Room</h3>
  <?php if ($errors): ?>
    <div class="alert error">
      <ul><?php foreach ($errors as $e): ?><li><?php echo e($e); ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
<?php echo csrf_field(); ?>
    <input type="hidden" name="create_room" value="1">
    <label>Name</label><input type="text" name="name" required>
    <label>Price per night (Ksh)</label><input type="number" name="price" required>
    <label>Location</label><input type="text" name="location">
    <label>Description</label><textarea name="description" rows="3"></textarea>
    <label>Cover Image</label><input type="file" name="cover">
    <button class="btn-primary" style="width:100%;">Save</button>
  </form>
</div>

<h3 style="margin-top:30px;">My Listings</h3>
<table class="table">
  <thead>
    <tr>
      <th>Image</th><th>Name</th><th>Price</th><th>Location</th><th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rooms as $r): ?>
    <tr>
      <td>
        <?php if ($r['cover_image']): ?>
          <img src="../uploads/room_photos/<?php echo e($r['cover_image']); ?>" width="70" style="border-radius:8px;">
        <?php else: ?>
          <em>No image</em>
        <?php endif; ?>
      </td>
      <td><?php echo e($r['name']); ?></td>
      <td><?php echo format_ksh($r['price_ksh']); ?></td>
      <td><?php echo e($r['location_text']); ?></td>
      <td>
        <a href="room_edit.php?id=<?php echo (int)$r['id']; ?>">Edit</a> |
        <a href="calendar.php?room_id=<?php echo (int)$r['id']; ?>">Calendar</a> |
        <a href="bookings.php?room_id=<?php echo (int)$r['id']; ?>">Bookings</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<?php include __DIR__ . '/footer.php'; ?>
