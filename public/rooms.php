<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $stmt = $pdo->prepare("SELECT r.id, r.name, r.price_ksh, r.location_text, r.cover_image, c.name AS category
                           FROM rooms r
                           LEFT JOIN room_categories c ON c.id = r.category_id
                           WHERE r.is_active = 1 AND (r.name LIKE :q OR r.location_text LIKE :q)
                           ORDER BY r.id DESC");
    $stmt->execute([':q' => '%' . $q . '%']);
} else {
    $stmt = $pdo->query("SELECT r.id, r.name, r.price_ksh, r.location_text, r.cover_image, c.name AS category
                         FROM rooms r
                         LEFT JOIN room_categories c ON c.id = r.category_id
                         WHERE r.is_active = 1
                         ORDER BY r.id DESC");
}
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
include __DIR__ . '/../includes/header.php';
?>
<div class="section-heading">
  <h2>All Houses</h2>
  <form method="get">
    <input type="text" name="q" placeholder="Search by name or location" value="<?php echo e($q); ?>" style="padding:8px 10px;border-radius:999px;border:1px solid #cbd5e1;">
  </form>
</div>
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
