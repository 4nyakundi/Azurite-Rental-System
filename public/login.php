<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = "Please enter username and password.";
    }

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !$user['is_active'] || !verify_password($password, $user['password_md5'])) {
            $errors[] = "Invalid username or password.";
        } else {
            if (password_needs_rehash($user['password_md5'], PASSWORD_DEFAULT)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE users SET password_md5=? WHERE id=?')->execute([$hash, $user['id']]);
            }
            session_regenerate_id(true);
            unset($user['password_md5']);
            // Login success
            $_SESSION['user'] = $user;

            // Smart redirect based on role
            if ($user['role'] === 'admin') {
                redirect('admin/index.php');
            }
            elseif ($user['role'] === 'host') {
                redirect('host/index.php');
            }
            else { // tenant
                redirect('index.php');
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="form-card" style="max-width:420px;margin:40px auto;">
  <h2>Login</h2>

  <?php if ($errors): ?>
    <div class="alert error">
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?php echo e($e); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post">
<?php echo csrf_field(); ?>
    <div class="form-row">
      <label>Username</label>
      <input type="text" name="username" value="<?php echo e($username); ?>" required>
    </div>

    <div class="form-row">
      <label>Password</label>
      <input type="password" name="password" required>
    </div>

    <button type="submit" class="btn-primary" style="width:100%;margin-top:10px;">
      Login
    </button>
  </form>

  <p style="margin-top:12px;font-size:13px;text-align:center;">
    Don’t have an account?
    <a href="register.php">Create one</a>
  </p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
