<?php
// public/room_availability.php - Iframe target for public room view

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Get the room ID being viewed
$roomId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$roomId) {
    // If no ID is passed, show a graceful error
    echo "<p style='padding: 20px; color: #cc0000;'>Error: Room ID not specified.</p>";
    return;
}

// Check if the room exists and is active (Public-facing check)
$stmt = $pdo->prepare("SELECT id FROM rooms WHERE id = :id AND is_active = 1");
$stmt->execute([':id' => $roomId]);
if (!$stmt->fetch()) {
    echo "<p style='padding: 20px; color: #cc0000;'>Room not found or is currently inactive.</p>";
    return;
}

// Include the read-only calendar component
include __DIR__ . "/../includes/calendar_read_only.php";