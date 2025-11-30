<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<?php 
$base = '/Azurite-Rental-System-Modern/public/'; 
?>
<?php
// Detect base path dynamically
$base = "/AZURITE-RENTAL-SYSTEM/public/";
?>
<link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
<link rel="stylesheet" href="<?php echo $base; ?>assets/css/modern.css">
<link rel="stylesheet" href="<?php echo $base; ?>assets/css/select2.min.css">
<link rel="stylesheet" href="<?php echo $base; ?>assets/css/jquery.datetimepicker.min.css">
<link rel="stylesheet" href="<?php echo $base; ?>assets/css/jquery-te-1.4.0.css">
<link rel="stylesheet" href="<?php echo $base; ?>assets/css/matarial.css">


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Azurite Properties - Rental System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/modern.css">
  <link rel="stylesheet" href="assets/css/select2.min.css">
  <link rel="stylesheet" href="assets/css/jquery.datetimepicker.min.css">
  <link rel="stylesheet" href="assets/css/jquery-te-1.4.0.css">
  <link rel="stylesheet" href="assets/css/matarial.css">
</head>
<body>
<header class="top-header">
  <div class="container">
    <div class="logo">
      <img src="assets/img/azurite_logo_white_bg_.jpg" alt="Azurite Properties">
    </div>
    <nav class="main-nav">
      <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="rooms.php">Houses</a></li>
        <?php if (!empty($_SESSION['user']) && $_SESSION['user']['role']==='admin'): ?>
          <li><a href="admin/index.php">Admin</a></li>
        <?php elseif (!empty($_SESSION['user']) && $_SESSION['user']['role']==='host'): ?>
          <li><a href="admin/rooms.php">Host Panel</a></li>
        <?php endif; ?>
        <?php if (!empty($_SESSION['user'])): ?>
          <li><a href="profile.php">Profile</a></li>
          <li><a href="my_bookings.php">My Bookings</a></li>
          <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
          <li><a href="login.php">Login</a></li>
          <li><a href="register.php">Sign Up</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</header>
<main>
  <div class="container">
