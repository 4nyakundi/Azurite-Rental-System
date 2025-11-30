<?php
require_once "../../includes/db.php";
require_once "../../includes/helpers.php";

if (!is_admin()) {
    die("Admin only area.");
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['new_status'])) {
    $bookingId  = (int)$_POST['booking_id'];
    $newStatus  = $_POST['new_status'];

    // Valid statuses
    $allowed = ['Pending','Approved','Declined','Confirmed','CheckedIn','Completed','Cancelled'];
    if (!in_array($newStatus, $allowed, true)) {
        die("Invalid status selected.");
    }

    // Fetch current booking
    $stmt = $pdo->prepare("SELECT status FROM bookings WHERE id = ?");
    $stmt->execute([$bookingId]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($current) {
        $oldStatus = $current['status'];

        // Update booking
        $upd = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $upd->execute([$newStatus, $bookingId]);

        // Log event
        $admin = current_user();
        $adminId = $admin['id'] ?? null;

        if ($adminId) {
            $log = $pdo->prepare("
                INSERT INTO booking_events (booking_id, actor_id, old_status, new_status, note)
                VALUES (:bid, :aid, :old, :new, :note)
            ");
            $log->execute([
                ':bid' => $bookingId,
                ':aid' => $adminId,
                ':old' => $oldStatus,
                ':new' => $newStatus,
                ':note'=> 'Status changed by admin'
            ]);
        }
    }

    header("Location: bookings.php");
    exit;
}

// Filter (optional) – by status
$statusFilter = $_GET['status'] ?? '';
$params = [];
$whereSql = '';

if ($statusFilter && $statusFilter !== 'All') {
    $whereSql = "WHERE b.status = :status";
    $params[':status'] = $statusFilter;
}

// Fetch all bookings with room + tenant
$sql = "
    SELECT 
        b.*,
        r.name AS room_name,
        r.location_text,
        u.full_name AS tenant_name,
        u.email AS tenant_email
    FROM bookings b
    JOIN rooms r ON r.id = b.room_id
    JOIN users u ON u.id = b.tenant_id
    $whereSql
    ORDER BY b.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

include "../../includes/admin_header.php";
?>

<h1 class="page-title">Bookings</h1>

<div class="card-box" style="padding:20px; margin-bottom:20px;">
    <form method="get" style="display:flex; gap:10px; align-items:center;">
        <label><strong>Filter by status:</strong></label>
        <select name="status">
            <?php
            $statuses = ['All','Pending','Approved','Declined','Confirmed','CheckedIn','Completed','Cancelled'];
            foreach ($statuses as $st):
            ?>
                <option value="<?php echo $st; ?>" <?php echo ($statusFilter === $st ? 'selected' : ''); ?>>
                    <?php echo $st; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="btn-primary" type="submit" style="padding:6px 12px;">Apply</button>
    </form>
</div>

<div class="card-box" style="padding:0;">
    <table class="table">
        <thead>
            <tr>
                <th>Ref</th>
                <th>Room</th>
                <th>Tenant</th>
                <th>Dates</th>
                <th>Guests</th>
                <th>Total</th>
                <th>Status</th>
                <th>Phone</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$bookings): ?>
            <tr><td colspan="9" style="text-align:center;padding:20px;">No bookings yet.</td></tr>
        <?php else: ?>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><?php echo e($b['ref_code']); ?></td>
                    <td>
                        <?php echo e($b['room_name']); ?><br>
                        <small><?php echo e($b['location_text']); ?></small>
                    </td>
                    <td>
                        <?php echo e($b['tenant_name']); ?><br>
                        <small><?php echo e($b['tenant_email']); ?></small>
                    </td>
                    <td>
                        <?php echo e($b['checkin_date']); ?> →
                        <?php echo e($b['checkout_date']); ?>
                    </td>
                    <td><?php echo (int)$b['guests']; ?></td>
                    <td><?php echo format_ksh($b['total_amount_ksh']); ?></td>
                    <td>
                        <span class="badge badge-status-<?php echo strtolower($b['status']); ?>">
                            <?php echo e($b['status']); ?>
                        </span>
                    </td>
                    <td><?php echo e($b['contact_phone']); ?></td>
                    <td>
                        <a href="booking_view.php?id=<?php echo (int)$b['id']; ?>" class="btn-outline" style="padding:4px 8px;font-size:12px;">View</a>
                        <form method="post" style="margin-top:5px;">
                            <input type="hidden" name="booking_id" value="<?php echo (int)$b['id']; ?>">
                            <select name="new_status" style="font-size:11px;">
                                <?php foreach (['Pending','Approved','Declined','Confirmed','CheckedIn','Completed','Cancelled'] as $st): ?>
                                    <option value="<?php echo $st; ?>" <?php echo ($b['status']===$st?'selected':''); ?>>
                                        <?php echo $st; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-primary" style="padding:3px 8px;font-size:11px;">Save</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.badge {
    display:inline-block;
    padding:3px 8px;
    border-radius:999px;
    font-size:11px;
    font-weight:600;
}
.badge-status-pending   { background:#fff3cd; color:#856404; }
.badge-status-approved  { background:#d4edda; color:#155724; }
.badge-status-declined  { background:#f8d7da; color:#721c24; }
.badge-status-confirmed { background:#cce5ff; color:#004085; }
.badge-status-checkedin { background:#d1ecf1; color:#0c5460; }
.badge-status-completed { background:#e2e3e5; color:#383d41; }
.badge-status-cancelled { background:#f8d7da; color:#721c24; }
</style>

<?php include "../../includes/admin_footer.php"; ?>
