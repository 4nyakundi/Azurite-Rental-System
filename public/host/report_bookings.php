<?php
// public/host/report_bookings.php

// ----------------------------------------------------
// 1. SETUP & DATA FETCHING (Before any HTML output)
// ----------------------------------------------------
require_once __DIR__ . '/../../includes/helpers.php'; 
require_once __DIR__ . '/../../includes/db.php';     

// Ensure database connection is available
if (!isset($pdo) || !$pdo instanceof PDO) {
    die("FATAL ERROR: Database connection (\$pdo) is not available.");
}

// Check Host permissions immediately
if (!is_host()) die("Host only area.");

$host = current_user();
$hostId = $host['id'];

// Default filter dates
$from   = $_GET['from'] ?? date('Y-m-01');
$to     = $_GET['to']   ?? date('Y-m-d');
$export = $_GET['export'] ?? '';

// Helper function
function clean_ksh($amount) {
    return is_numeric($amount) ? number_format((float)$amount, 2, '.', '') : '0.00'; 
}

// SQL QUERY: Fetch data before deciding to show HTML or Export
$sql = "
SELECT 
    b.*, 
    r.name AS room_name, 
    u.full_name AS tenant_name,
    u.id AS tenant_id
FROM bookings b
JOIN rooms r ON r.id = b.room_id          
JOIN users u ON u.id = b.tenant_id
WHERE r.owner_id = :hostId
  AND b.created_at BETWEEN :from AND :to
ORDER BY b.created_at DESC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':from' => $from . ' 00:00:00',
        ':to'   => $to   . ' 23:59:59',
        ':hostId' => $hostId,
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// ----------------------------------------------------
// 2. EXPORT LOGIC (Must happen BEFORE header inclusion)
// ----------------------------------------------------

// --- A. EXPORT TO CSV ---
if ($export === 'csv') {
    // Clear any previous output buffers to ensure clean CSV
    if (ob_get_level()) ob_end_clean();
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="bookings_report_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $out = fopen('php://output', 'w');
    
    // Header Row
    fputcsv($out, ['Ref Code', 'Client Name', 'House Name', 'Check-in', 'Check-out', 'Total KSh', 'Status', 'Booking Date']);
    
    // Data Rows
    foreach ($rows as $b) {
        fputcsv($out, [
            $b['ref_code'],
            $b['tenant_name'],
            $b['room_name'],
            $b['checkin_date'],
            $b['checkout_date'],
            clean_ksh($b['total_amount_ksh']),
            $b['status'],
            $b['created_at']
        ]);
    }
    fclose($out);
    exit; // Stop script here so no HTML is added
}

// --- B. EXPORT TO "PRINT VIEW" (For PDF) ---
if ($export === 'pdf') {
    // This generates a clean HTML page and opens the Print Dialog
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Booking Report - <?php echo date('Y-m-d'); ?></title>
        <style>
            body { font-family: sans-serif; font-size: 12px; color: #333; }
            h2 { margin-bottom: 5px; }
            .meta { margin-bottom: 20px; font-size: 14px; color: #555; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .amount { text-align: right; }
            @media print {
                .no-print { display: none; }
            }
        </style>
    </head>
    <body onload="window.print()">
        <button class="no-print" onclick="window.print()" style="padding:10px; cursor:pointer; margin-bottom:20px;">Click to Print / Save as PDF</button>
        <button class="no-print" onclick="history.back()" style="padding:10px; cursor:pointer;">Go Back</button>

        <h2>Booking Report</h2>
        <div class="meta">
            <strong>Host:</strong> <?php echo e($host['full_name']); ?><br>
            <strong>Period:</strong> <?php echo e($from); ?> to <?php echo e($to); ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Ref</th><th>Client</th><th>House</th>
                    <th>Check-in</th><th>Check-out</th>
                    <th>Status</th><th class="amount">Total (KSh)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $b): ?>
                <tr>
                    <td><?php echo e($b['ref_code']); ?></td>
                    <td><?php echo e($b['tenant_name']); ?></td>
                    <td><?php echo e($b['room_name']); ?></td>
                    <td><?php echo e($b['checkin_date']); ?></td>
                    <td><?php echo e($b['checkout_date']); ?></td>
                    <td><?php echo e($b['status']); ?></td>
                    <td class="amount"><?php echo clean_ksh($b['total_amount_ksh']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit; // Stop script here
}

// ----------------------------------------------------
// 3. STANDARD WEB INTERFACE (Only runs if not exporting)
// ----------------------------------------------------
require_once __DIR__ . '/../../includes/host_header.php'; 
?>

<style>
/* --- Re-using your original styles --- */
.section-heading { margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
.hero-actions { display: flex; align-items: flex-end; gap: 15px; padding: 15px; background: #f9f9f9; border: 1px solid #eee; border-radius: 8px; margin-bottom: 25px; }
.hero-actions .form-row { display: flex; flex-direction: column; }
.hero-actions label { font-weight: 600; margin-bottom: 5px; color: #555; }
.hero-actions input[type="date"] { padding: 8px 10px; border: 1px solid #ccc; border-radius: 5px; }
.table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden; }
.table thead th { background-color: #0d1b3d; color: white; padding: 12px 15px; text-align: left; font-weight: 600; font-size: 14px; border: none; }
.table tbody tr:nth-child(even) { background-color: #f8f8f8; }
.table tbody tr:hover { background-color: #f0f8ff; }
.table tbody td { padding: 10px 15px; border-bottom: 1px solid #eee; color: #333; font-size: 14px; }
.btn-primary { background-color: #007bff; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 14px; transition: background-color 0.2s; }
.btn-primary:hover { background-color: #0056b3; }
.btn-outline { background-color: white; color: #007bff; border: 1px solid #007bff; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 14px; transition: all 0.2s; }
.btn-outline:hover { background-color: #f0f8ff; }
.btn-sm { padding: 4px 10px; font-size: 12px; }
</style>

<div class="section-heading">
  <h2>Host Booking Reports</h2>
</div>

<form method="get" class="hero-actions">
  <div class="form-row">
    <label for="from">From Date:</label>
    <input type="date" id="from" name="from" value="<?php echo e($from); ?>">
  </div>
  <div class="form-row">
    <label for="to">To Date:</label>
    <input type="date" id="to" name="to" value="<?php echo e($to); ?>">
  </div>
  <div class="form-row" style="margin-top:0;">
    <button class="btn-primary" type="submit">Filter</button>
  </div>
  
  <div class="form-row" style="margin-top:0; margin-left: auto; flex-direction: row; align-items: center;">
    <a class="btn-outline" href="?from=<?php echo e($from); ?>&to=<?php echo e($to); ?>&export=csv" style="margin-right: 8px;">Download CSV (Excel)</a>
    <a class="btn-outline" target="_blank" href="?from=<?php echo e($from); ?>&to=<?php echo e($to); ?>&export=pdf">Print / Save as PDF</a>
  </div>
</form>

<table class="table">
  <thead>
    <tr>
      <th>Ref</th>
      <th>Client</th>
      <th>House</th>
      <th>Check-in</th>
      <th>Check-out</th>
      <th>Total</th>
      <th>Status</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $b): ?>
    <tr>
      <td><?php echo e($b['ref_code']); ?></td>
      <td><a href="../admin/client_view.php?id=<?php echo (int)$b['tenant_id']; ?>" style="color:#007bff; text-decoration:underline;"><?php echo e($b['tenant_name']); ?></a></td>
      <td><?php echo e($b['room_name']); ?></td>
      <td><?php echo e($b['checkin_date']); ?></td>
      <td><?php echo e($b['checkout_date']); ?></td>
      <td>KSh <?php echo number_format($b['total_amount_ksh'], 2); ?></td>
      <td><?php echo e($b['status']); ?></td>
      <td>
        <a href="booking_view.php?id=<?php echo (int)$b['id']; ?>" class="btn-sm btn-info btn-primary">View</a>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (empty($rows)): ?>
    <tr>
        <td colspan="8" style="text-align:center; padding: 20px;">No bookings found for your houses in the selected period.</td>
    </tr>
  <?php endif; ?>
  </tbody>
</table>

<?php require_once __DIR__ . '/../../includes/host_footer.php'; ?>