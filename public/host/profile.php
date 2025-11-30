<?php
// public/host/profile.php
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';

if (!is_host()) die("Host only");

$host = current_user();
$hostId = $host['id'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');

    if ($full_name === '') $errors[] = 'Full name is required.';
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }

    // Handle avatar upload (optional)
    $avatarFileName = $host['avatar_path'] ?? null;
    if (!empty($_FILES['avatar']['name'])) {
        $avatarDir = __DIR__ . '/../uploads/avatars/';
        if (!is_dir($avatarDir)) mkdir($avatarDir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png'];

        if (!in_array($ext, $allowed, true)) {
            $errors[] = 'Invalid avatar type. Use JPG or PNG.';
        } else {
            $safeName = 'avatar_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            $target   = $avatarDir . $safeName;
            if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
                $errors[] = 'Failed to upload avatar.';
            } else {
                $avatarFileName = $safeName;
            }
        }
    }

    if (!$errors) {
        $upd = $pdo->prepare("
            UPDATE users
            SET full_name = :name,
                email = :email,
                phone = :phone,
                avatar_path = :avatar
            WHERE id = :id
        ");
        $upd->execute([
            ':name'   => $full_name,
            ':email'  => $email ?: null,
            ':phone'  => $phone ?: null,
            ':avatar' => $avatarFileName,
            ':id'     => $hostId
        ]);

        // Refresh session user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $hostId]);
        $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);

        set_flash('success', 'Profile updated.');
        redirect('profile.php');
    }
} else {
    $full_name = $host['full_name'] ?? '';
    $email     = $host['email'] ?? '';
    $phone     = $host['phone'] ?? '';
    $avatarFileName = $host['avatar_path'] ?? null;
}

include __DIR__ . '/../../includes/host_header.php';
?>

<div class="section-heading">
  <h2>My Profile</h2>
</div>

<div class="form-card" style="max-width:520px;">
  <?php if ($errors): ?>
    <div class="alert error">
      <ul><?php foreach ($errors as $e): ?><li><?php echo e($e); ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <div style="text-align:center;margin-bottom:15px;">
      <?php if (!empty($avatarFileName)): ?>
        <img src="../uploads/avatars/<?php echo e($avatarFileName); ?>" 
             style="width:80px;height:80px;border-radius:50%;object-fit:cover;">
      <?php else: ?>
        <div style="width:80px;height:80px;border-radius:50%;background:#e5e7eb;
                    display:flex;align-items:center;justify-content:center;margin:0 auto;">
          <span style="font-size:30px;color:#6b7280;">
            <?php echo strtoupper($full_name[0] ?? 'H'); ?>
          </span>
        </div>
      <?php endif; ?>
    </div>

    <label>Full Name</label>
    <input type="text" name="full_name" value="<?php echo e($full_name); ?>" required>

    <label>Email</label>
    <input type="email" name="email" value="<?php echo e($email); ?>">

    <label>Phone</label>
    <input type="text" name="phone" value="<?php echo e($phone); ?>">

    <label>Change Avatar (optional)</label>
    <input type="file" name="avatar" accept=".jpg,.jpeg,.png">

    <button class="btn-primary" style="width:100%;margin-top:15px;">Save Profile</button>
  </form>
</div>

<?php include __DIR__ . '/../../includes/host_footer.php'; ?>
