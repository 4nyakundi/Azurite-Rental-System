<?php
require_once __DIR__ . '/../../includes/admin_header.php';

// Quick stats
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'tenant'")->fetchColumn();
$totalRooms    = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$pending       = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'Pending'")->fetchColumn();
$approved      = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'Approved'")->fetchColumn();

$revenueStmt = $pdo->query("
    SELECT COALESCE(SUM(total_amount_ksh),0)
    FROM bookings
    WHERE status IN ('Approved','Confirmed','CheckedIn','Completed')
");
$revenue = $revenueStmt->fetchColumn();
?>

<div class="section-heading">
  <h2>Admin Dashboard</h2>
</div>

<div class="card-grid">
  <div class="card">
    <div class="card-body">
      <h3>Tenants</h3>
      <div class="card-meta">Registered users</div>
      <div class="card-bottom">
        <span class="card-price"><?php echo (int)$totalUsers; ?></span>
        <a href="clients.php" class="btn-outline" style="padding:4px 10px;font-size:12px;">View clients</a>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h3>Rooms</h3>
      <div class="card-meta">Total listings</div>
      <div class="card-bottom">
        <span class="card-price"><?php echo (int)$totalRooms; ?></span>
        <a href="rooms.php" class="btn-outline" style="padding:4px 10px;font-size:12px;">Manage rooms</a>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h3>Bookings</h3>
      <div class="card-meta">All time</div>
      <div class="card-bottom">
        <span class="card-price"><?php echo (int)$totalBookings; ?></span>
        <a href="bookings.php" class="btn-outline" style="padding:4px 10px;font-size:12px;">View bookings</a>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h3>Pending Requests</h3>
      <div class="card-meta">Awaiting approval</div>
      <div class="card-bottom">
        <span class="card-price"><?php echo (int)$pending; ?></span>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h3>Approved</h3>
      <div class="card-meta">Confirmed stays</div>
      <div class="card-bottom">
        <span class="card-price"><?php echo (int)$approved; ?></span>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h3>Revenue</h3>
      <div class="card-meta">Approved / completed</div>
      <div class="card-bottom">
        <span class="card-price"><?php echo format_ksh($revenue); ?></span>
        <a href="report_bookings.php" class="btn-outline" style="padding:4px 10px;font-size:12px;">Reports</a>
      </div>
    </div>
  </div>
</div>

<h3>Quick Actions</h3>
<div class="hero-actions" style="margin-bottom:24px;">
  <a href="rooms.php" class="btn-primary">Add / Manage Houses</a>
  <a href="bookings.php" class="btn-outline">View Bookings</a>
  <a href="clients.php" class="btn-outline">View Clients</a>
  <a href="calendar.php" class="btn-outline">Calendar</a>
  <a href="report_bookings.php" class="btn-outline">Reports</a>
</div>

<?php include __DIR__ . '/../../includes/admin_footer.php'; ?>
