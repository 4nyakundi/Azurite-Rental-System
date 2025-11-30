<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Base login URL (adjust folder name if needed)
 */
const LOGIN_URL = '/Azurite-Rental-System/public/login.php';

/**
 * Escape HTML
 */
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
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
        redirect(LOGIN_URL);
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
        redirect(LOGIN_URL);
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
