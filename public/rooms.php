<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$q = trim($_GET['q'] ?? '');
$checkin = (string)($_GET['checkin'] ?? '');
$checkout = (string)($_GET['checkout'] ?? '');
$error = '';
$params = [];
$sql = "SELECT r.id,r.name,r.price_ksh,r.location_text,r.cover_image,c.name AS category FROM rooms r LEFT JOIN room_categories c ON c.id=r.category_id WHERE r.is_active=1";
if ($q !== '') {
    $sql .= ' AND (r.name LIKE ? OR r.location_text LIKE ?)';
    $params = ['%' . $q . '%', '%' . $q . '%'];
}
if ($checkin !== '' || $checkout !== '') {
    if (!valid_date($checkin) || !valid_date($checkout) || $checkin < date('Y-m-d') || $checkout <= $checkin) {
        $error = 'Choose valid future check-in and check-out dates.';
        $sql .= ' AND 1=0';
    } else {
        $sql .= " AND NOT EXISTS (SELECT 1 FROM bookings b WHERE b.room_id=r.id AND b.status IN ('Pending','Approved','Confirmed','CheckedIn') AND b.checkin_date<? AND b.checkout_date>?) AND NOT EXISTS (SELECT 1 FROM room_block_dates d WHERE d.room_id=r.id AND d.block_date>=? AND d.block_date<?)";
        array_push($params, $checkout, $checkin, $checkin, $checkout);
    }
}
$sql .= ' ORDER BY r.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
include __DIR__ . '/../includes/header.php';
?>
<div class="section-heading"><div><div class="eyebrow">Make yourself at home</div><h2>Explore coastal stays</h2></div><span><?php echo count($rooms); ?> <?php echo count($rooms) === 1 ? 'stay' : 'stays'; ?> found</span></div>
<form method="get" class="search-filters">
  <label>Location or house<input type="search" name="q" value="<?= e($q) ?>" placeholder="Nyali, Mombasa, Diani…"></label>
  <label>Check-in<input type="date" name="checkin" value="<?= e($checkin) ?>"></label>
  <label>Check-out<input type="date" name="checkout" value="<?= e($checkout) ?>"></label>
  <button class="btn-primary" type="submit">Find a stay</button>
  <a href="rooms.php">Clear filters</a>
</form>
<?php if ($error): ?><div class="alert error" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php if (!$rooms && !$error): ?><div class="empty-state"><h3>No stays match your search</h3><p>Try another location or date range.</p><a href="rooms.php">View all stays</a></div><?php endif; ?>
<div class="card-grid">
  <?php foreach ($rooms as $r): ?>
    <div class="card">
      <div class="card-image">
        <?php if ($r['cover_image']): ?>
          <img src="uploads/room_photos/<?php echo e($r['cover_image']); ?>" alt="<?php echo e($r['name']); ?>">
        <?php else: ?>
          <img src="assets/img/outside_5.jpg" alt="House">
        <?php endif; ?>
        <div class="card-tag"><?php echo e($r['category'] ?? 'Listing'); ?></div>
      </div>
      <div class="card-body">
        <h3><?php echo e($r['name']); ?></h3>
        <div class="card-meta"><?php echo e($r['location_text']); ?></div>
        <div class="card-bottom">
          <span class="card-price"><?php echo format_ksh($r['price_ksh']); ?> / night</span>
          <a href="room.php?id=<?php echo (int)$r['id']; ?>" class="btn-outline" style="padding:6px 12px;font-size:12px;">Details</a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
