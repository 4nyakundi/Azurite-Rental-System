<?php
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';

if (!is_host()) die("Host only");

$hostId = $_SESSION['user']['id'];
$bookingId = 0;
$newStatus = '';
$actionNote = '';

// --- 1. Determine Request Type and New Status ---

// Case A: POST request from booking_view.php (Dropdown selection)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId = (int)($_POST['id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $actionNote = 'Status update via detail form';

    // Host allowed statuses (must match booking_view.php's list)
    $allowed = ['Pending','Approved','Declined','Confirmed','CheckedIn','Cancelled'];
    if (!in_array($newStatus, $allowed, true)) {
        set_flash('error', "Invalid status selected: " . e($newStatus));
        redirect('bookings.php');
    }

// Case B: GET request from bookings.php (Approve/Decline links)
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $bookingId = (int)($_GET['id'] ?? 0);
    $action = $_GET['action'] ?? '';

    if (!in_array($action, ['approve','decline'])) {
        die("Invalid action.");
    }

    $newStatus = $action === 'approve' ? 'Approved' : 'Declined';
    $actionNote = $action . " via quick link";

}

// Exit if no valid ID was found
if (!$bookingId) {
    die("Invalid request.");
}

// --- 2. Confirm booking belongs to host (Crucial Security Check) ---
$sql = "
SELECT b.*, r.owner_id
FROM bookings b
JOIN rooms r ON r.id = b.room_id
WHERE b.id = :id AND r.owner_id = :host
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $bookingId, ':host' => $hostId]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    set_flash('error', "Booking not found or not yours.");
    redirect('bookings.php');
}

$oldStatus = $booking['status'];

// Check if status is actually changing to avoid unnecessary updates
if ($oldStatus === $newStatus) {
    set_flash('info', "Booking status is already $newStatus.");
    // Redirect back to view page if it was a POST request
    $redirectUrl = ($_SERVER['REQUEST_METHOD'] === 'POST') ? 'booking_view.php?id=' . $bookingId : 'bookings.php';
    redirect($redirectUrl);
}

// --- 3. Update status ---
$pdo->prepare("UPDATE bookings SET status = :s WHERE id = :id")
    ->execute([':s'=>$newStatus, ':id'=>$bookingId]);

// --- 4. Log event (Audit Trail) ---
$pdo->prepare("
INSERT INTO booking_events (booking_id, actor_id, old_status, new_status, note, created_at)
VALUES (:bid, :actor, :old, :new, :note, NOW())
")->execute([
  ':bid'   => $bookingId,
  ':actor' => $hostId,
  ':old'   => $oldStatus,
  ':new'   => $newStatus,
  ':note'  => $actionNote . " by host"
]);

set_flash('success', "Booking status changed from $oldStatus to $newStatus.");

// Redirect back to the view page if it was a POST, otherwise list view
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect('booking_view.php?id=' . $bookingId);
} else {
    redirect('bookings.php');
}