<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

// Protect admin / host area
if (!is_admin() && !is_host()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Admin/Host only area.');
}

$host = current_user();

// Base URL for assets (adjust folder name if needed)
$base = "/Azurite-Rental-System/public/";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Azurite Host Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- CSS -->
  <link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
  <link rel="stylesheet" href="<?php echo $base; ?>assets/css/modern.css">
  <link rel="stylesheet" href="<?php echo $base; ?>assets/css/select2.min.css">
  <link rel="stylesheet" href="<?php echo $base; ?>assets/css/jquery.datetimepicker.min.css">
  <link rel="stylesheet" href="<?php echo $base; ?>assets/css/jquery-te-1.4.0.css">
  <link rel="stylesheet" href="<?php echo $base; ?>assets/css/matarial.css">
</head>
<body class="admin-body">
  <div class="admin-shell">

    <!-- Sidebar -->
    <aside class="admin-sidebar">
      <div class="admin-logo">
        <span class="logo-mark">AZ</span>
        <span class="logo-text">Azurite Panel</span>
      </div>
      <nav class="admin-nav">
        <a href="<?php echo $base; ?>host/index.php">Dashboard</a>
        <a href="<?php echo $base; ?>host/rooms.php">My Houses</a>
        <a href="<?php echo $base; ?>host/bookings.php">My Bookings</a>
        <a href="<?php echo $base; ?>host/clients.php">Clients</a>
        <a href="<?php echo $base; ?>host/calendar.php">My Calendar</a>
        <a href="<?php echo $base; ?>host/profile.php">Profile</a>
        <a href="<?php echo $base; ?>host/report_bookings.php">Reports</a>
      </nav>
    </aside>

    <!-- Main content wrapper -->
    <div class="admin-main">
      <header class="admin-topbar">
        <div class="admin-top-left">
          <h1 class="admin-title">Azurite Host Panel</h1>
        </div>
        <div class="admin-top-right">
          <?php if ($host): ?>
            <span class="host-user">
              Logged in as: <?php echo e($host['full_name']); ?> (<?php echo e($host['role']); ?>)
            </span>
          <?php endif; ?>
          <a href="<?php echo $base; ?>logout.php"
             class="btn-outline" style="padding:6px 12px;font-size:12px;">
            Logout
          </a>
        </div>
      </header>

      <div class="admin-content container">
        <?php if ($m = flash('success')): ?>
          <div class="alert success"><?php echo e($m); ?></div>
        <?php endif; ?>
        <?php if ($m = flash('error')): ?>
          <div class="alert error"><?php echo e($m); ?></div>
        <?php endif; ?>
