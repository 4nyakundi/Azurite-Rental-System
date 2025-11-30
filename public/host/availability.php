<?php
// public/host/availability.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (!is_host()) {
    die("Host only");
}

// Get host ID
$hostId = current_user()['id'];

// Get all rooms owned by this host
$roomsStmt = $pdo->prepare("SELECT id, name FROM rooms WHERE owner_id = ? ORDER BY name");
$roomsStmt->execute([$hostId]);
$hostRooms = $roomsStmt->fetchAll(PDO::FETCH_ASSOC);

if (!$hostRooms) {
    include __DIR__ . '/../../includes/host_header.php';
    echo "<div class='p-4'><h2>You have no houses yet to manage availability for.</h2></div>";
    include __DIR__ . '/../../includes/host_footer.php';
    exit;
}

// Determine selected room
$roomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : $hostRooms[0]['id'];
$selectedRoom = null;

foreach ($hostRooms as $r) {
    if ($r['id'] == $roomId) {
        $selectedRoom = $r;
        break;
    }
}

// Fallback to the first room if an invalid ID was passed
if (!$selectedRoom) {
    $selectedRoom = $hostRooms[0];
    $roomId = $selectedRoom['id'];
}


include __DIR__ . '/../../includes/host_header.php'; // Include the host panel header

// Calendar Renderer
require_once __DIR__ . '/../../includes/calendar_single.php';
?>

<style>
/* Styling for Host room list sidebar */
.main-wrapper {
    display: flex;
    gap: 25px;
    padding: 20px;
}

.room-sidebar {
    width: 230px;
    background: #0d1b3d; /* Host specific color */
    color: white;
    padding: 20px;
    border-radius: 10px;
}

.room-sidebar h3 {
    margin-bottom: 15px;
    font-size: 18px;
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
    background: #007bff; /* Primary color highlight */
}

.room-sidebar li.active a {
    color: white;
    font-weight: 600;
}

.room-sidebar a {
    color: white;
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
        <h3>Your Houses</h3>
        <ul>
            <?php foreach ($hostRooms as $r): ?>
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
            // The 'true' parameter enables the toggle links for the Host.
            render_calendar($pdo, $roomId, $host = true); 
            ?>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../../includes/host_footer.php'; ?>