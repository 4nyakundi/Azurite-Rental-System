<?php
// public/admin/room_view.php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (!is_admin()) {
    die("Admin only.");
}

$roomId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Load room details + host
$stmt = $pdo->prepare("
    SELECT r.*,
           c.name AS category,
           u.full_name   AS host_name,
           u.email       AS host_email,
           u.phone       AS host_phone,
           u.avatar_path AS host_avatar
    FROM rooms r
    LEFT JOIN room_categories c ON c.id = r.category_id
    LEFT JOIN users u ON u.id = r.owner_id
    WHERE r.id = :id
");
$stmt->execute([':id' => $roomId]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    die("Room not found.");
}

// Load room photos
$photosStmt = $pdo->prepare("
    SELECT image_path 
    FROM room_photos 
    WHERE room_id = :id 
    ORDER BY sort_order
");
$photosStmt->execute([':id' => $roomId]);
$photos = $photosStmt->fetchAll(PDO::FETCH_ASSOC);

// Load bookings for this room
$bookStmt = $pdo->prepare("
    SELECT b.*,
           u.full_name AS tenant_name,
           u.email     AS tenant_email
    FROM bookings b
    JOIN users u ON u.id = b.tenant_id
    WHERE b.room_id = :id
    ORDER BY b.created_at DESC
");
$bookStmt->execute([':id' => $roomId]);
$bookings = $bookStmt->fetchAll(PDO::FETCH_ASSOC);

// Admin layout header
include __DIR__ . '/../../includes/admin_header.php';
?>

<style>
.room-card {
    background:#fff;
    padding:25px;
    border-radius:18px;
    box-shadow:0 4px 18px rgba(0,0,0,0.06);
    margin-bottom:30px;
}
.gallery-strip {
    display:flex;
    gap:12px;
    overflow-x:auto;
    padding-bottom:6px;
}
.gallery-strip img {
    height:120px;
    border-radius:10px;
    box-shadow:0 3px 10px rgba(0,0,0,0.15);
}
.host-box {
    display:flex;
    align-items:center;
    gap:15px;
}
.host-box img {
    width:70px;
    height:70px;
    border-radius:50%;
    object-fit:cover;
    border:3px solid #eef;
}
.booking-row {
    background:#fff;
    padding:15px;
    margin-bottom:12px;
    border-radius:14px;
    box-shadow:0 3px 12px rgba(0,0,0,0.06);
}
.booking-row small {
    color:#666;
}
</style>

<div class="section-heading">
    <h2>Room Details</h2>
</div>

<div class="room-card">

    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:20px;flex-wrap:wrap;">
        <div>
            <h2 style="margin-bottom:5px;"><?php echo e($room['name']); ?></h2>
            <p style="color:#666;">Category: <?php echo e($room['category']); ?></p>
            <p style="color:#666;">Location: <?php echo e($room['location_text']); ?></p>
            <p style="font-size:20px; font-weight:600;margin-top:10px;">
                KSh <?php echo number_format($room['price_ksh'], 2); ?> / night
            </p>
        </div>
        <div>
            <a href="room_edit.php?id=<?php echo $roomId; ?>" class="btn btn-primary btn-sm">Edit Room</a>
        </div>
    </div>

    <img src="<?php echo ($room['cover_image'])
                ? '../uploads/room_photos/' . e($room['cover_image'])
                : '../assets/img/outside_2.jpg'; ?>"
         style="width:100%; max-height:350px; object-fit:cover; border-radius:18px; margin-top:20px;">

    <?php if ($photos): ?>
    <h3 style="margin-top:25px;">Gallery</h3>
    <div class="gallery-strip">
        <?php foreach ($photos as $p): ?>
            <img src="../uploads/room_photos/<?php echo e($p['image_path']); ?>" alt="Photo">
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($room['description'])): ?>
        <h3 style="margin-top:25px;">Description</h3>
        <p><?php echo nl2br(e($room['description'])); ?></p>
    <?php endif; ?>

</div>

<!-- HOST INFO -->
<div class="room-card">
    <h3>Host Information</h3>

    <div class="host-box">
        <?php if ($room['host_avatar']): ?>
            <img src="../uploads/avatars/<?php echo e($room['host_avatar']); ?>" alt="Host">
        <?php else: ?>
            <img src="../assets/img/default-avatar.png" alt="Host">
        <?php endif; ?>

        <div>
            <p><strong><?php echo e($room['host_name']); ?></strong></p>
            <p style="color:#666;"><?php echo e($room['host_email']); ?></p>
            <p style="color:#666;">Phone: <?php echo e($room['host_phone']); ?></p>
        </div>
    </div>
</div>

<!-- BOOKINGS FOR THIS ROOM -->
<div class="room-card">
    <h3>Bookings for this Room</h3>

    <?php if (!$bookings): ?>
        <p>No bookings for this room yet.</p>
    <?php else: ?>
        <?php foreach ($bookings as $b): ?>
        <div class="booking-row">
            <strong><?php echo e($b['tenant_name']); ?></strong>
            <span style="color:#777;">(<?php echo e($b['tenant_email']); ?>)</span><br>

            <small>Ref:</small> <?php echo e($b['ref_code']); ?><br>
            <small>Check-in:</small> <?php echo e($b['checkin_date']); ?><br>
            <small>Check-out:</small> <?php echo e($b['checkout_date']); ?><br>
            <small>Guests:</small> <?php echo (int)$b['guests']; ?><br>
            <small>Status:</small> <strong><?php echo e($b['status']); ?></strong><br>
            <small>Total:</small> KSh <?php echo number_format($b['total_amount_ksh'], 2); ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- AVAILABILITY CALENDAR PREVIEW -->
<div class="room-card">
    <h3>Availability Calendar</h3>

    <iframe 
        src="/Azurite-Rental-System/public/admin/availability.php?id=<?php echo $roomId; ?>"
        style="width:100%; height:420px; border:0; border-radius:15px;">
    </iframe>
</div>

<?php
// Admin layout footer
include __DIR__ . '/../../includes/admin_footer.php';
?>
