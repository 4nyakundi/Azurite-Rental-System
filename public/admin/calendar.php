<?php
// public/admin/calendar.php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (!is_admin()) {
    die("Admin only");
}

// --------------------------------------------------
// GET ROOM SELECTION
// --------------------------------------------------
$roomId = isset($_GET['room']) ? (int)$_GET['room'] : 0;

// Load all rooms
$rooms = $pdo->query("
    SELECT id, name 
    FROM rooms 
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

if ($roomId == 0 && count($rooms) > 0) {
    $roomId = $rooms[0]['id']; // default first room
}

// --------------------------------------------------
// MONTH NAVIGATION
// --------------------------------------------------
$month = isset($_GET['m']) ? (int)$_GET['m'] : date('n');
$year  = isset($_GET['y']) ? (int)$_GET['y'] : date('Y');

$firstDay         = strtotime("$year-$month-01");
$daysInMonth      = date("t", $firstDay);
$monthName        = date("F", $firstDay);
$startWeekday     = date("N", $firstDay); // 1=Mon, 7=Sun

// --------------------------------------------------
// LOAD BLOCKED DATES
// --------------------------------------------------
$blockStmt = $pdo->prepare("
    SELECT block_date 
    FROM room_block_dates 
    WHERE room_id = :r 
      AND MONTH(block_date) = :m 
      AND YEAR(block_date) = :y
");
$blockStmt->execute([
    ':r' => $roomId,
    ':m' => $month,
    ':y' => $year
]);

$blocked = [];
foreach ($blockStmt->fetchAll(PDO::FETCH_ASSOC) as $b) {
    $blocked[] = $b['block_date'];
}

// --------------------------------------------------
// LOAD BOOKINGS
// --------------------------------------------------
$bookStmt = $pdo->prepare("
    SELECT checkin_date, checkout_date, ref_code
    FROM bookings
    WHERE room_id = :r
      AND status IN ('Pending','Approved','Confirmed','CheckedIn')
      AND (
            MONTH(checkin_date) = :m 
         OR MONTH(checkout_date) = :m
      )
");
$bookStmt->execute([
    ':r' => $roomId,
    ':m' => $month
]);

$bookings = [];

foreach ($bookStmt->fetchAll(PDO::FETCH_ASSOC) as $b) {
    $cin  = strtotime($b['checkin_date']);
    $cout = strtotime($b['checkout_date']);

    for ($d = $cin; $d < $cout; $d += 86400) {
        $date = date('Y-m-d', $d);
        $bookings[$date] = $b['ref_code'];
    }
}

// --------------------------------------------------
// HANDLE BLOCK / UNBLOCK ACTIONS
// --------------------------------------------------
if (isset($_GET['action'], $_GET['date']) && is_admin()) {
    $action = $_GET['action'];
    $date   = $_GET['date'];

    if ($action === "block") {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO room_block_dates (room_id, block_date)
            VALUES (:r, :d)
        ");
        $stmt->execute([':r' => $roomId, ':d' => $date]);
    }

    if ($action === "unblock") {
        $stmt = $pdo->prepare("
            DELETE FROM room_block_dates
            WHERE room_id = :r AND block_date = :d
        ");
        $stmt->execute([':r' => $roomId, ':d' => $date]);
    }

    header("Location: calendar.php?room=$roomId&m=$month&y=$year");
    exit;
}

// --------------------------------------------------
// PAGE LAYOUT HEADER
// --------------------------------------------------
include __DIR__ . '/../../includes/admin_header.php';
?>

<style>
.calendar-box {
    background:#fff;
    padding:25px;
    border-radius:18px;
    box-shadow:0 5px 20px rgba(0,0,0,0.06);
    margin-bottom:40px;
}
.calendar-header {
    display:flex;
    justify-content:space-between;
    margin-bottom:20px;
}
.calendar-grid {
    display:grid;
    grid-template-columns: repeat(7, 1fr);
    gap:8px;
}
.day-cell {
    padding:12px;
    border-radius:12px;
    min-height:80px;
    text-align:center;
    background:#f8f9fc;
    font-weight:600;
}
.blocked {
    background:#ffdddd !important;
    color:#b00000;
}
.booked {
    background:#fff4cc !important;
    color:#8a5a00;
}
.available {
    background:#e9ffe9 !important;
    color:#196f19;
}
.day-number {
    font-size:18px;
    margin-bottom:10px;
}
.select-room {
    margin-bottom:20px;
}
a.day-link {
    text-decoration:none;
    font-size:12px;
}
</style>

<div class="section-heading">
    <h2>Room Availability Calendar</h2>
</div>

<div class="calendar-box">

    <!-- Room selector -->
    <form class="select-room" method="get">
        <label>Select Room:</label>
        <select name="room" onchange="this.form.submit()">
            <?php foreach ($rooms as $r): ?>
                <option value="<?php echo $r['id']; ?>" <?php if ($r['id'] == $roomId) echo "selected"; ?>>
                    <?php echo e($r['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="m" value="<?php echo $month; ?>">
        <input type="hidden" name="y" value="<?php echo $year; ?>">
    </form>

    <!-- Month navigation -->
    <div class="calendar-header">
        <a class="btn btn-outline" href="?room=<?php echo $roomId; ?>&m=<?php echo ($month == 1 ? 12 : $month - 1); ?>&y=<?php echo ($month == 1 ? $year - 1 : $year); ?>">← Prev</a>

        <h3><?php echo $monthName . " " . $year; ?></h3>

        <a class="btn btn-outline" href="?room=<?php echo $roomId; ?>&m=<?php echo ($month == 12 ? 1 : $month + 1); ?>&y=<?php echo ($month == 12 ? $year + 1 : $year); ?>">Next →</a>
    </div>

    <!-- Calendar grid -->
    <div class="calendar-grid">

        <!-- Weekdays -->
        <div class="day-cell"><strong>Mon</strong></div>
        <div class="day-cell"><strong>Tue</strong></div>
        <div class="day-cell"><strong>Wed</strong></div>
        <div class="day-cell"><strong>Thu</strong></div>
        <div class="day-cell"><strong>Fri</strong></div>
        <div class="day-cell"><strong>Sat</strong></div>
        <div class="day-cell"><strong>Sun</strong></div>

        <?php
        // Empty cells before month start
        for ($i = 1; $i < $startWeekday; $i++) {
            echo "<div></div>";
        }

        // Actual days
        for ($day = 1; $day <= $daysInMonth; $day++):
            $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $day);

            // Determine status
            $class = "available";
            $label = "Available";

            if (in_array($dateStr, $blocked)) {
                $class = "blocked";
                $label = "Blocked";
            }

            if (array_key_exists($dateStr, $bookings)) {
                $class = "booked";
                $label = "Booked";
            }
        ?>

        <div class="day-cell <?php echo $class; ?>">
            <div class="day-number"><?php echo $day; ?></div>

            <?php if ($class === "blocked"): ?>
                <a class="day-link" href="?room=<?php echo $roomId; ?>&m=<?php echo $month; ?>&y=<?php echo $year; ?>&action=unblock&date=<?php echo $dateStr; ?>">Unblock</a>

            <?php elseif ($class === "booked"): ?>
                <small>Ref: <?php echo e($bookings[$dateStr]); ?></small>

            <?php else: ?>
                <a class="day-link" href="?room=<?php echo $roomId; ?>&m=<?php echo $month; ?>&y=<?php echo $year; ?>&action=block&date=<?php echo $dateStr; ?>">Block</a>
            <?php endif; ?>

        </div>

        <?php endfor; ?>

    </div>
</div>

<?php include __DIR__ . '/../../includes/admin_footer.php'; ?>
