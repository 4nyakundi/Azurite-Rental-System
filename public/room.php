<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$roomId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT r.*, c.name AS category, u.full_name AS host_name
    FROM rooms r
    LEFT JOIN room_categories c ON c.id = r.category_id
    LEFT JOIN users u ON u.id = r.owner_id
    WHERE r.id = :id AND r.is_active = 1
");
$stmt->execute([':id' => $roomId]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) die("Room not found");

// Fetch room photos
$photosStmt = $pdo->prepare("SELECT image_path FROM room_photos WHERE room_id = :id ORDER BY sort_order");
$photosStmt->execute([':id' => $roomId]);
$photos = $photosStmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<style>
/* MAIN PAGE STYLING */
.room-hero-img {
    width: 100%;
    height: 420px;
    object-fit: cover;
    border-radius: 20px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
}

.gallery-strip {
    margin-top: 15px;
    display: flex;
    gap: 12px;
    overflow-x: auto;
    padding-bottom: 10px;
}
.gallery-strip img {
    height: 140px;
    width: auto;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.section-card {
    background: #fff;
    padding: 25px;
    border-radius: 18px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    margin-bottom: 35px;
}

.section-title {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 15px;
}

.room-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 28px;
    margin-top: 30px;
}

/* BOOKING FORM CARD */
.booking-box {
    background: #ffffff;
    padding: 25px;
    border-radius: 18px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}

.booking-box h3 {
    font-size: 20px;
    margin-bottom: 10px;
}

.booking-box label {
    font-weight: 600;
    margin-bottom: 4px;
    display: block;
}

.booking-box input,
.booking-box select {
    width: 100%;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid #ccc;
    margin-bottom: 14px;
}

.btn-book {
    width: 100%;
    padding: 12px;
    font-size: 16px;
    background: #1a2c59;
    border: none;
    color: #fff;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
}
.btn-book:hover { background:#122040; }

/* Availability iframe container */
.calendar-box {
    background: #fff;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.07);
}
</style>


<div class="container" style="max-width:1100px; margin:auto; padding-top:30px;">

    <!-- HERO IMAGE -->
    <img src="<?php echo ($room['cover_image'])
            ? 'uploads/room_photos/' . e($room['cover_image'])
            : 'assets/img/outside_2.jpg'; ?>"
         alt="Room Cover" class="room-hero-img">

    <!-- GALLERY PHOTOS -->
    <?php if ($photos): ?>
    <div class="gallery-strip">
        <?php foreach ($photos as $p): ?>
            <img src="uploads/room_photos/<?php echo e($p['image_path']); ?>" alt="Room Photo">
        <?php endforeach; ?>
    </div>
    <?php endif; ?>



    <!-- ROOM TITLE -->
    <h1 style="font-size:32px; margin-top:30px; font-weight:700;">
        <?php echo e($room['name']); ?>
    </h1>

    <p style="font-size:16px; color:#777; margin-top:-8px;">
        <?php echo e($room['location_text']); ?>
    </p>


    <!-- 2-COLUMN CONTENT -->
    <div class="room-info-grid">

        <!-- LEFT COLUMN: DETAILS -->
        <div class="section-card">
            <div class="section-title">Details</div>

            <p><strong>Category:</strong> <?php echo e($room['category']); ?></p>
            <p><strong>Location:</strong> <?php echo e($room['location_text']); ?></p>
            <p><strong>Host:</strong> <?php echo e($room['host_name']); ?></p>
            <p><strong>Price:</strong> KSh <?php echo number_format($room['price_ksh'], 2); ?> per night</p>

            <p style="margin-top:20px;"><?php echo nl2br(e($room['description'])); ?></p>

            <!-- Availability Calendar -->
            <div class="calendar-box" style="margin-top: 25px;">
                <h3 class="section-title">Availability Calendar</h3>

    <iframe
        title="Availability calendar" src="room_availability.php?id=<?php echo (int)$room['id']; ?>"
        style="width:100%; height:450px; border:0; border-radius:15px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
    </iframe>


            </div>
        </div>


        <!-- RIGHT COLUMN: BOOKING FORM -->
        <div>
            <div class="booking-box">
                <h3>Book This Room</h3>

                <?php if (!is_logged_in()): ?>
                    <p style="color:#d00; margin-bottom:15px;">
                        You must <a href="login.php">login</a> to book.
                    </p>
                <?php endif; ?>

                <form action="book.php" method="POST">
<?php echo csrf_field(); ?>
                    <input type="hidden" name="room_id" value="<?php echo (int)$room['id']; ?>">

                    <label>Check In:</label>
                    <input type="date" name="checkin" required>

                    <label>Check Out:</label>
                    <input type="date" name="checkout" required>

                    <label>Guests:</label>
                    <select name="guests" required>
                        <option value="1">1 Guest</option>
                        <option value="2">2 Guests</option>
                        <option value="3">3 Guests</option>
                        <option value="4">4 Guests</option>
                    </select>

                    <label>Phone Number:</label>
                    <input type="text" name="contact_phone" placeholder="07XX XXX XXX" required>

                    <button class="btn-book" <?php echo (!is_logged_in() ? 'disabled' : ''); ?>>
                        Book Now
                    </button>
                </form>
            </div>
        </div>

    </div><!-- /room-info-grid -->

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
