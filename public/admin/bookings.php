<?php
require_once "../../includes/db.php";
require_once "../../includes/helpers.php";

if (!is_admin()) {
    die("Admin only area.");
}

require_once __DIR__ . '/../../includes/booking_service.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['new_status'])) {
    try {
        update_booking_status($pdo, (int)$_POST['booking_id'], $_POST['new_status'], current_user());
        set_flash('success', 'Booking status updated.');
    } catch (DomainException $exception) {
        set_flash('error', $exception->getMessage());
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
        set_flash('error', 'Unable to update this booking. Please try again.');
    }
    redirect('bookings.php');
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
<?php echo csrf_field(); ?>
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
