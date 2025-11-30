<?php
require_once "../includes/db.php";
require_once "../includes/helpers.php";

if (!is_logged_in()) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];

// Fetch current user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: profile.php?error=User not found");
    exit;
}

// Get POST data
$full_name = trim($_POST['full_name']);
$username  = trim($_POST['username']);
$email     = trim($_POST['email']);
$phone     = trim($_POST['phone']);

// ---------- VALIDATIONS ----------
$checkUser = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
$checkUser->execute([$username, $email, $userId]);
if ($checkUser->fetch()) {
    header("Location: profile.php?error=Username or email already in use");
    exit;
}

// ---------- HANDLE AVATAR UPLOAD ----------
$avatarName = $user['avatar_path'];
if (!empty($_FILES['avatar']['name'])) {
    $avatarName = "avatar_" . time() . "_" . rand(1000,9999) . ".jpg";
    move_uploaded_file($_FILES['avatar']['tmp_name'], "../uploads/avatars/" . $avatarName);
}

// ---------- HANDLE ID CARD UPLOAD ----------
$idCardName = $user['id_card_path'];
if (!empty($_FILES['id_card']['name'])) {
    $idCardName = "idcard_" . time() . "_" . rand(1000,9999) . ".jpg";
    move_uploaded_file($_FILES['id_card']['tmp_name'], "../uploads/id_cards/" . $idCardName);
}

// ---------- HANDLE PASSWORD CHANGE ----------
if (!empty($_POST['current_password'])) {
    $old = md5($_POST['current_password']);
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($old !== $user['password_md5']) {
        header("Location: profile.php?error=Current password is incorrect");
        exit;
    }

    if ($new !== $confirm) {
        header("Location: profile.php?error=Passwords do not match");
        exit;
    }

    $newHash = md5($new);

    $pdo->prepare("UPDATE users SET password_md5=? WHERE id=?")
        ->execute([$newHash, $userId]);
}

// ---------- UPDATE USER INFO ----------
$update = $pdo->prepare("
    UPDATE users 
    SET full_name=?, username=?, email=?, phone=?, avatar_path=?, id_card_path=?
    WHERE id=?
");

$update->execute([
    $full_name, $username, $email, $phone,
    $avatarName, $idCardName,
    $userId
]);

header("Location: profile.php?success=1");
exit;
