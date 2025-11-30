<?php
// public/host/client_view.php
require_once "../../includes/db.php";
require_once "../../includes/helpers.php";

// 1. Access Control: Must be a host
if (!is_host()) die("Host only area.");

$host = current_user();
$clientId = (int)($_GET['id'] ?? 0);

if (!$clientId) {
    die("Invalid client ID.");
}

// 2. Authorization and Data Fetching
// Fetch client details ONLY IF they have a booking on one of the host's rooms.
$sql = "
SELECT 
    u.id, u.full_name, u.username, u.email, u.phone, 
    u.avatar_path, u.id_card_path, u.created_at
FROM users u
WHERE u.id = :clientId
  -- Subquery to check for authorization: client must have booked a room owned by this host
  AND EXISTS (
      SELECT 1
      FROM bookings b
      JOIN rooms r ON r.id = b.room_id
      WHERE b.tenant_id = u.id
        AND r.owner_id = :hostId
      LIMIT 1
  )
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':clientId' => $clientId, ':hostId' => $host['id']]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    include "header.php"; // Assuming host panel structure includes a header
    echo "<div class='host-card'><p>Client not found or you are not authorized to view this client's profile.</p></div>";
    include "footer.php"; // Assuming host panel structure includes a footer
    exit;
}

$avatarUrl = "../uploads/avatars/" . ($client['avatar_path'] ?: "default.png");
$idCardUrl = "../uploads/id_cards/" . ($client['id_card_path'] ?: "no_id.png");

include "header.php"; // Assuming your host panel uses header.php
?>

<style>
.client-detail-card {
    background:#fff;
    padding:30px;
    border-radius:12px;
    box-shadow:0 6px 15px rgba(0,0,0,0.05);
    max-width: 800px;
    margin: 30px auto;
}
.client-info-grid {
    display: flex;
    gap: 40px;
    flex-wrap: wrap;
    margin-top: 20px;
}
.client-info-section {
    flex: 1;
    min-width: 300px;
}
.client-info-section h3 {
    border-bottom: 2px solid #007bff;
    padding-bottom: 5px;
    margin-bottom: 15px;
    color: #333;
}
.client-media-section img {
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-top: 10px;
}
.avatar-img {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 50%;
}
.id-card-img {
    width: 100%;
    max-width: 350px;
    height: auto;
}
</style>

<div class="client-detail-card">
    <h2 style="margin-bottom: 0;">Client Profile: <?php echo e($client['full_name']); ?></h2>
    <p style="color:#6c757d;">Member Since: <?php echo date('Y-m-d', strtotime($client['created_at'])); ?></p>
    
    <hr style="margin: 20px 0;">

    <div class="client-info-grid">
        <div class="client-info-section">
            <h3>Contact Details</h3>
            <p><strong>Username:</strong> <?php echo e($client['username']); ?></p>
            <p><strong>Email:</strong> <?php echo e($client['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo e($client['phone']); ?></p>
        </div>

        <div class="client-info-section client-media-section">
            <h3>Verification Media</h3>
            
            <p style="margin-top: 10px;"><strong>Avatar:</strong></p>
            <img src="<?php echo $avatarUrl; ?>" class="avatar-img">

            <p style="margin-top: 20px;"><strong>ID Card:</strong></p>
            <img src="<?php echo $idCardUrl; ?>" class="id-card-img">
            <?php if (!$client['id_card_path']): ?>
                <p><small style="color: red;">No ID Card uploaded by client.</small></p>
            <?php endif; ?>
        </div>
    </div>

    <a href="bookings.php" class="btn btn-secondary" style="margin-top: 30px;">← Back to Bookings</a>
</div>

<?php include "footer.php"; ?>