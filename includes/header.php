<?php
// Common header for all pages
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/hotel_reservation_system/public/assets/style.css">
    <title>Hotel Reservation System</title>
</head>
<body>
    <header>
        <nav>
            <a href="/hotel_reservation_system/public/index.php">Home</a>
            <?php if (is_logged_in()): ?>
                <?php if (is_admin()): ?>
                    <a href="/hotel_reservation_system/public/admin_dashboard.php">Admin Dashboard</a>
                <?php else: ?>
                    <a href="/hotel_reservation_system/public/customer_dashboard.php">Customer Dashboard</a>
                <?php endif; ?>
                <a href="/hotel_reservation_system/public/logout.php">Logout</a>
            <?php else: ?>
                <a href="/hotel_reservation_system/public/login.php">Login</a>
                <a href="/hotel_reservation_system/public/register.php">Register</a>
            <?php endif; ?>
        </nav>
    </header>
    <main>
