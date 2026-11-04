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
    if (strlen($password) < 8 || strlen($password) > 72 || strpos($password, "\0") !== false) $errors[] = 'Password should be between 8 and 72 characters.';
    if (strlen($full_name) > 150 || strlen($username) > 80 || strlen($email) > 150 || strlen($phone) > 30) $errors[] = 'One or more account fields are too long.';
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

    if (!$errors) {
        try {
            $idCardFileName = save_upload('id_card', __DIR__ . '/../storage/id_cards', true);
            $avatarFileName = save_upload('avatar', __DIR__ . '/uploads/avatars');
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }
    }

    // If everything ok, insert into DB
    if (!$errors) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (full_name, username, email, phone, password_md5, role, id_card_path, avatar_path, is_active, created_at)
                VALUES (:full_name, :username, :email, :phone, :pwd, :role, :id_card, :avatar, 1, NOW())
            ");
            $stmt->execute([
                ':full_name' => $full_name,
                ':username'  => $username,
                ':email'     => $email,
                ':phone'     => $phone,
                ':pwd'       => password_hash($password, PASSWORD_DEFAULT),
                ':role'      => $role,
                ':id_card'   => $idCardFileName,
                ':avatar'    => $avatarFileName
            ]);

            // Reset captcha so it can't be reused
            unset($_SESSION['reg_captcha_a'], $_SESSION['reg_captcha_b']);

            set_flash('success', 'Account created successfully. You can now log in.');
            redirect('login.php');
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $errors[] = 'Unable to create your account. Please check your details and try again.';
        }
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
<?php echo csrf_field(); ?>
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
