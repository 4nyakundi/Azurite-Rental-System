<?php
// public/register.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// If already logged in, you can redirect if you want
if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
$full_name = '';
$username  = '';
$email     = '';
$phone     = '';
$role      = 'tenant';

// Setup captcha on first load or after GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['reg_captcha_a'] = rand(1, 9);
    $_SESSION['reg_captcha_b'] = rand(1, 9);
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $role      = $_POST['role'] ?? 'tenant';
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $captcha   = trim($_POST['captcha'] ?? '');

    // Basic validation
    if ($full_name === '') $errors[] = 'Full name is required.';
    if ($username === '')  $errors[] = 'Username is required.';
    if ($email === '')     $errors[] = 'Email is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    if ($password === '')  $errors[] = 'Password is required.';
    if (strlen($password) < 4) $errors[] = 'Password should be at least 4 characters.';
    if ($password !== $confirm) $errors[] = 'Password and confirmation do not match.';

    if (!in_array($role, ['tenant','host'], true)) {
        $errors[] = 'Invalid role selected.';
    }

    // Captcha validation
    $a = $_SESSION['reg_captcha_a'] ?? null;
    $b = $_SESSION['reg_captcha_b'] ?? null;
    if ($a === null || $b === null) {
        $errors[] = 'Captcha session expired. Please reload the page.';
    } else {
        $correct = $a + $b;
        if ((string)$correct !== $captcha) {
            $errors[] = 'Incorrect captcha answer.';
        }
    }

    // ID card upload required
    $idCardFileName = null;
    $avatarFileName = null;

    $idCardRequired = true;
    if ($idCardRequired && (empty($_FILES['id_card']['name']))) {
        $errors[] = 'Please upload a copy of your ID card.';
    }

    // If no previous errors, handle uploads and DB checks
    if (!$errors) {
        // Check username/email uniqueness
        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :u OR email = :e");
        $check->execute([':u' => $username, ':e' => $email]);
        if ($check->fetchColumn() > 0) {
            $errors[] = 'Username or email already exists. Please choose another.';
        }
    }

    // Handle uploads if still no errors
    if (!$errors) {
        // Ensure upload directories exist
        $idCardDir = __DIR__ . '/uploads/id_cards/';
        $avatarDir = __DIR__ . '/uploads/avatars/';

        if (!is_dir($idCardDir)) {
            mkdir($idCardDir, 0777, true);
        }
        if (!is_dir($avatarDir)) {
            mkdir($avatarDir, 0777, true);
        }

        // ID Card upload
        if (!empty($_FILES['id_card']['name'])) {
            $ext = strtolower(pathinfo($_FILES['id_card']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','pdf'];

            if (!in_array($ext, $allowed, true)) {
                $errors[] = 'Invalid ID card file type. Use JPG, PNG or PDF.';
            } else {
                $safeName = 'id_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                $target   = $idCardDir . $safeName;
                if (!move_uploaded_file($_FILES['id_card']['tmp_name'], $target)) {
                    $errors[] = 'Failed to upload ID card file.';
                } else {
                    $idCardFileName = $safeName;
                }
            }
        }

        // Avatar upload (optional)
        if (!$errors && !empty($_FILES['avatar']['name'])) {
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png'];

            if (!in_array($ext, $allowed, true)) {
                $errors[] = 'Invalid avatar file type. Use JPG or PNG.';
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
    }

    // If everything ok, insert into DB
    if (!$errors) {
        $stmt = $pdo->prepare("
            INSERT INTO users (full_name, username, email, phone, password_md5, role, id_card_path, avatar_path, is_active, created_at)
            VALUES (:full_name, :username, :email, :phone, :pwd, :role, :id_card, :avatar, 1, NOW())
        ");
        $stmt->execute([
            ':full_name' => $full_name,
            ':username'  => $username,
            ':email'     => $email,
            ':phone'     => $phone,
            ':pwd'       => md5($password),   // per school requirement
            ':role'      => $role,
            ':id_card'   => $idCardFileName,
            ':avatar'    => $avatarFileName
        ]);

        // Reset captcha so it can't be reused
        unset($_SESSION['reg_captcha_a'], $_SESSION['reg_captcha_b']);

        set_flash('success', 'Account created successfully. You can now log in.');
        redirect('login.php');
    } else {
        // regenerate captcha for next try
        $_SESSION['reg_captcha_a'] = rand(1, 9);
        $_SESSION['reg_captcha_b'] = rand(1, 9);
    }
}

include __DIR__ . '/../includes/header.php';

$a = $_SESSION['reg_captcha_a'] ?? 0;
$b = $_SESSION['reg_captcha_b'] ?? 0;
?>
<div class="form-card" style="max-width:520px;margin:40px auto;">
  <h2>Create Your Azurite Account</h2>
  <p style="margin-bottom:18px;font-size:14px;color:#6b7280;">
    Sign up as a <strong>Tenant</strong> to book houses or as a <strong>Host</strong> to list your property.
  </p>

  <?php if ($errors): ?>
    <div class="alert error">
      <ul>
      <?php foreach ($errors as $err): ?>
        <li><?php echo e($err); ?></li>
      <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <div class="form-row">
      <label>Full Name</label>
      <input type="text" name="full_name" required
             value="<?php echo e($full_name); ?>">
    </div>

    <div class="form-row">
      <label>Username</label>
      <input type="text" name="username" required
             value="<?php echo e($username); ?>">
    </div>

    <div class="form-row">
      <label>Email</label>
      <input type="email" name="email" required
             value="<?php echo e($email); ?>">
    </div>

    <div class="form-row">
      <label>Phone</label>
      <input type="text" name="phone"
             value="<?php echo e($phone); ?>">
    </div>

    <div class="form-row">
      <label>Register As</label>
      <select name="role" required>
        <option value="tenant" <?php echo $role === 'tenant' ? 'selected' : ''; ?>>Tenant / Client</option>
        <option value="host"   <?php echo $role === 'host' ? 'selected' : ''; ?>>Host / Property Owner</option>
      </select>
    </div>

    <div class="form-row">
      <label>Password</label>
      <input type="password" name="password" required>
    </div>

    <div class="form-row">
      <label>Confirm Password</label>
      <input type="password" name="confirm_password" required>
    </div>

    <div class="form-row">
      <label>ID Card (JPG/PNG/PDF) <span style="color:#dc2626;">*</span></label>
      <input type="file" name="id_card" accept=".jpg,.jpeg,.png,.pdf" required>
    </div>

    <div class="form-row">
      <label>Profile Photo (optional)</label>
      <input type="file" name="avatar" accept=".jpg,.jpeg,.png">
    </div>

    <div class="form-row">
      <label>Captcha: <?php echo $a . ' + ' . $b; ?> = ?</label>
      <input type="text" name="captcha" required placeholder="Your answer">
    </div>

    <button type="submit" class="btn-primary" style="width:100%;margin-top:10px;">
      Create Account
    </button>

    <p style="margin-top:12px;font-size:13px;text-align:center;">
      Already have an account?
      <a href="login.php">Log in here</a>
    </p>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
