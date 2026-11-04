<?php
require_once __DIR__ . '/../includes/helpers.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit; }
require_once __DIR__ . '/../includes/db.php';
$id = current_user()['id'];
$stmt = $pdo->prepare('SELECT * FROM users WHERE id=?');
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) { http_response_code(404); exit('Account not found.'); }
try {
    $name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if ($name === '' || $username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new DomainException('Enter your name, username and a valid email.');
    if (strlen($name) > 150 || strlen($username) > 80 || strlen($email) > 150 || strlen($phone) > 30) throw new DomainException('One or more profile fields are too long.');
    $stmt = $pdo->prepare('SELECT id FROM users WHERE (username=? OR email=?) AND id<>?');
    $stmt->execute([$username, $email, $id]);
    if ($stmt->fetch()) throw new DomainException('Username or email is already in use.');
    $hash = $user['password_md5'];
    if (!empty($_POST['new_password']) || !empty($_POST['current_password']) || !empty($_POST['confirm_password'])) {
        if (!verify_password($_POST['current_password'] ?? '', $hash)) throw new DomainException('Current password is incorrect.');
        $password = $_POST['new_password'] ?? '';
        if (strlen($password) < 8 || strlen($password) > 72 || strpos($password, "\0") !== false || $password !== ($_POST['confirm_password'] ?? '')) throw new DomainException('Use matching passwords between 8 and 72 characters.');
        $hash = password_hash($password, PASSWORD_DEFAULT);
    }
    $avatar = save_upload('avatar', __DIR__ . '/uploads/avatars') ?? $user['avatar_path'];
    $card = save_upload('id_card', __DIR__ . '/../storage/id_cards', true) ?? $user['id_card_path'];
    $pdo->prepare('UPDATE users SET full_name=?,username=?,email=?,phone=?,avatar_path=?,id_card_path=?,password_md5=? WHERE id=?')
        ->execute([$name, $username, $email, $phone, $avatar, $card, $hash, $id]);
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id=?');
    $stmt->execute([$id]);
    $_SESSION['user'] = $stmt->fetch();
    unset($_SESSION['user']['password_md5']);
    redirect('profile.php?success=1');
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    redirect('profile.php?error=' . urlencode('Unable to save your profile. Please try again.'));
} catch (DomainException | RuntimeException $exception) {
    redirect('profile.php?error=' . urlencode($exception->getMessage()));
}
