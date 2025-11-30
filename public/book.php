<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

if (!is_logged_in()) {
    die("You must be logged in to book a room.");
}

$errors = [];

// Receive form input
$roomId        = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
$checkin_raw   = $_POST['checkin'] ?? '';
$checkout_raw  = $_POST['checkout'] ?? '';
$guests        = $_POST['guests'] ?? 1;
$contact_phone = trim($_POST['contact_phone'] ?? '');


// Validate room exists
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = :id AND is_active = 1");
$stmt->execute([':id' => $roomId]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    die("Invalid room selected.");
}


// Normalize dates
$cin  = date('Y-m-d', strtotime($checkin_raw));
$cout = date('Y-m-d', strtotime($checkout_raw)); // checkout is exclusive

// Prevent invalid dates
if (!$cin || !$cout) {
    $errors[] = "Invalid check-in or check-out date.";
}

if ($cin >= $cout) {
    $errors[] = "Check-out date must be after check-in date.";
}


// ----------------------------------------------------------------------
// 1. Check for BLOCKED DATES
// ----------------------------------------------------------------------
$blockCheck = $pdo->prepare("
    SELECT 1 
    FROM room_block_dates
    WHERE room_id = :room
      AND block_date >= :cin
      AND block_date < :cout
");
$blockCheck->execute([
    ':room' => $roomId,
    ':cin'  => $cin,
    ':cout' => $cout
]);

if ($blockCheck->fetch()) {
    $errors[] = "One or more selected dates are unavailable.";
}


// ----------------------------------------------------------------------
// 2. Prevent DOUBLE BOOKING
// ----------------------------------------------------------------------
$doubleCheck = $pdo->prepare("
    SELECT 1
    FROM bookings
    WHERE room_id = :room
      AND status IN ('Pending','Approved','Confirmed','CheckedIn')
      AND NOT (
            :cout <= checkin_date
        OR  :cin  >= checkout_date
      )
");
$doubleCheck->execute([
    ':room' => $roomId,
    ':cin'  => $cin,
    ':cout' => $cout
]);

if ($doubleCheck->fetch()) {
    $errors[] = "These dates are already booked by another guest.";
}


// ----------------------------------------------------------------------
// If errors → show message and stop
// ----------------------------------------------------------------------
if (!empty($errors)) {
    include __DIR__ . '/../includes/header.php';

    echo "<div class='container' style='padding:40px; max-width:700px;'>";
    echo "<h2>Booking Error</h2>";
    echo "<ul style='color:red;'>";
    foreach ($errors as $e) {
        echo "<li>$e</li>";
    }
    echo "</ul>";
    echo "<a href='room.php?id=$roomId' class='btn btn-primary'>Go Back</a>";
    echo "</div>";

    include __DIR__ . '/../includes/footer.php';
    exit;
}


// ----------------------------------------------------------------------
// 3. Calculate Price (simple nights × price)
// ----------------------------------------------------------------------
$nights = (strtotime($cout) - strtotime($cin)) / 86400;
$total = $nights * $room['price_ksh'];


// ----------------------------------------------------------------------
// 4. Insert booking into database
// ----------------------------------------------------------------------
$ref = "AZ-" . strtoupper(substr(md5(uniqid()), 0, 8));

$insert = $pdo->prepare("
    INSERT INTO bookings 
        (ref_code, room_id, tenant_id, contact_phone, checkin_date, checkout_date, guests, total_amount_ksh) 
    VALUES
        (:ref, :room, :tenant, :phone, :cin, :cout, :guests, :total)
");
$insert->execute([
    ':ref'    => $ref,
    ':room'   => $roomId,
    ':tenant' => $_SESSION['user']['id'],
    ':phone'  => $contact_phone,
    ':cin'    => $cin,
    ':cout'   => $cout,
    ':guests' => $guests,
    ':total'  => $total
]);


// ----------------------------------------------------------------------
// 5. Redirect to success page
// ----------------------------------------------------------------------
header("Location: booking_success.php?ref=$ref");
exit;
