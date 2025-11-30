<?php
require_once "../../includes/db.php";
require_once "../../includes/helpers.php";

if (!is_admin()) die("Unauthorized access.");

$bookingId = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT 
        b.*, 
        r.name AS room_name, r.location_text, r.price_ksh,
        u.full_name, u.email, u.phone, u.avatar_path, u.id_card_path
    FROM bookings b
    JOIN rooms r ON r.id = b.room_id
    JOIN users u ON u.id = b.tenant_id
    WHERE b.id = ?
");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) die("Booking not found.");

include "../../includes/admin_header.php";
?>

<style>
/* Layout */
.booking-container {
    padding: 40px;
}

.booking-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 30px;
}

/* Two-column grid */
.booking-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

/* Card styling */
.booking-card {
    background: #ffffff;
    padding: 30px;
    border-radius: 14px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}

.booking-card h3 {
    font-size: 22px;
    font-weight: 600;
    margin-bottom: 20px;
}

.booking-card p {
    margin: 8px 0;
    font-size: 15px;
    line-height: 1.5;
}

/* Image styling */
.booking-img {
    width: 140px;
    border-radius: 10px;
    border: 1px solid #ddd;
    margin-top: 5px;
}

.booking-img-id {
    width: 260px;
    border-radius: 8px;
    border: 1px solid #ccc;
    margin-top: 10px;
}

/* Back button */
.back-btn {
    padding: 10px 20px;
    background: #0d1b4c;
    color: #fff;
    border-radius: 8px;
    display: inline-block;
    margin-top: 35px;
    font-size: 14px;
}

.back-btn:hover {
    background: #0a153a;
    color: #fff;
}
</style>

<div class="booking-container">

    <h2 class="booking-title">
        Booking Details – <?php echo $booking['ref_code']; ?>
    </h2>

    <div class="booking-grid">

        <!-- Left Card -->
        <div class="booking-card">
            <h3>Stay Information</h3>

            <p><strong>Room:</strong> <?php echo e($booking['room_name']); ?></p>
            <p><strong>Location:</strong> <?php echo e($booking['location_text']); ?></p>

            <p><strong>Check-in:</strong> <?php echo e($booking['checkin_date']); ?></p>
            <p><strong>Check-out:</strong> <?php echo e($booking['checkout_date']); ?></p>

            <p><strong>Guests:</strong> <?php echo e($booking['guests']); ?></p>

            <p><strong>Status:</strong> <?php echo e($booking['status']); ?></p>

            <p><strong>Total Amount:</strong> 
                KSh <?php echo number_format($booking['total_amount_ksh']); ?>
            </p>

            <p><strong>Contact Phone:</strong> <?php echo e($booking['contact_phone']); ?></p>
        </div>

        <!-- Right Card -->
        <div class="booking-card">
            <h3>Tenant Information</h3>

            <p><strong>Name:</strong> <?php echo e($booking['full_name']); ?></p>
            <p><strong>Email:</strong> <?php echo e($booking['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo e($booking['phone']); ?></p>

            <p><strong>Avatar:</strong></p>
            <img 
                src="/Azurite-Rental-System/public/uploads/avatars/<?php echo $booking['avatar_path'] ?: 'default.png'; ?>" 
                class="booking-img"
            >

            <p><strong>ID Card:</strong></p>
            <img 
                src="/Azurite-Rental-System/public/uploads/id_cards/<?php echo $booking['id_card_path'] ?: 'no_id.png'; ?>" 
                class="booking-img-id"
            >
        </div>

    </div>

    <a href="bookings.php" class="back-btn">← Back to Bookings</a>
</div>

<?php include "../../includes/admin_footer.php"; ?>
