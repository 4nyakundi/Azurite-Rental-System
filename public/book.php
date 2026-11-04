<?php
require_once __DIR__ . '/../includes/helpers.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit('Submit the booking form to reserve a stay.'); }
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/booking_service.php';
try {
    $ref = create_booking($pdo, $_POST, current_user());
    redirect('booking_success.php?ref=' . urlencode($ref));
} catch (DomainException $exception) {
    set_flash('error', $exception->getMessage());
    redirect('room.php?id=' . (int)($_POST['room_id'] ?? 0));
} catch (Throwable $exception) {
    error_log('Booking failed: ' . $exception->getMessage());
    set_flash('error', 'Unable to save this booking. Please try again.');
    redirect('rooms.php');
}
