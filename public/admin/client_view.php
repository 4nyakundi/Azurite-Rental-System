<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (!is_admin()) { die("Admin only"); }

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=:id");
$stmt->execute([':id'=>$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) die("User not found");

$bookStmt = $pdo->prepare("
SELECT b.*, r.name AS room_name
FROM bookings b
LEFT JOIN rooms r ON r.id = b.room_id
WHERE b.tenant_id = :id
ORDER BY b.created_at DESC
");
$bookStmt->execute([':id'=>$id]);
$bookings = $bookStmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/admin_header.php';
?>

<h2 class="section-heading">Client Profile</h2>

<div class="form-card" style="max-width:450px;margin-bottom:30px;">
    <h3><?php echo e($user['full_name']); ?></h3>

    <p><strong>Username:</strong> <?php echo e($user['username']); ?></p>
    <p><strong>Email:</strong> <?php echo e($user['email']); ?></p>
    <p><strong>Phone:</strong> <?php echo e($user['phone']); ?></p>
    <p><strong>Joined:</strong> <?php echo e($user['created_at']); ?></p>

    <?php if ($user['id_card_path']): ?>
        <h4>ID Card</h4>
        <img src="../uploads/id_cards/<?php echo e($user['id_card_path']); ?>" 
             style="max-width:100%;border-radius:10px;">
    <?php endif; ?>

    <button onclick="window.print()" class="btn-primary" style="width:100%;margin-top:15px;">
        Print Profile
    </button>
</div>

<h3>Booking History</h3>

<table class="table">
<thead>
<tr>
    <th>Ref</th>
    <th>House</th>
    <th>Check-in</th>
    <th>Check-out</th>
    <th>Total</th>
    <th>Status</th>
</tr>
</thead>

<tbody>
<?php foreach ($bookings as $b): ?>
<tr>
    <td><?php echo e($b['ref_code']); ?></td>
    <td><?php echo e($b['room_name']); ?></td>
    <td><?php echo e($b['checkin_date']); ?></td>
    <td><?php echo e($b['checkout_date']); ?></td>
    <td><?php echo format_ksh($b['total_amount_ksh']); ?></td>
    <td><?php echo e($b['status']); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php include __DIR__ . '/../../includes/admin_footer.php'; ?>
