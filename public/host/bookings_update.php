<?php
require_once __DIR__ . '/../../includes/helpers.php';
require_role('host');
confirm_mutation('Update this booking?');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/booking_service.php';
$id = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? (['approve'=>'Approved','decline'=>'Declined'][$_POST['action'] ?? ''] ?? '');
try {
    update_booking_status($pdo, $id, $status, current_user());
    set_flash('success', 'Booking status updated.');
} catch (DomainException $exception) {
    set_flash('error', $exception->getMessage());
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    set_flash('error', 'Unable to update this booking. Please try again.');
}
redirect('bookings.php');
