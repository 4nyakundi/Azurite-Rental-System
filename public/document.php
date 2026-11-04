<?php
require_once __DIR__ . '/../includes/helpers.php';
require_login();
require_once __DIR__ . '/../includes/db.php';
$name = (string)($_GET['file'] ?? '');
if ($name === '' || basename($name) !== $name) { http_response_code(404); exit('Document not found.'); }
$stmt = $pdo->prepare('SELECT id FROM users WHERE id_card_path=?');
$stmt->execute([$name]);
$owner = $stmt->fetchColumn();
$allowed = $owner && (is_admin() || (int)$owner === (int)current_user()['id']);
if (!$allowed && $owner && is_host()) {
    $stmt = $pdo->prepare('SELECT b.id FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.tenant_id=? AND r.owner_id=? LIMIT 1');
    $stmt->execute([$owner, current_user()['id']]);
    $allowed = (bool)$stmt->fetchColumn();
}
$path = __DIR__ . '/../storage/id_cards/' . $name;
if (!is_file($path)) $path = __DIR__ . '/uploads/id_cards/' . $name;
if (!$allowed || !is_file($path)) { http_response_code(404); exit('Document not found.'); }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
if (!in_array($mime, ['image/jpeg', 'image/png', 'application/pdf'], true)) { http_response_code(415); exit; }
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="identity-document.' . pathinfo($name, PATHINFO_EXTENSION) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($path);
