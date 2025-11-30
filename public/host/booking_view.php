<?php
// public/host/booking_view.php
require_once "../../includes/db.php";
require_once "../../includes/helpers.php";
require_once "header.php";


// Ensure only hosts can access this page
if (!is_host()) { die('Host only'); }

$host = current_user();
$bookingId = (int)($_GET['id'] ?? 0);

// Fetch booking ensuring room belongs to this host
$stmt = $pdo->prepare("
    SELECT 
        b.*, 
        r.name AS room_name,
        r.location_text AS room_location,
        u.full_name AS tenant_name,
        u.email AS tenant_email,
        u.phone AS tenant_phone,
        u.avatar_path,
        u.id_card_path
    FROM bookings b
    JOIN rooms r ON r.id = b.room_id
    JOIN users u ON u.id = b.tenant_id
    WHERE b.id = ?
      AND r.owner_id = ?
");
$stmt->execute([$bookingId, $host['id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    // Used consistent styling with the host-card for error message
    echo "<div class='host-card'><p>Booking not found or does not belong to your property.</p></div>";
    require_once "footer.php";
    exit;
}

$avatarUrl = "../uploads/avatars/" . ($booking['avatar_path'] ?: "default.png");
$idCardUrl = "../uploads/id_cards/" . ($booking['id_card_path'] ?: "no_id.png");

// Allowed status transitions for host - matches the list in the status form
// Note: Host typically cannot set 'Completed' (done by system/admin) or 'CheckedIn' (done by system/host)
$statuses = ['Pending','Approved','Declined','Confirmed','CheckedIn','Cancelled'];
?>

<div class="host-card">
    <h2>Booking Details – <?php echo e($booking['ref_code']); ?></h2>

    <div style="display:flex;gap:30px;flex-wrap:wrap;margin-top:15px;">
        <div style="flex:1;min-width:260px;">
            <h3>Stay Information</h3>
            <p><strong>House:</strong> <?php echo e($booking['room_name']); ?></p>
            <p><strong>Location:</strong> <?php echo e($booking['room_location']); ?></p>
            <p><strong>Check-in:</strong> <?php echo e($booking['checkin_date']); ?></p>
            <p><strong>Check-out:</strong> <?php echo e($booking['checkout_date']); ?></p>
            <p><strong>Guests:</strong> <?php echo (int)$booking['guests']; ?></p>
            <p><strong>Total:</strong> <?php echo format_ksh($booking['total_amount_ksh']); ?></p>
            <p><strong>Status:</strong> <span style="font-weight:bold; color: <?php echo $booking['status'] === 'Approved' ? 'green' : ($booking['status'] === 'Declined' ? 'red' : 'orange'); ?>;"><?php echo e($booking['status']); ?></span></p>

            <form method="post" action="bookings_update.php" style="margin-top:20px; border-top: 1px solid #eee; padding-top: 15px;">
                <input type="hidden" name="id" value="<?php echo $bookingId; ?>">
                <label style="font-weight:600;">Update Status</label>
                <select name="status" class="form-control" style="max-width:220px; margin-right: 10px; display: inline-block;">
                    <?php foreach ($statuses as $st): ?>
                        <option value="<?php echo $st; ?>" <?php echo $booking['status']==$st?'selected':''; ?>>
                            <?php echo $st; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary btn-sm" style="margin-top:0;">Save Status</button>
            </form>
            </div>

        <div style="flex:1;min-width:260px;">
            <h3>Tenant Information (For Verification)</h3>
            <p><strong>Name:</strong> <?php echo e($booking['tenant_name']); ?></p>
            <p><strong>Email:</strong> <?php echo e($booking['tenant_email']); ?></p>
            <p><strong>Phone:</strong> <?php echo e($booking['tenant_phone']); ?></p>

            <p style="margin-top:15px; font-weight:600;">Avatar:</p>
            <img src="<?php echo $avatarUrl; ?>" style="width:120px;height:120px;border-radius:10px;border:1px solid #ddd;object-fit:cover;">

            <p style="margin-top:15px; font-weight:600;">ID Card:</p>
            <img src="<?php echo $idCardUrl; ?>" style="width:260px;border-radius:8px;border:1px solid #ccc;">
            <p style="margin-top:5px;"><small>Verify this ID before Approving.</small></p>
        </div>
    </div>

    <a href="bookings.php" class="btn btn-secondary" style="margin-top:25px;">← Back to Bookings</a>
</div>

<?php require_once "footer.php"; ?>