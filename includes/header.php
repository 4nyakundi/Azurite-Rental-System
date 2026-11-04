<?php
require_once __DIR__ . '/helpers.php';
$base = public_base();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Azurite Properties · Coastal stays in Kenya</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Find your next coastal stay with Azurite. Explore apartments and houses in Mombasa, Nyali and Diani.">
  <link rel="stylesheet" href="<?= e($base) ?>assets/css/style.css">
  <link rel="stylesheet" href="<?= e($base) ?>assets/css/modern.css">
  <link rel="stylesheet" href="<?= e($base) ?>assets/css/polish.css">
  <script src="<?= e($base) ?>assets/js/forms.js" defer></script>
</head>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>
<header class="top-header">
  <div class="container">
    <a class="brand" href="<?= e($base) ?>index.php" aria-label="Azurite home"><span class="brand-mark">A</span><span>AZURITE<small>PROPERTIES &amp; STAYS</small></span></a>
    <nav class="main-nav" aria-label="Main navigation">
      <ul>
        <li><a href="<?= e($base) ?>index.php">Home</a></li>
        <li><a href="<?= e($base) ?>rooms.php">Explore stays</a></li>
        <?php if (is_admin()): ?><li><a href="<?= e($base) ?>admin/index.php">Dashboard</a></li>
        <?php elseif (is_host()): ?><li><a href="<?= e($base) ?>host/index.php">Host dashboard</a></li><?php endif; ?>
        <?php if (is_logged_in()): ?>
          <li><a href="<?= e($base) ?>profile.php">Profile</a></li>
          <?php if (is_tenant()): ?><li><a href="<?= e($base) ?>my_bookings.php">My bookings</a></li><?php endif; ?>
          <li><a href="<?= e($base) ?>logout.php">Sign out</a></li>
        <?php else: ?>
          <li><a href="<?= e($base) ?>login.php">Sign in</a></li>
          <li><a class="nav-cta" href="<?= e($base) ?>register.php">Get started</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</header>
<main id="main-content">
<div class="container">
<?php foreach (['success', 'error', 'info'] as $kind): ?>
  <?php if ($message = flash($kind)): ?><div class="alert <?= e($kind) ?>" role="status"><?= e($message) ?></div><?php endif; ?>
<?php endforeach; ?>
