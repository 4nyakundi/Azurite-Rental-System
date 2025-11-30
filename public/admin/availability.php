<?php
// public/admin/availability.php (Revised)
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';

// 1. Access Control
if (!is_admin()) die("Admin only");

// --- 2. ROOM ID AND DISPLAY LOGIC ---

// Fetch all rooms (admin global)
$roomsStmt = $pdo->query("SELECT id, name FROM rooms ORDER BY name");
$rooms = $roomsStmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rooms) die("No rooms found.");

// Determine selected room: check 'room_id' first, then 'id' (legacy/iframe), then default to first room
$roomId = 0;
if (isset($_GET['room_id'])) {
    $roomId = (int)$_GET['room_id'];
} elseif (isset($_GET['id'])) {
    // Support for 'id' parameter (e.g., from room_view iframe)
    $roomId = (int)$_GET['id'];
}

// Find the selected room or default to the first one
$selectedRoom = null;
foreach ($rooms as $r) {
    if ($r['id'] == $roomId) {
        $selectedRoom = $r;
        break;
    }
}
// If selected room is invalid, use the first room in the list
if (!$selectedRoom) {
    $selectedRoom = $rooms[0];
    $roomId = $selectedRoom['id'];
}

include __DIR__ . '/../../includes/admin_header.php'; // Admin top header

// 3. Calendar Renderer
require_once __DIR__ . '/../../includes/calendar_single.php';
?>

<style>
/* Styling for Admin room list sidebar */
.main-wrapper {
    display: flex;
    gap: 25px;
    padding: 20px;
}

.room-sidebar {
    width: 230px;
    background: #f8f9fa; /* Light background for admin menu */
    color: #343a40;
    padding: 20px;
    border-radius: 10px;
    border: 1px solid #dee2e6;
}

.room-sidebar h3 {
    margin-bottom: 15px;
    font-size: 18px;
    border-bottom: 2px solid #007bff;
    padding-bottom: 5px;
}

.room-sidebar ul {
    list-style: none;
    padding-left: 0;
}

.room-sidebar li {
    padding: 8px 5px;
    margin-bottom: 3px;
    border-radius: 6px;
}

.room-sidebar li.active {
    background: #007bff;
}

.room-sidebar li.active a {
    color: white;
    font-weight: 600;
}

.room-sidebar a {
    color: #343a40;
    text-decoration: none;
    display: block;
}

.calendar-wrapper {
    flex-grow: 1;
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
}
</style>


<div class="main-wrapper">

    <div class="room-sidebar">
        <h3>Room Availability</h3>
        <ul>
            <?php foreach ($rooms as $r): ?>
                <li class="<?= ($r['id'] == $roomId ? 'active' : '') ?>">
                    <a href="availability.php?room_id=<?= $r['id'] ?>">
                        <?= e($r['name']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="calendar-wrapper">

        <h1 class="page-title">Availability – <?= htmlspecialchars($selectedRoom['name']) ?></h1>
        <p class="text-sm text-gray-600">
            <span style="font-weight: bold; color: #155724;">Green</span> = Available, 
            <span style="font-weight: bold; color: #856404;">Yellow</span> = Booked, 
            <span style="font-weight: bold; color: #721c24;">Red</span> = Blocked.
            Click on an **Available** or **Blocked** date to toggle its status.
        </p>

        <div style="margin-top:20px;">
            <?php 
            // Calling the centralized calendar renderer
            // The third parameter (true) enables the toggle links for the Admin/Host.
            render_calendar($pdo, $roomId, $host = true); 
            ?>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../../includes/admin_footer.php'; ?>