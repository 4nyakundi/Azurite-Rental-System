<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    $sessionDirectory = __DIR__ . '/../storage/sessions';
    if (!is_dir($sessionDirectory)) mkdir($sessionDirectory, 0700, true);
    session_save_path($sessionDirectory);
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    session_start();
}
date_default_timezone_set('Africa/Nairobi');

function public_base() {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/public/index.php');
    $position = strrpos($script, '/public/');
    return $position === false ? '/' : substr($script, 0, $position) . '/public/';
}
function csrf_token() {
    return $_SESSION['csrf_token'] ?? ($_SESSION['csrf_token'] = bin2hex(random_bytes(32)));
}
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !hash_equals(csrf_token(), (string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    exit('Your session expired. Reload the form and try again.');
}
function valid_date($value) {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
}
function verify_password($password, $hash) {
    return preg_match('/^[a-f0-9]{32}$/i', $hash) ? hash_equals(strtolower($hash), md5($password)) : password_verify($password, $hash);
}
function save_upload($field, $directory, $allowPdf = false) {
    $file = $_FILES[$field] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) throw new RuntimeException('Upload failed. Choose a file smaller than 5 MB.');
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    if ($allowPdf) $types['application/pdf'] = 'pdf';
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($types[$mime])) throw new RuntimeException('Choose a JPG or PNG' . ($allowPdf ? ' or PDF' : '') . ' file.');
    if (!is_dir($directory) && !mkdir($directory, 0750, true)) throw new RuntimeException('Upload directory is unavailable.');
    $name = bin2hex(random_bytes(20)) . '.' . $types[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) throw new RuntimeException('Unable to save the uploaded file.');
    return $name;
}

// Keep bookmarked action links usable, but require an explicit protected POST.
function confirm_mutation($title) {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') return;
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') { http_response_code(405); exit; }
    $base = public_base();
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . e($title) . '</title><link rel="stylesheet" href="' . e($base) . 'assets/css/polish.css">';
    echo '<div class="form-card" style="max-width:480px;margin:80px auto"><h1>' . e($title) . '</h1><form method="post">' . csrf_field();
    foreach ($_GET as $key => $value) {
        if (is_scalar($value) && $key !== 'csrf_token') echo '<input type="hidden" name="' . e($key) . '" value="' . e($value) . '">';
    }
    echo '<button class="btn-primary" type="submit">Confirm</button> <a href="' . e($base) . 'index.php">Back to home</a></form></div></html>';
    exit;
}

/**
 * Base login URL (adjust folder name if needed)
 */
const LOGIN_URL = '/Azurite-Rental-System/public/login.php';

/**
 * Escape HTML
 */
function e($str) {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Format price in KSH
 */
function format_ksh($amount) {
    return 'KSh ' . number_format((float)$amount, 2);
}

/**
 * Simple redirect
 */
function redirect($url) {
    header("Location: {$url}");
    exit;
}

/**
 * FLASH MESSAGES
 */
function set_flash($key, $message) {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$key] = $message;
}

function flash($key) {
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

/**
 * AUTH HELPERS
 */
function is_logged_in() {
    return !empty($_SESSION['user']);
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function is_admin() {
    return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'admin');
}

function is_host() {
    return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'host');
}

function is_tenant() {
    return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'tenant');
}

function require_login() {
    if (!is_logged_in()) {
        redirect(public_base() . 'login.php');
    }
}

/**
 * Generic role helpers
 */
function has_role($role) {
    return is_logged_in() && (($_SESSION['user']['role'] ?? '') === $role);
}

function require_role($role) {
    if (!has_role($role)) {
        redirect(public_base() . 'login.php');
    }
}

/**
 * Generate random booking code
 */
function random_code($length = 8) {
    return strtoupper(substr(bin2hex(random_bytes(10)), 0, $length));
}


function get_room_name($pdo, $id) {
    $stmt = $pdo->prepare("SELECT name FROM rooms WHERE id=?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn() ?: "Room";
}
