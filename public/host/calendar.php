<?php
require_once "../../includes/db.php";
require_once "../../includes/helpers.php";
require_once "../../includes/host_header.php";

if (!is_host()) {
    die("Host only");
}


// Get host ID
$hostId = current_user()['id'];

// Get all rooms owned by this host
$rooms = $pdo->prepare("SELECT id, name FROM rooms WHERE owner_id = ?");
$rooms->execute([$hostId]);
$hostRooms = $rooms->fetchAll(PDO::FETCH_ASSOC);

if (!$hostRooms) {
    echo "<div class='p-4'><h2>You have no houses yet.</h2></div>";
    exit;
}

// Determine selected room
$roomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : $hostRooms[0]['id'];
$roomFound = false;

foreach ($hostRooms as $r) {
    if ($r['id'] == $roomId) $roomFound = true;
}

if (!$roomFound) {
    $roomId = $hostRooms[0]['id']; // force valid room
}

// Calendar Renderer
require_once "../../includes/calendar_single.php";
?>

<div class="main-wrapper">

    <!-- LEFT SIDEBAR (List of host’s rooms) -->
    <div class="room-sidebar">
        <h3>Your Houses</h3>
        <ul>
            <?php foreach ($hostRooms as $r): ?>
                <li class="<?= ($r['id'] == $roomId ? 'active' : '') ?>">
                    <a href="calendar.php?room_id=<?= $r['id'] ?>">
                        <?= e($r['name']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- RIGHT CONTENT (Calendar) -->
    <div class="calendar-wrapper">
        <?php render_calendar($pdo, $roomId, $host = true); ?>
    </div>

</div>

<style>
.main-wrapper {
    display: flex;
    gap: 25px;
    padding: 20px;
}

.room-sidebar {
    width: 230px;
    background: #0d1b3d;
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
    padding: 10px 5px;
    margin-bottom: 5px;
    border-radius: 6px;
}

.room-sidebar li.active,
.room-sidebar li:hover {
    background: #1f2c49;
}

.room-sidebar a {
    color: white;
    text-decoration: none;
}

.calendar-wrapper {
    flex-grow: 1;
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
}
</style>

<?php require_once "../../includes/host_footer.php"; ?>
