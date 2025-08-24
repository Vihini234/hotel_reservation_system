<?php
include '../config/db.php';
$sql = "SELECT * FROM rooms ORDER BY price ASC";
$result = $conn->query($sql);
function moneyx($v){ return '$' . number_format((float)$v, 2); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hotel Reservation System</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .hero { position: relative; width: 100%; height: 60vh; overflow: hidden; }
        .hero img { width: 100%; height: 100%; object-fit: cover; }
        .hero-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; color: #fff; flex-direction: column; }
        .hero-title { font-size: 2.5em; font-weight: bold; margin-bottom: 0.5em; letter-spacing: 2px; }
        .hero-btn { background: #007bff; color: #fff; border: none; font-size: 1.2em; padding: 0.7em 2em; border-radius: 4px; cursor: pointer; margin-top: 1em; text-decoration: none; }
        .hero-btn:hover { background: #0056b3; }
        .navbar { background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 1em 0; display: flex; justify-content: space-between; align-items: center; }
        .navbar nav { display: flex; gap: 2em; align-items: center; }
        .navbar a { color: #003366; font-weight: bold; text-decoration: none; font-size: 1.1em; }
        .navbar a:hover { text-decoration: underline; }
        .brand { font-family: 'Segoe UI', Arial, sans-serif; font-size: 2em; color: #003366; font-weight: bold; letter-spacing: 1px; margin-left: 2em; }
        .rooms-grid { display: flex; flex-wrap: wrap; gap: 2em; justify-content: center; margin-top: 2em; }
        .card.room { background: #f8fafc; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); width: 320px; padding: 1.5em 1em 1em 1em; display: flex; flex-direction: column; align-items: center; position: relative; }
        .thumb { width: 100px; height: 100px; background: #e5e7eb; border-radius: 50%; margin-bottom: 1em; display: flex; align-items: center; justify-content: center; font-size: 2em; color: #b0b0b0; overflow: hidden; }
        .thumb img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .room-title { margin: 0.5em 0 0.2em 0; font-size: 1.2em; }
        .price { color: #2563eb; font-weight: bold; font-size: 1.1em; margin-bottom: 0.5em; }
        .muted { color: #888; }
        .pill { font-size: 0.95em; color: #fff; background: #2563eb; border-radius: 8px; padding: 0.2em 1em; margin-bottom: 0.5em; display: inline-block; }
        .badge.deal { background: #06b6d4; color: #fff; border-radius: 6px; padding: 0.2em 0.7em; margin-left: 0.5em; }
        .actions { margin-top: 1em; }
        .actions .btn { margin-right: 0.5em; }
    </style>
</head>
<body>
<div class="navbar">
    <span class="brand">MARINO BEACH<br><span style="font-size:0.6em; font-weight:normal;">HOTEL COLOMBO</span></span>
    <nav>
  <a href="index.php">Home</a>
  <a href="login.php">Login</a>
  <a href="register.php">Register</a>
  <a href="login.php" class="hero-btn" style="margin-left:2em;">Book a Room</a>
    </nav>
</div>
<div class="hero">
    <img src="assets/images/6718b6820112355ec0cc585c_BHC_facade.jpg" alt="Hotel Garden" />
    <div class="hero-overlay">
        <div class="hero-title">Welcome to Marino Beach Hotel Colombo</div>
        <div>Experience luxury, comfort, and the best of Colombo's hospitality.</div>
        <a href="login.php" class="hero-btn">Book a Room</a>
    </div>
</div>
<main>
    <div style="text-align:center; margin-top:2em;">
      <h2 style="font-size:2.2em; color:#2563eb; margin-bottom:0.2em; letter-spacing:1px;">Available Rooms</h2>
      <div style="font-size:1.1em; color:#444; margin-bottom:1.5em;">Choose from our best rooms and book your stay today.</div>
    </div>
    <?php if ($result && $result->num_rows > 0): ?>
      <div class="rooms-grid" style="gap:2.5em;">
        <?php while ($row = $result->fetch_assoc()): ?>
          <?php
            $img = $row['image'];
            $img_path = '';
            if (!empty($img)) {
              $try_path = dirname(__DIR__) . '/assets/images/' . $img;
              if (file_exists($try_path)) {
                $img_path = '../assets/images/' . $img;
              }
            }
            $type = ucfirst($row['type']);
            $discount = !empty($row['discount']) && (float)$row['discount'] > 0 ? (float)$row['discount'] : 0;
            $old_price = $discount ? '<span style="color:#aaa;text-decoration:line-through;font-size:1em;margin-left:8px;">' . moneyx($row['price']/(1-$discount/100)) . '</span>' : '';
            $badge_color = ($type === 'Premium') ? '#2563eb' : (($type === 'Deluxe') ? '#1e90ff' : (($type === 'Luxury') ? '#6c47ff' : '#888'));
            $availability = strtolower($row['availability']);
            $is_available = ($availability === 'available');
          ?>
          <article class="card room" style="box-shadow:0 4px 24px rgba(0,0,0,0.08);border-radius:18px;padding:2em 1.5em 1.5em 1.5em;min-width:320px;max-width:370px;opacity:<?php echo $is_available ? '1' : '0.7'; ?>;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
              <span style="background:<?php echo $badge_color; ?>;color:#fff;padding:0.3em 1.1em;border-radius:8px;font-size:1em;font-weight:600;letter-spacing:0.5px;"><?php echo $type; ?></span>
              <?php if ($discount): ?>
                <span style="background:#ff3b3b;color:#fff;padding:0.3em 1.1em;border-radius:8px;font-size:1em;font-weight:600;letter-spacing:0.5px;margin-left:8px;">-<?php echo $discount; ?>% OFF</span>
              <?php endif; ?>
            </div>
            <div class="thumb" style="margin:1.2em auto 1.2em auto;width:120px;height:120px;">
              <?php if ($img_path): ?>
              <img src="<?php echo htmlspecialchars($img_path); ?>" alt="Room image">
              <?php else: ?>
              <div class="noimg" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#bbb;font-size:2.5em;background:#f0f0f0;border-radius:50%;">🛏️</div>
              <?php endif; ?>
            </div>
            <div style="display:flex;align-items:center;gap:1.2em;justify-content:center;margin-bottom:0.7em;">
              <span style="color:#444;font-size:1em;display:flex;align-items:center;gap:0.3em;">👥 <span>2 guests</span></span>
              <span style="color:#444;font-size:1em;display:flex;align-items:center;gap:0.3em;">📏 <span>50m²</span></span>
            </div>
            <h3 class="room-title" style="font-size:1.3em;font-weight:700;margin-bottom:0.2em;text-align:center;letter-spacing:0.5px;">Room <?php echo htmlspecialchars($row['room_number']); ?></h3>
            <p class="muted" style="text-align:center;font-size:1.05em;min-height:2.5em;">
              <?php echo htmlspecialchars($row['description'] ?: 'Cozy room with premium amenities and ocean-inspired decor.'); ?>
            </p>
            <div style="display:flex;align-items:center;justify-content:center;gap:0.7em;margin:1em 0 0.5em 0;">
              <span style="font-size:1.5em;font-weight:700;color:#2563eb;letter-spacing:1px;"><?php echo moneyx($row['price']); ?></span>
              <?php echo $old_price; ?>
              <span style="color:#888;font-size:1em;">per night</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:center;gap:0.7em;margin-bottom:1.2em;">
              <span style="background:#fffbe6;color:#ffb300;padding:0.2em 0.8em;border-radius:6px;font-size:1em;font-weight:600;display:flex;align-items:center;gap:0.3em;">★ 4.7</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:center;gap:0.7em;margin-bottom:1em;">
              <span style="background:<?php echo $is_available ? '#d4f7d4' : '#ffe6e6'; ?>;color:<?php echo $is_available ? '#1a7f1a' : '#b30000'; ?>;padding:0.2em 0.8em;border-radius:6px;font-size:1em;font-weight:600;display:flex;align-items:center;gap:0.3em;">
                <?php echo $is_available ? 'Available' : 'Unavailable'; ?>
              </span>
            </div>
            <div style="display:flex;gap:0.7em;justify-content:center;">
              <a class="btn primary" href="room_details.php?id=<?php echo $row['id']; ?>" style="width:48%;background:#fff;color:#2563eb;font-size:1.1em;font-weight:600;padding:0.8em 0;border-radius:8px;text-align:center;text-decoration:none;box-shadow:0 2px 8px rgba(37,99,235,0.08);border:2px solid #2563eb;transition:background 0.2s;">See More</a>
              <?php if ($is_available): ?>
                <a class="btn primary" href="login.php" style="width:48%;background:#2563eb;color:#fff;font-size:1.1em;font-weight:600;padding:0.8em 0;border-radius:8px;text-align:center;text-decoration:none;box-shadow:0 2px 8px rgba(37,99,235,0.08);transition:background 0.2s;">Book Now</a>
              <?php else: ?>
                <span class="btn" style="width:48%;background:#ccc;color:#fff;font-size:1.1em;font-weight:600;padding:0.8em 0;border-radius:8px;text-align:center;text-decoration:none;box-shadow:0 2px 8px rgba(37,99,235,0.08);cursor:not-allowed;">Unavailable</span>
              <?php endif; ?>
            </div>
          </article>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="card" style="text-align:center;"><p class="muted">No rooms found in the system.</p></div>
    <?php endif; ?>
</main>
<footer>
    &copy; 2025 Marino Beach Hotel Colombo
</footer>
<?php
// Close the database connection
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
</body>
</html>