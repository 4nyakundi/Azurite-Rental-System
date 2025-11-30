<?php
// public/my_bookings.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];

$stmt = $pdo->prepare("
    SELECT b.*, 
           r.name AS room_name,
           r.location_text
    FROM bookings b
    JOIN rooms r ON r.id = b.room_id
    WHERE b.tenant_id = :u
    ORDER BY b.created_at DESC
");
$stmt->execute([':u' => $userId]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="section-heading">
  <h2>My Bookings</h2>
</div>

<div class="container" style="max-width: 960px; margin:auto;">

  <?php if (!$bookings): ?>
    <p>You have not made any bookings yet.</p>
  <?php else: ?>

    <table class="table">
      <thead>
        <tr>
          <th>Ref</th>
          <th>Room</th>
          <th>Location</th>
          <th>Dates</th>
          <th>Guests</th>
          <th>Total (KSh)</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($bookings as $b): ?>
        <tr>
          <td><?php echo e($b['ref_code']); ?></td>
          <td><a href="room.php?id=<?php echo (int)$b['room_id']; ?>"><?php echo e($b['room_name']); ?></a></td>
          <td><?php echo e($b['location_text']); ?></td>
          <td><?php echo e($b['checkin_date']); ?> → <?php echo e($b['checkout_date']); ?></td>
          <td><?php echo (int)$b['guests']; ?></td>
          <td><?php echo number_format($b['total_amount_ksh'], 2); ?></td>
          <td><?php echo e($b['status']); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

  <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
