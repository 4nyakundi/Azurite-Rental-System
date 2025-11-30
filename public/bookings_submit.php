<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

if (!is_logged_in()) redirect('login.php');

$userId = $_SESSION['user']['id'];

$roomId  = (int)($_POST['room_id'] ?? 0);
$checkin = $_POST['checkin'] ?? '';
$checkout = $_POST['checkout'] ?? '';
$guests = (int)($_POST['guests'] ?? 1);

// Basic validation
if (!$roomId || !$checkin || !$checkout) {
    set_flash('error', 'Missing booking details.');
    redirect('rooms.php');
}

if (strtotime($checkout) <= strtotime($checkin)) {
    set_flash('error', 'Checkout must be after checkin.');
    redirect('room.php?id=' . $roomId);
}

// Load room info
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = :id AND is_active = 1");
$stmt->execute([':id' => $roomId]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    set_flash('error', 'Room not found.');
    redirect('rooms.php');
}

// Check for existing booking overlap
$overlap = $pdo->prepare("
    SELECT COUNT(*)
    FROM bookings
    WHERE room_id = :room
    AND status IN ('Pending','Approved','Confirmed','CheckedIn')
    AND (
       (checkin_date <= :co AND checkout_date >= :ci)
    )
");
$overlap->execute([
    ':room' => $roomId,
    ':ci' => $checkin,
    ':co' => $checkout
]);

if ($overlap->fetchColumn() > 0) {
    set_flash('error', 'These dates are not available.');
    redirect('room.php?id=' . $roomId);
}

// Calculate price
$nights = (strtotime($checkout) - strtotime($checkin)) / 86400;
if ($nights < 1) $nights = 1;

$total = $nights * $room['price_ksh'];

// Insert booking
$ref = random_code();

$stmt = $pdo->prepare("
    INSERT INTO bookings (ref_code, room_id, tenant_id, contact_phone, checkin_date, checkout_date, guests, total_amount_ksh, status, created_at)
    VALUES (:ref, :room, :tenant, '', :ci, :co, :guests, :total, 'Pending', NOW())
");
$stmt->execute([
    ':ref' => $ref,
    ':room' => $roomId,
    ':tenant' => $userId,
    ':ci' => $checkin,
    ':co' => $checkout,
    ':guests' => $guests,
    ':total' => $total
]);

$newBookingId = $pdo->lastInsertId();

// Redirect to success page
redirect('booking_success.php?ref=' . $ref);
