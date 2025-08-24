<?php
// Start session if not already
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Compute a base URL that works whether you visit /public/ directly or via a vhost
$BASE = rtrim(dirname($_SERVER['PHP_SELF']), '/\\'); // e.g. /hotel_reservation_system/public

// Convenience helper for links/assets
function u($path) {
  global $BASE;
  return $BASE . '/' . ltrim($path, '/');
}

// If you store a user in session:
$user = $_SESSION['user'] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ocean Breeze Resort — Home</title>
  <link rel="stylesheet" href="<?= u('assets/css/site.css') ?>">
</head>
<body>
  <div class="topbar">
    <div class="container" style="display:flex;align-items:center;width:100%">
      <div class="brand">🌊 Ocean Breeze Resort</div>
      <div class="spacer"></div>

      <?php if ($user): ?>
        <span class="hello">Hi, <?= htmlspecialchars($user['name'] ?? 'Guest') ?></span>
        <?php /* if you have a role in session, show admin link */ ?>
        <?php if (($user['role'] ?? '') === 'admin'): ?>
          <a class="ghost" href="<?= u('admin/index.php') ?>">Admin</a>
        <?php else: ?>
          <a class="ghost" href="<?= u('customer/index.php') ?>">My Dashboard</a>
        <?php endif; ?>
        <form method="post" action="<?= u('auth/logout.php') ?>" style="margin:0 0 0 8px">
          <button class="button ghost">Logout</button>
        </form>
      <?php else: ?>
        <a class="ghost" href="<?= u('auth/login.php') ?>">Login</a>
        <a class="ghost" href="<?= u('auth/register.php') ?>">Register</a>
        <a class="button" href="<?= u('customer/index.php') ?>">Book Now</a>
      <?php endif; ?>
    </div>
  </div>

  <main class="container">
