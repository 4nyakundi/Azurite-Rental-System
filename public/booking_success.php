<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

require_login();

if (!isset($_GET['ref'])) {
    die("Invalid booking reference.");
}

$ref = $_GET['ref'];

// Fetch booking details
$stmt = $pdo->prepare("
    SELECT b.*, r.name AS room_name, r.location_text, r.price_ksh, u.full_name
    FROM bookings b
    LEFT JOIN rooms r ON r.id = b.room_id
    LEFT JOIN users u ON u.id = b.tenant_id
    WHERE b.ref_code = :ref AND b.tenant_id = :tenant
");
$stmt->execute([':ref' => $ref, ':tenant' => current_user()['id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) die("Booking not found.");

include __DIR__ . '/../includes/header.php';
?>

<style>
.success-container {
    max-width: 750px;
    margin: auto;
    padding: 40px;
}

.success-card {
    background: #fff;
    padding: 35px;
    border-radius: 22px;
    box-shadow: 0 6px 25px rgba(0,0,0,0.07);
    text-align: center;
}

.success-icon {
    font-size: 70px;
    color: #22bb33;
    margin-bottom: 20px;
}

.success-title {
    font-size: 30px;
    font-weight: 700;
    margin-bottom: 10px;
}

.success-sub {
    color: #666;
    margin-bottom: 30px;
    font-size: 17px;
}

.details-box {
    margin-top: 25px;
    text-align: left;
    padding: 25px;
    border-radius: 18px;
    background: #f8f9fb;
}

.details-box p {
    font-size: 16px;
    margin: 8px 0;
}

.big-ref {
    font-size: 22px;
    font-weight: 600;
    color: #1a2c59;
}

.btn-row {
    margin-top: 30px;
    display: flex;
    justify-content: center;
    gap: 20px;
}

.btn-main {
    background: #1a2c59;
    color: #fff;
    padding: 12px 28px;
    border-radius: 12px;
    text-decoration: none;
    font-size: 16px;
    font-weight: 600;
}

.btn-main:hover {
    background: #152245;
}

.btn-outline {
    border: 2px solid #1a2c59;
    padding: 12px 28px;
    border-radius: 12px;
    text-decoration: none;
    font-size: 16px;
    color: #1a2c59;
    font-weight: 600;
}

.btn-outline:hover {
    background: #1a2c59;
    color: #fff;
}
</style>

<div class="success-container">

    <div class="success-card">

        <div class="success-icon">✔️</div>

        <div class="success-title">Booking Request Received</div>
        <div class="success-sub">Your request has been saved. Track approval in My Bookings.</div>

        <p class="big-ref">Booking Reference: <?php echo e($ref); ?></p>

        <div class="details-box">
            <p><strong>Guest:</strong> <?php echo e($booking['full_name']); ?></p>
            <p><strong>Room:</strong> <?php echo e($booking['room_name']); ?></p>
            <p><strong>Location:</strong> <?php echo e($booking['location_text']); ?></p>
            <p><strong>Check-in:</strong> <?php echo e($booking['checkin_date']); ?></p>
            <p><strong>Check-out:</strong> <?php echo e($booking['checkout_date']); ?></p>
            <p><strong>Status:</strong> <?php echo e($booking['status']); ?></p>
            <p><strong>Guests:</strong> <?php echo (int)$booking['guests']; ?></p>
            <p><strong>Total Price:</strong> KSh <?php echo number_format($booking['total_amount_ksh'], 2); ?></p>
        </div>

        <div class="btn-row">
            <a href="index.php" class="btn-main">Back to Home</a>
            <a href="my_bookings.php" class="btn-outline">View My Bookings</a>
        </div>

    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
