<?php
require_once __DIR__ . '/../../includes/helpers.php';
require_role('host');
confirm_mutation('Change availability for this day?');
require_once __DIR__ . '/../../includes/db.php';
$roomId = (int)($_POST['room_id'] ?? 0);
$date = (string)($_POST['date'] ?? '');
$return = 'availability.php?room_id=' . $roomId;
if (!valid_date($date) || $date < date('Y-m-d')) {
    set_flash('error', 'Choose a valid date today or in the future.');
    redirect($return);
}
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('SELECT id FROM rooms WHERE id=? AND owner_id=? FOR UPDATE');
    $stmt->execute([$roomId, current_user()['id']]);
    if (!$stmt->fetch()) throw new DomainException('You cannot change this house.');
    $stmt = $pdo->prepare("SELECT id FROM bookings WHERE room_id=? AND status IN ('Pending','Approved','Confirmed','CheckedIn') AND checkin_date<=? AND checkout_date>? LIMIT 1");
    $stmt->execute([$roomId, $date, $date]);
    if ($stmt->fetch()) throw new DomainException('This night is already reserved.');
    $stmt = $pdo->prepare('SELECT id FROM room_block_dates WHERE room_id=? AND block_date=?');
    $stmt->execute([$roomId, $date]);
    $block = $stmt->fetchColumn();
    if ($block) $pdo->prepare('DELETE FROM room_block_dates WHERE id=?')->execute([$block]);
    else $pdo->prepare('INSERT INTO room_block_dates (room_id,block_date) VALUES (?,?)')->execute([$roomId, $date]);
    $pdo->commit();
    set_flash('success', $block ? 'Night unblocked.' : 'Night blocked.');
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($exception instanceof DomainException) set_flash('error', $exception->getMessage());
    else { error_log($exception->getMessage()); set_flash('error', 'Unable to change availability. Please try again.'); }
}
redirect($return);
