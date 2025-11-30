<?php
// public/host/toggle_day.php

require_once "../../includes/db.php";
require_once "../../includes/helpers.php";

// 1. Access Control: Must be a host
if (!is_host()) {
    die('Host only');
}

$hostId = current_user()['id'] ?? 0;
$roomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
$date   = $_GET['date'] ?? null;

// Return path for redirection after action
$return = $_GET['return'] ?? ("availability.php?room_id=" . $roomId); 

if (!$roomId || !$date) {
    die("Missing room/date.");
}

// --- 2. Security Check: Verify room belongs to this host (CRITICAL) ---
$stmt = $pdo->prepare("SELECT id FROM rooms WHERE id=? AND owner_id=?");
$stmt->execute([$roomId, $hostId]);
if (!$stmt->fetch()) {
    set_flash('error', "Security: You cannot modify this room.");
    header("Location: " . urldecode($return));
    exit;
}

// --- 3. Crucial Check: Prevent blocking an already booked date! ---
$bookedStmt = $pdo->prepare("
    SELECT COUNT(*) FROM bookings 
    WHERE room_id = ? AND status IN ('Confirmed', 'Approved', 'CheckedIn') 
    AND checkin_date <= ? AND checkout_date > ?
");
$bookedStmt->execute([$roomId, $date, $date]);
if ($bookedStmt->fetchColumn() > 0) {
    // If booked, redirect back without changing anything
    set_flash('error', "Cannot toggle status: Date is already booked/confirmed.");
    header("Location: " . urldecode($return));
    exit;
}

// --- 4. Toggle block record ---
// Check if the date is currently blocked
$check = $pdo->prepare("SELECT id FROM room_block_dates WHERE room_id=? AND block_date=?");
$check->execute([$roomId, $date]);
$row = $check->fetch(PDO::FETCH_ASSOC);

if ($row) {
    // If record exists, DELETE it (Unblock the day)
    $pdo->prepare("DELETE FROM room_block_dates WHERE id=?")->execute([$row['id']]);
    set_flash('success', "Date {$date} unblocked successfully.");
} else {
    // If record does not exist, INSERT it (Block the day)
    $pdo->prepare("INSERT INTO room_block_dates (room_id, block_date) VALUES (?,?)")
        ->execute([$roomId, $date]);
    set_flash('success', "Date {$date} blocked successfully.");
}

// --- 5. Redirect back to the calendar view ---
header("Location: " . urldecode($return));
exit;