<?php
// public/host/bookings.php
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';

if (!is_host()) die("Host only");

$hostId = $_SESSION['user']['id'];

$sql = "
SELECT 
    b.*, 
    r.name AS room_name, 
    u.full_name, u.phone, u.email,
    u.avatar_path, u.id_card_path
FROM bookings b
JOIN rooms r ON r.id = b.room_id
JOIN users u ON u.id = b.tenant_id
WHERE r.owner_id = :host
ORDER BY b.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':host' => $hostId]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/host_header.php';
?>

<style>
.booking-card {
  background:#fff;
  margin-bottom:20px;
  padding:20px;
  border-radius:12px;
  box-shadow:0 3px 10px rgba(0,0,0,0.08);
  display:flex;
  gap:20px;
  align-items:flex-start;
}
.avatar-box img {
  width:80px;
  height:80px;
  border-radius:50%;
  object-fit:cover;
  border:3px solid #eef;
}
.id-box img {
  width:200px;
  border-radius:8px;
  border:2px solid #ddd;
}
.booking-info strong {
  font-size:16px;
}
.small-label {
  font-size:12px;
  color:#666;
}
.action-buttons a {
  margin-right:10px;
}
.action-buttons form {
    display: inline-block; /* Allows buttons/links to sit side-by-side */
}
/* New style for View Details button */
.btn-view {
    background-color: #f8f9fa; 
    border: 1px solid #ced4da; 
    color: #495057; 
}
.btn-view:hover {
    background-color: #e2e6ea;
}
</style>

<div class="section-heading">
  <h2>Bookings</h2>
</div>

<?php if (!$bookings): ?>
  <p>No bookings yet.</p>
<?php endif; ?>

<?php foreach ($bookings as $b): ?>
<div class="booking-card">

  <div class="tenant-media">
    <div class="avatar-box">
      <?php if ($b['avatar_path']): ?>
        <img src="../uploads/avatars/<?php echo e($b['avatar_path']); ?>">
      <?php else: ?>
        <img src="../assets/img/default-avatar.png">
      <?php endif; ?>
    </div>

    <div class="id-box" style="margin-top:10px;">
      <?php if ($b['id_card_path']): ?>
        <a href="../uploads/id_cards/<?php echo e($b['id_card_path']); ?>" target="_blank">
          <img src="../uploads/id_cards/<?php echo e($b['id_card_path']); ?>">
        </a>
      <?php else: ?>
        <small>No ID uploaded</small>
      <?php endif; ?>
    </div>
  </div>

  <div class="booking-info">
    <strong><?php echo e($b['full_name']); ?></strong><br>
    <span class="small-label"><?php echo e($b['email']); ?></span><br>
    <span class="small-label">Phone: <?php echo e($b['phone']); ?></span><br><br>

    <p><strong>Room:</strong> <?php echo e($b['room_name']); ?></p>
    <p><strong>Ref:</strong> <?php echo e($b['ref_code']); ?></p>
    <p><strong>Stay:</strong> <?php echo e($b['checkin_date']); ?> → <?php echo e($b['checkout_date']); ?></p>
    <p><strong>Guests:</strong> <?php echo (int)$b['guests']; ?></p>
    <p><strong>Total:</strong> KSh <?php echo number_format($b['total_amount_ksh'], 2); ?></p>
    <p><strong>Status:</strong> <?php echo e($b['status']); ?></p>

    <div class="action-buttons" style="margin-top:10px;">
      
      <a href="booking_view.php?id=<?php echo (int)$b['id']; ?>" class="btn-sm btn-view">View Details</a> 

      <?php if ($b['status'] === 'Pending'): ?>
        <a href="bookings_update.php?id=<?php echo (int)$b['id']; ?>&action=approve" class="btn-primary btn-sm">Approve</a>
        <a href="bookings_update.php?id=<?php echo (int)$b['id']; ?>&action=decline" class="btn-outline btn-sm">Decline</a>
      <?php endif; ?>
    </div>
  </div>

</div>
<?php endforeach; ?>

<?php include __DIR__ . '/../../includes/host_footer.php'; ?>