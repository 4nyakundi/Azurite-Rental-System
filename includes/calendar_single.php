<?php

// includes/calendar_single.php

// Helper function to check if a date falls within any booked range
// NOTE: This assumes $bookings is an array of objects/arrays with checkin_date and checkout_date keys.
function isBooked($date, $bookings) {
    $dateTs = strtotime($date);
    
    foreach ($bookings as $b) {
        $checkinTs = strtotime($b['checkin_date']);
        $checkoutTs = strtotime($b['checkout_date']);
        
        // A day is booked if it is on or after check-in AND strictly before check-out.
        if ($dateTs >= $checkinTs && $dateTs < $checkoutTs) {
            return true;
        }
    }
    return false;
}

/**
 * Renders the interactive calendar for a given room.
 * @param PDO $pdo Database connection object.
 * @param int $roomId ID of the room to display availability for.
 * @param bool $canToggle If true, renders clickable links to block/unblock dates (for Host/Admin).
 */
function render_calendar($pdo, $roomId, $canToggle = false) {

    // Get month and year from URL parameters, default to current month/year
    $month = isset($_GET['m']) ? (int)$_GET['m'] : date('n');
    $year  = isset($_GET['y']) ? (int)$_GET['y'] : date('Y');

    // Fix invalid values and normalize month/year for navigation
    while ($month < 1) { $month += 12; $year--; }
    while ($month > 12) { $month -= 12; $year++; }

    // Calculate dates for the displayed month
    $monthName = date('F', mktime(0, 0, 0, $month, 1, $year));
    $numDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $firstDay = date('w', mktime(0, 0, 0, $month, 1, $year)); // 0=Sunday, 6=Saturday
    
    // --- 1. Fetch Blocked and Booked Dates ---
    
    $startDate = date('Y-m-01', mktime(0, 0, 0, $month, 1, $year));
    $endDate = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));

    // Fetch Blocked Dates
    $blockedStmt = $pdo->prepare("SELECT block_date FROM room_block_dates WHERE room_id = ? AND block_date BETWEEN ? AND ?");
    $blockedStmt->execute([$roomId, $startDate, $endDate]);
    $blockedDates = $blockedStmt->fetchAll(PDO::FETCH_COLUMN);

    // Fetch Booked Dates (Confirmed/Approved/CheckedIn are states that block the room)
    $bookingStmt = $pdo->prepare("
        SELECT checkin_date, checkout_date 
        FROM bookings 
        WHERE room_id = ? 
          AND status IN ('Confirmed', 'Approved', 'CheckedIn') 
          AND (checkin_date <= ? AND checkout_date >= ?)
    ");
    // We check for bookings that start before or during the month AND end after or during the month
    $bookingStmt->execute([$roomId, $endDate, $startDate]);
    $bookings = $bookingStmt->fetchAll(PDO::FETCH_ASSOC);

    // --- 2. Navigation Logic ---
    $prevMonth = $month - 1;
    $prevYear = $year;
    if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

    $nextMonth = $month + 1;
    $nextYear = $year;
    if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

    $currentUrl = strtok($_SERVER["REQUEST_URI"], '?'); // Get the base file path
    $params = ['room_id' => $roomId]; // Base parameters for navigation links
    
    // Determine the path to the toggle script (depends on who is calling it)
    $togglePath = $canToggle ? '../host/toggle_day.php' : ''; 
    
    // Build the 'return' URL for the toggle script to redirect back to the current calendar view
    $returnUrl = urlencode($currentUrl . "?" . http_build_query(array_merge($params, ['m' => $month, 'y' => $year])));

    // --- 3. Start Calendar HTML ---
    ?>

    <div class="calendar-container">
        <div class="calendar-nav">
            <a href="<?= $currentUrl ?>?<?= http_build_query(array_merge($params, ['m' => $prevMonth, 'y' => $prevYear])) ?>" class="nav-arrow">&laquo;</a>
            <h3 class="calendar-title"><?= $monthName ?> <?= $year ?></h3>
            <a href="<?= $currentUrl ?>?<?= http_build_query(array_merge($params, ['m' => $nextMonth, 'y' => $nextYear])) ?>" class="nav-arrow">&raquo;</a>
        </div>

        <table class="calendar-grid">
            <thead>
                <tr>
                    <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                <?php
                // Render leading empty cells
                $dayOfWeek = $firstDay;
                if ($firstDay == 0) $dayOfWeek = 7; // Treat Sunday as the 7th day for the loop index
                
                for ($i = 1; $i < $dayOfWeek; $i++) {
                    echo "<td class='empty-day'></td>";
                }
                
                // --- 4. Render Days ---
                for ($d = 1; $d <= $numDays; $d++):
                    $date = date('Y-m-d', mktime(0, 0, 0, $month, $d, $year));
                    $isBlocked = in_array($date, $blockedDates);
                    $isDayBooked = isBooked($date, $bookings);
                    $today = date('Y-m-d');
                    
                    $class = '';
                    $statusText = '';
                    $actionUrl = '';

                    if ($isDayBooked) {
                        $class = 'c-booked';
                        $statusText = 'Booked';
                    } elseif ($isBlocked) {
                        $class = 'c-blocked';
                        $statusText = $canToggle ? 'Blocked (Click to Unblock)' : 'Blocked';
                        if ($canToggle) {
                            $actionUrl = "{$togglePath}?room_id={$roomId}&date={$date}&return={$returnUrl}";
                        }
                    } else {
                        $class = 'c-available';
                        $statusText = $canToggle ? 'Available (Click to Block)' : 'Available';
                        if ($canToggle) {
                            $actionUrl = "{$togglePath}?room_id={$roomId}&date={$date}&return={$returnUrl}";
                        }
                    }

                    // Add classes for styling based on date conditions
                    if ($date === $today) $class .= ' is-today';
                    if ($date < $today) $class .= ' is-past';

                    // Determine if the day is clickable
                    $isClickable = $canToggle && !$isDayBooked && $date >= $today;
                    
                    ?>
                    
                    <td class="<?= $class ?>" style="height: 100px;">
                        <div class="day-num"><?= $d ?></div>
                        
                        <?php if ($isClickable): ?>
                            <a href="<?= $actionUrl ?>" class="toggle-link">
                                <?= $isBlocked ? 'Unblock' : 'Block' ?>
                            </a>
                        <?php elseif (!$canToggle): ?>
                            <span class="label"><?= $statusText ?></span>
                        <?php else: ?>
                             <span class="label"><?= $isDayBooked ? 'Booked' : ($isBlocked ? 'Blocked' : 'Available') ?></span>
                        <?php endif; ?>

                    </td>
                    
                    <?php
                    // Start a new row every 7 days (including initial empty cells)
                    if (($d + $firstDay - 1) % 7 == 0) echo "</tr><tr>";
                endfor;
                
                // Render trailing empty cells
                while (($d + $firstDay - 1) % 7 != 0 && $d <= $numDays + 7) {
                    echo "<td class='empty-day'></td>";
                    $d++;
                }
                ?>
                </tr>
            </tbody>
        </table>
    </div>

    <style>
    /* CSS for the calendar component (Should be in a separate CSS file in a real app) */
    .calendar-container {
        font-family: Arial, sans-serif;
        user-select: none;
    }
    .calendar-nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .calendar-title {
        font-size: 24px;
        margin: 0;
        color: #333;
    }
    .nav-arrow {
        font-size: 20px;
        text-decoration: none;
        color: #007bff;
        padding: 5px 15px;
        border: 1px solid #007bff;
        border-radius: 6px;
        transition: background-color 0.2s;
    }
    .nav-arrow:hover {
        background-color: #f0f8ff;
    }
    .calendar-grid {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    .calendar-grid th {
        text-align: center;
        padding: 10px 0;
        border-bottom: 2px solid #ddd;
        font-weight: bold;
        color: #555;
    }
    .calendar-grid td {
        border: 1px solid #eee;
        padding: 8px;
        vertical-align: top;
        text-align: left;
        transition: all 0.2s;
        cursor: default;
    }
    
    /* Day Status Colors */
    .day-num {
        font-size: 18px;
        margin-bottom: 5px;
        font-weight: bold;
        color: #333;
    }
    .toggle-link, .label {
        display: block;
        text-align: center;
        text-decoration: none;
        font-weight: 600;
        padding: 4px 0;
        border-radius: 4px;
        font-size: 12px;
        margin-top: 5px;
    }

    /* Available */
    .c-available { 
        background: #f1fff1; 
        color: #006400; 
        border-color: #d0f7d0;
    }
    .c-available .toggle-link {
        color: #fff;
        background: #28a745;
    }
    .c-available .toggle-link:hover {
        background: #218838;
    }
    
    /* Blocked */
    .c-blocked { 
        background: #fff1f1; 
        color: #721c24; 
        border-color: #ffd0d0;
    }
    .c-blocked .toggle-link {
        color: #fff;
        background: #dc3545;
    }
    .c-blocked .toggle-link:hover {
        background: #c82333;
    }
    
    /* Booked */
    .c-booked { 
        background: #fffacd; 
        color: #856404; 
        border-color: #ffeeb3;
    }
    .c-booked .label {
        color: #856404;
        background: #ffe38f;
    }

    /* Past Dates & Today */
    .is-past {
        background: #f8f8f8 !important;
        color: #ccc !important;
        cursor: not-allowed;
    }
    .is-past .day-num {
        color: #ccc;
    }
    .is-today {
        border: 3px solid #007bff;
        padding: 5px; /* Adjust padding due to border increase */
    }
    .empty-day {
        background: #f9f9f9;
        cursor: default;
    }
    </style>
    <?php
}
?>