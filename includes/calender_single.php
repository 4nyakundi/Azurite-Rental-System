<?php
//
// includes/calendar_single.php
//

if (!isset($room_id)) {
    echo "<p>No room selected.</p>";
    return;
}

// Month & Year
$month = isset($_GET['m']) ? intval($_GET['m']) : date('n');
$year  = isset($_GET['y']) ? intval($_GET['y']) : date('Y');

// Normalize month overflow
while ($month < 1) { $month += 12; $year--; }
while ($month > 12) { $month -= 12; $year++; }

$firstDay = strtotime("$year-$month-01");
$daysInMonth = date("t", $firstDay);
$startDow = date("N", $firstDay); // 1=Mon .. 7=Sun

// Get booked dates
$book = $pdo->prepare("
    SELECT checkin_date, checkout_date
    FROM bookings
    WHERE room_id = ?
      AND status IN ('Pending','Approved','Confirmed','CheckedIn')
");
$book->execute([$room_id]);
$bookings = $book->fetchAll(PDO::FETCH_ASSOC);

// Get blocked dates
$blk = $pdo->prepare("
    SELECT block_date 
    FROM room_block_dates
    WHERE room_id = ?
");
$blk->execute([$room_id]);
$blocked = array_column($blk->fetchAll(PDO::FETCH_ASSOC), "block_date");

// Helper: Is date booked?
function isBooked($date, $bookings) {
    foreach ($bookings as $b) {
        if ($date >= $b['checkin_date'] && $date < $b['checkout_date']) {
            return true;
        }
    }
    return false;
}

?>

<style>
.calendar-box {
    background:#fff;
    border-radius:12px;
    padding:20px;
    box-shadow:0 3px 10px rgba(0,0,0,0.1);
}
.calendar-grid {
    display:grid;
    grid-template-columns:repeat(7,1fr);
    gap:5px;
    text-align:center;
}
.calendar-day {
    padding:10px;
    border-radius:8px;
    background:#f3f4f7;
}
.calendar-day.booked { background:#ffd966; }
.calendar-day.blocked { background:#ffb3b3; }
.calendar-day.available { background:#d6ffd6; }
.calendar-nav a {
    padding:8px 12px;
    background:#0d1b4c;
    color:#fff;
    border-radius:8px;
    text-decoration:none;
}
</style>

<div class="calendar-box">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
        <a class="calendar-nav" href="?room_id=<?php echo $room_id; ?>&m=<?php echo $month-1; ?>&y=<?php echo $year; ?>">&laquo; Prev</a>

        <h3><?php echo date("F Y", $firstDay); ?></h3>

        <a class="calendar-nav" href="?room_id=<?php echo $room_id; ?>&m=<?php echo $month+1; ?>&y=<?php echo $year; ?>">Next &raquo;</a>
    </div>

    <div class="calendar-grid">
        <strong>Mon</strong>
        <strong>Tue</strong>
        <strong>Wed</strong>
        <strong>Thu</strong>
        <strong>Fri</strong>
        <strong>Sat</strong>
        <strong>Sun</strong>

        <?php
        for ($i=1; $i<$startDow; $i++) {
            echo "<div></div>";
        }

        for ($day=1; $day<=$daysInMonth; $day++) {
            $date = "$year-$month-" . str_pad($day,2,'0',STR_PAD_LEFT);

            $class = "available";
            $label = $day;

            if (in_array($date, $blocked)) {
                $class = "blocked";
            } elseif (isBooked($date, $bookings)) {
                $class = "booked";
            }

            // Host/Admin clickable
            if (isset($hostMode) && $hostMode === true) {
                $url = "toggle_day.php?room_id=$room_id&date=$date&return=" . urlencode($_SERVER['REQUEST_URI']);
                echo "<a href='$url' class='calendar-day $class'>$label</a>";
            }
            // Public view
            else {
                echo "<div class='calendar-day $class'>$label</div>";
            }
        }
        ?>
    </div>
</div>
