<?php
// figure out current page for active link
$current = basename($_SERVER['PHP_SELF']);
?>
<div class="host-sidebar">
    <h3>Host Menu</h3>
    <ul>
        <li>
            <a href="index.php" class="<?php echo $current=='index.php' ? 'active' : ''; ?>">
                Dashboard
            </a>
        </li>
        <li>
            <a href="rooms.php" class="<?php echo $current=='rooms.php' ? 'active' : ''; ?>">
                My Houses
            </a>
        </li>
        <li>
            <a href="bookings.php" class="<?php echo $current=='bookings.php' ? 'active' : ''; ?>">
                Bookings
            </a>
        </li>
        <li>
            <a href="calendar.php" class="<?php echo $current=='calendar.php' ? 'active' : ''; ?>">
                Availability Calendar
            </a>
        </li>
        <li>
            <a href="../profile.php">
                Profile
            </a>
        </li>
    </ul>
</div>
<div class="host-content">
