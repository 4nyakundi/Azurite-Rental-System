<?php
// includes/calendar_read_only.php
// This component displays the calendar for a SINGLE ROOM ID in a read-only format.
// It assumes $pdo, $roomId, $year, $month, $month_str, and date helpers are defined by the calling script.
// It requires the calling script to set $roomId, $year, and $month.

// --- 1. SET CALENDAR PARAMETERS (Assumes $roomId is set) ---
$year = max(1970, min(2100, (int)($_GET['year'] ?? date('Y'))));
$month = isset($_GET['month']) ? (int)$_GET['month'] : date("m");
$month_str = sprintf("%02d", $month);

if ($month < 1 || $month > 12) {
    $month = date("m");
    $year = date("Y");
}

$days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$current_date_string = sprintf("%04d-%02d-01", $year, $month);
$firstDay = date("N", strtotime($current_date_string)); // 1 (Mon) through 7 (Sun)
$displayMonth = date("F Y", strtotime($current_date_string));

// Calculate Previous and Next Months (Use 'id' as the param name for this embedded view)
$prevMonthTime = strtotime("-1 month", strtotime($current_date_string));
$nextMonthTime = strtotime("+1 month", strtotime($current_date_string));

$prevMonth = date("m", $prevMonthTime);
$prevYear = date("Y", $prevMonthTime);

$nextMonth = date("m", $nextMonthTime);
$nextYear = date("Y", $nextMonthTime);

$baseURL = "room_availability.php?id={$roomId}";


// --- 2. LOAD AVAILABILITY DATA FOR CURRENT MONTH ---

// Load Blocked dates
$blocked = $pdo->prepare("
    SELECT block_date
    FROM room_block_dates
    WHERE room_id = :r AND block_date LIKE :month_prefix
");
$blocked->execute([':r' => $roomId, ':month_prefix' => "$year-$month_str%"]);
$blockedDates = array_column($blocked->fetchAll(PDO::FETCH_ASSOC), 'block_date');

// Load Booked date ranges
$bookedStmt = $pdo->prepare("
    SELECT checkin_date, checkout_date
    FROM bookings
    WHERE room_id = :r AND status IN ('Pending','Approved','Confirmed','CheckedIn')
          AND checkout_date >= :start_date AND checkin_date <= :end_date
");
$bookedStmt->execute([
    ':r' => $roomId,
    ':start_date' => $current_date_string,
    ':end_date' => sprintf("%04d-%02d-%02d", $year, $month, $days)
]);
$ranges = $bookedStmt->fetchAll(PDO::FETCH_ASSOC);

// Flatten booked ranges into individual dates
$bookedDates = [];
$month_prefix = "$year-$month_str";
foreach ($ranges as $b) {
    $start = max(strtotime($b['checkin_date']), strtotime($current_date_string));
    $end = min(strtotime($b['checkout_date']) - 86400, strtotime(sprintf("%04d-%02d-%02d", $year, $month, $days)));

    for ($t=$start; $t<=$end; $t+=86400) {
        $date = date("Y-m-d", $t);
        if (strpos($date, $month_prefix) === 0) {
            $bookedDates[] = $date;
        }
    }
}
?>

<style>
/* Simplified CSS for the calendar view */
.calendar-card-public {
    background:white;
    padding:20px;
    border-radius:15px;
    box-shadow:0 2px 10px rgba(0,0,0,0.05);
}
.cal-available{
    background:#e0ffe0;
    color:#006400;
    padding:8px 0;
    border-radius:6px;
    font-weight:bold;
    text-align:center;
}
.cal-blocked{
    background:#ffe0e0;
    color:#8B0000;
    padding:8px 0;
    border-radius:6px;
    font-weight:bold;
    text-align:center;
}
.cal-booked{
    background:#fff0c9;
    color:#B8860B;
    padding:8px 0;
    border-radius:6px;
    font-weight:bold;
    text-align:center;
}
.cal-day-num { font-size: 16px; display: block; }
.cal-status-text { font-size: 10px; font-weight: normal; display: block; }
.cal-nav-link {
    text-decoration:none;
    font-size:24px;
    color:#1a2c59;
    padding: 0 10px;
}
.cal-nav-link:hover {
    color: #0056b3;
}
</style>

<div class="calendar-card-public">

    <!-- MONTH NAVIGATION -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; padding:10px 0; border-bottom: 1px solid #eee;">
        <a href="<?php echo $baseURL; ?>&month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>"
           class="cal-nav-link">
            &laquo;
        </a>
        <h3 style="margin:0; color:#1a2c59; font-size:18px;"><?php echo $displayMonth; ?></h3>
        <a href="<?php echo $baseURL; ?>&month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>"
           class="cal-nav-link">
            &raquo;
        </a>
    </div>
    <!-- END: MONTH NAVIGATION -->

    <p style="font-size:14px; margin-bottom:15px;">
      <strong>Availability Legend:</strong>
      <span style="background:#e0ffe0;padding:3px 7px;border-radius:4px;margin-right:5px;">Available</span>
      <span style="background:#ffe0e0;padding:3px 7px;border-radius:4px;margin-right:5px;">Unavailable (Host Blocked)</span>
      <span style="background:#fff0c9;padding:3px 7px;border-radius:4px;">Booked</span>
    </p>

    <table class="table" style="width:100%; text-align:center;">
      <thead>
        <tr style="background:#f7f7f7;"><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th><th>Sun</th></tr>
      </thead>
      <tbody>
        <tr>
        <?php
        // Print empty cells for the start of the month
        for ($i=1; $i<$firstDay; $i++) echo "<td></td>";

        // Loop through all days of the month
        for ($d=1; $d<=$days; $d++):
            $date = sprintf("%04d-%02d-%02d", $year, $month, $d);
            echo "<td style='padding:5px;'>";

            if (in_array($date, $bookedDates)) {
                $class = 'cal-booked';
                $status_text = 'Booked';
            } elseif (in_array($date, $blockedDates)) {
                $class = 'cal-blocked';
                $status_text = 'Unavailable';
            } else {
                $class = 'cal-available';
                $status_text = 'Available';
            }

            // Check if date is in the past
            $is_past = strtotime($date) < strtotime(date('Y-m-d'));

            echo "<div class='$class' style='opacity: " . ($is_past ? '0.5' : '1') . ";'>
                    <span class='cal-day-num'>$d</span>
                    <span class='cal-status-text'>$status_text</span>
                  </div>";

            echo "</td>";

            // Start a new row every 7 days (including initial empty cells)
            if ((($d + $firstDay) % 7)==0) echo "</tr><tr>";
        endfor;
        ?>
        </tr>
      </tbody>
    </table>
</div>
