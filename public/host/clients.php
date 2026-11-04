<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
if (!is_host()) { die('host only'); }

// Get the current host's ID from the session
$hostId = $_SESSION['user']['id'];

// MODIFIED QUERY: Select DISTINCT tenants who have booked a room owned by this host.
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.full_name, u.username, u.email, u.phone, u.role, u.created_at, u.id_card_path
    FROM users u
    JOIN bookings b ON u.id = b.tenant_id
    JOIN rooms r ON b.room_id = r.id
    WHERE u.role = 'tenant'
      AND r.owner_id = :hostId
    ORDER BY u.created_at DESC
");
$stmt->execute([':hostId' => $hostId]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../../includes/host_header.php';
?>

<div class="section-heading">
  <h2>Clients with Bookings</h2>
</div>

<?php if (empty($users)): ?>
    <p class="p-4" style="color:#666; font-style:italic;">No clients have booked your houses yet.</p>
<?php else: ?>
    <table class="table">
    <thead>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Username</th>
        <th>Email</th>
        <th>Phone</th>
        <th>ID Card</th>
        <th>Action</th>
    </tr>
    </thead>

    <tbody>
    <?php foreach ($users as $u): ?>
    <tr>
        <td><?php echo $u['id']; ?></td>
        <td><?php echo e($u['full_name']); ?></td>
        <td><?php echo e($u['username']); ?></td>
        <td><?php echo e($u['email']); ?></td>
        <td><?php echo e($u['phone']); ?></td>
        <td>
            <?php if ($u['id_card_path']): ?>
                <img src="<?php echo e(public_base()); ?>document.php?file=<?php echo $u['id_card_path']; ?>" width="60">
            <?php else: ?>
                <em>None</em>
            <?php endif; ?>
        </td>
        <td>
            <a href="client_view.php?id=<?php echo $u['id']; ?>" class="btn-secondary" style="padding:6px 12px;font-size:14px;">View Profile</a>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/host_footer.php'; ?>