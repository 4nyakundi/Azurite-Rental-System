<?php
require_once __DIR__ . '/helpers.php';

function create_booking(PDO $pdo, array $input, array $user) {
    $roomId = filter_var($input['room_id'] ?? null, FILTER_VALIDATE_INT);
    $cin = (string)($input['checkin'] ?? '');
    $cout = (string)($input['checkout'] ?? '');
    $guests = filter_var($input['guests'] ?? null, FILTER_VALIDATE_INT);
    $phone = trim((string)($input['contact_phone'] ?? ''));
    if (($user['role'] ?? '') !== 'tenant') throw new DomainException('Please use a tenant account to book a stay.');
    if (!$roomId || !valid_date($cin) || !valid_date($cout) || $cin < date('Y-m-d') || $cout <= $cin) {
        throw new DomainException('Choose valid future dates, with check-out after check-in.');
    }
    if (!$guests || $guests < 1 || $guests > 4) throw new DomainException('Choose between 1 and 4 guests.');
    if (!preg_match('/^[+0-9 ()-]{7,30}$/', $phone)) throw new DomainException('Enter a valid contact phone number.');
    $pdo->beginTransaction();
    try {
        // Serialize all availability mutations using the parent room row.
        $stmt = $pdo->prepare('SELECT * FROM rooms WHERE id=? AND is_active=1 FOR UPDATE');
        $stmt->execute([$roomId]);
        $room = $stmt->fetch();
        if (!$room) throw new DomainException('This house is no longer available.');
        $stmt = $pdo->prepare('SELECT id FROM room_block_dates WHERE room_id=? AND block_date>=? AND block_date<? LIMIT 1');
        $stmt->execute([$roomId, $cin, $cout]);
        if ($stmt->fetch()) throw new DomainException('One or more selected nights are blocked by the host.');
        $stmt = $pdo->prepare("SELECT id FROM bookings WHERE room_id=? AND status IN ('Pending','Approved','Confirmed','CheckedIn') AND checkin_date<? AND checkout_date>? LIMIT 1");
        $stmt->execute([$roomId, $cout, $cin]);
        if ($stmt->fetch()) throw new DomainException('These dates are already booked. Please choose another stay.');
        $nights = (new DateTimeImmutable($cin))->diff(new DateTimeImmutable($cout))->days;
        $ref = 'AZ-' . bin2hex(random_bytes(12));
        $stmt = $pdo->prepare("INSERT INTO bookings (ref_code,room_id,tenant_id,contact_phone,checkin_date,checkout_date,guests,total_amount_ksh,status) VALUES (?,?,?,?,?,?,?,?,'Pending')");
        $stmt->execute([$ref, $roomId, $user['id'], $phone, $cin, $cout, $guests, $nights * $room['price_ksh']]);
        $pdo->commit();
        return $ref;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    }
}

function update_booking_status(PDO $pdo, $id, $status, array $actor) {
    if (!in_array($status, ['Pending','Approved','Declined','Confirmed','CheckedIn','Completed','Cancelled'], true)) throw new DomainException('Invalid booking status.');
    $stmt = $pdo->prepare('SELECT room_id FROM bookings WHERE id=?');
    $stmt->execute([$id]);
    $roomId = $stmt->fetchColumn();
    if (!$roomId) throw new DomainException('Booking not found.');
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT owner_id FROM rooms WHERE id=? FOR UPDATE');
        $stmt->execute([$roomId]);
        $owner = $stmt->fetchColumn();
        if (($actor['role'] ?? '') !== 'admin' && !(($actor['role'] ?? '') === 'host' && (int)$owner === (int)$actor['id'])) throw new DomainException('You cannot change this booking.');
        $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id=? FOR UPDATE');
        $stmt->execute([$id]);
        $booking = $stmt->fetch();
        if (in_array($status, ['Pending','Approved','Confirmed','CheckedIn'], true)) {
            $stmt = $pdo->prepare("SELECT id FROM bookings WHERE room_id=? AND id<>? AND status IN ('Pending','Approved','Confirmed','CheckedIn') AND checkin_date<? AND checkout_date>? LIMIT 1");
            $stmt->execute([$roomId, $id, $booking['checkout_date'], $booking['checkin_date']]);
            if ($stmt->fetch()) throw new DomainException('This change conflicts with another booking.');
            $stmt = $pdo->prepare('SELECT id FROM room_block_dates WHERE room_id=? AND block_date>=? AND block_date<? LIMIT 1');
            $stmt->execute([$roomId, $booking['checkin_date'], $booking['checkout_date']]);
            if ($stmt->fetch()) throw new DomainException('The host has blocked one of these nights.');
        }
        if ($booking['status'] !== $status) {
            $pdo->prepare('UPDATE bookings SET status=? WHERE id=?')->execute([$status, $id]);
            $pdo->prepare('INSERT INTO booking_events (booking_id,actor_id,old_status,new_status,note) VALUES (?,?,?,?,?)')->execute([$id, $actor['id'], $booking['status'], $status, 'Status changed by ' . $actor['role']]);
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    }
}
