<?php
// public/host/index.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
if (!is_host()) die('Host only');

$hostId = $_SESSION['user']['id'];

$totalRooms = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE owner_id = :h");
$totalRooms->execute([':h'=>$hostId]);
$totalRooms = $totalRooms->fetchColumn();

$totalBookings = $pdo->prepare("
    SELECT COUNT(*) FROM bookings b
    JOIN rooms r ON r.id = b.room_id
    WHERE r.owner_id = :h
");
$totalBookings->execute([':h'=>$hostId]);
$totalBookings = $totalBookings->fetchColumn();

$upcoming = $pdo->prepare("
    SELECT COUNT(*) FROM bookings b
    JOIN rooms r ON r.id = b.room_id
    WHERE r.owner_id = :h AND b.status IN ('Pending','Approved','Confirmed')
");
$upcoming->execute([':h'=>$hostId]);
$upcoming = $upcoming->fetchColumn();

include __DIR__ . '/../../includes/host_header.php';
?>

<div class="section-heading">
  <h2>Host Dashboard</h2>
</div>

<div class="card-grid">
  <div class="card">
    <div class="card-body">
      <h3>My Listings</h3>
      <div class="card-meta">Total rooms</div>
      <div class="card-bottom">
        <span class="card-price"><?php echo (int)$totalRooms; ?></span>
        <a class="btn-outline" href="rooms.php">Manage</a>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h3>Bookings</h3>
      <div class="card-meta">All time</div>
      <div class="card-bottom">
        <span class="card-price"><?php echo (int)$totalBookings; ?></span>
        <a class="btn-outline" href="bookings.php">View</a>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h3>Upcoming</h3>
      <div class="card-meta">Pending / approved</div>
      <div class="card-bottom">
        <span class="card-price"><?php echo (int)$upcoming; ?></span>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/host_footer.php'; ?>
