<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
if (!is_admin()) { die('Admin only'); }

// 1. Fetch all users including their role
$all_users = $pdo->query("
    SELECT id, full_name, username, email, phone, role, created_at, id_card_path
    FROM users ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// 2. Separate users into two groups: Hosts and Public Users
$hosts = [];
$public_users = [];

foreach ($all_users as $user) {
    // Assuming 'host' is the role for hosts and 'user' (or any non-admin role) for public clients
    if ($user['role'] === 'host') {
        $hosts[] = $user;
    } else if ($user['role'] !== 'admin') { // Exclude admin from the clients list if the role is stored
        $public_users[] = $user;
    }
}


include __DIR__ . '/../../includes/admin_header.php';
?>

<div class="section-heading">
  <h2>Clients Management</h2>
</div>

---

<div class="hosts-section">
    <h3 style="margin-top: 30px; margin-bottom: 20px; color: #007bff;">🏡 Hosts (<?php echo count($hosts); ?>)</h3>

    <?php if (empty($hosts)): ?>
        <p>No hosts found.</p>
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
        <?php foreach ($hosts as $u): ?>
        <tr>
            <td><?php echo $u['id']; ?></td>
            <td><?php echo e($u['full_name']); ?></td>
            <td><?php echo e($u['username']); ?></td>
            <td><?php echo e($u['email']); ?></td>
            <td><?php echo e($u['phone']); ?></td>
            <td>
                <?php if ($u['id_card_path']): ?>
                    <img src="<?php echo e(public_base()); ?>document.php?file=<?php echo $u['id_card_path']; ?>" width="60" style="border: 1px solid #007bff;">
                <?php else: ?>
                    <em>None</em>
                <?php endif; ?>
            </td>
            <td><a class="btn-outline" href="client_view.php?id=<?php echo $u['id']; ?>">View</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
    <?php endif; ?>
</div>

---

<div class="public-users-section">
    <h3 style="margin-top: 30px; margin-bottom: 20px; color: #28a745;">👤 Public Users (<?php echo count($public_users); ?>)</h3>

    <?php if (empty($public_users)): ?>
        <p>No public users found.</p>
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
        <?php foreach ($public_users as $u): ?>
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
            <td><a class="btn-outline" href="client_view.php?id=<?php echo $u['id']; ?>">View</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/admin_footer.php'; ?>