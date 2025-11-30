<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (!is_host()) {
    header("Location: ../login.php");
    exit;
}

$host = current_user();

// Base URL for HOST AREA only
$base = "/Azurite-Rental-System/public/host/";
$publicBase = "/Azurite-Rental-System/public/";
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Azurite Host Panel</title>
  <link rel="stylesheet" href="<?php echo $publicBase; ?>assets/css/style.css">
  <link rel="stylesheet" href="<?php echo $publicBase; ?>assets/css/modern.css">
</head>
<body class="admin-body">
  <div class="admin-shell">

    <!-- SIDEBAR -->
    <aside class="admin-sidebar">
      <div class="admin-logo">
        <span class="logo-mark">AZ</span>
        <span class="logo-text">Host Panel</span>
      </div>
      <nav class="admin-nav">
        <a href="<?php echo $base; ?>index.php">Dashboard</a>
        <a href="<?php echo $base; ?>rooms.php">My Houses</a>
        <a href="<?php echo $base; ?>bookings.php">My Bookings</a>
        <a href="<?php echo $base; ?>clients.php">Clients</a>
        <a href="<?php echo $base; ?>calendar.php">My Calendar</a>
        <a href="<?php echo $base; ?>profile.php">Profile</a>
        <a href="<?php echo $base; ?>report_bookings.php">Reports</a>
        
      </nav>
    </aside>

    <div class="admin-main">
      <header class="admin-topbar">
        <div class="admin-top-left">
          <h1 class="admin-title">Host Dashboard</h1>
        </div>
        <div class="admin-top-right">
          <span class="admin-user">
            Logged in as: <?php echo e($host['full_name']); ?> (Host)
          </span>
          <a href="<?php echo $publicBase; ?>logout.php" class="btn-outline">Logout</a>
        </div>
      </header>

      <div class="admin-content container">
