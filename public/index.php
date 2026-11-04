<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$stmt = $pdo->query("SELECT r.id, r.name, r.price_ksh, r.location_text, r.cover_image, c.name AS category
                     FROM rooms r
                     LEFT JOIN room_categories c ON c.id = r.category_id
                     WHERE r.is_active = 1
                     ORDER BY r.id DESC
                     LIMIT 6");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
include __DIR__ . '/../includes/header.php';
?>
<section class="hero">
  <div class="hero-text">
    <div class="eyebrow">Kenya’s coast, at your pace</div>
    <h1>A little closer<br>to the coast.</h1>
    <p>Find a place to slow down. Explore coastal homes, compare nightly rates and send your booking request directly to the host.</p>
    <div class="hero-actions">
      <a href="rooms.php" class="btn-primary">Browse Houses</a>
      <a href="#featured" class="btn-outline">View Featured</a>
    </div>
    <form class="search-bar" action="rooms.php" method="get">
      <div>
        <label>Location</label>
        <input type="text" name="q" placeholder="Mombasa, Nyali, Diani...">
      </div>
      <div>
        <label>Check-in</label>
        <input type="date" name="checkin">
      </div>
      <div>
        <label>Check-out</label>
        <input type="date" name="checkout">
      </div>
      <div>
        <button class="search-btn" type="submit">Search</button>
      </div>
    </form>
  </div>
  <div class="hero-visual">
    <div class="hero-visual-inner">
      <img src="assets/img/outside_1.jpg" alt="Coastal apartment">
    </div>
  </div>
</section>

<div id="featured" class="section-heading">
  <h2>Featured Houses</h2>
</div>
<div class="card-grid">
  <?php if ($rooms): ?>
    <?php foreach ($rooms as $r): ?>
      <div class="card">
        <div class="card-image">
          <?php if ($r['cover_image']): ?>
            <img src="uploads/room_photos/<?php echo e($r['cover_image']); ?>" alt="<?php echo e($r['name']); ?>">
          <?php else: ?>
            <img src="assets/img/outside_2.jpg" alt="House">
          <?php endif; ?>
          <div class="card-tag"><?php echo e($r['category'] ?? 'Apartment'); ?></div>
        </div>
        <div class="card-body">
          <h3><?php echo e($r['name']); ?></h3>
          <div class="card-meta"><?php echo e($r['location_text']); ?></div>
          <div class="card-bottom">
            <span class="card-price"><?php echo format_ksh($r['price_ksh']); ?> / night</span>
            <a href="room.php?id=<?php echo (int)$r['id']; ?>" class="btn-outline" style="padding:6px 12px;font-size:12px;">View &amp; Book</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>New stays are on the way. Please check back soon.</p>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
