<?php
// public/book_room.php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // surface DB errors during dev

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in() || !is_customer()) {
    header('Location: auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['room_id'])) {
    header('Location: customer_dashboard.php?error=' . urlencode('Invalid request.'));
    exit;
}

$room_id = (int)$_POST['room_id'];
$user_id = (int)$_SESSION['user_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    
    //    This is atomic: affected_rows will be 1 only if the room was available.
    $sql = "UPDATE rooms 
                SET availability = 'booked'
                WHERE id = ? AND LOWER(TRIM(availability)) = 'available'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        // Nothing updated → not available anymore
        $conn->rollback();
        header('Location: customer_dashboard.php?error=' . urlencode('Room is no longer available.'));
        exit;
    }
    $stmt->close();

    // 2) Insert the booking row
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, room_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $room_id);
    $stmt->execute();
    $stmt->close();

    // 3) Commit
    $conn->commit();

    header('Location: customer_dashboard.php?ok=' . urlencode('Room booked successfully!'));
    exit;

} catch (Throwable $e) {
    // Roll back any partial changes
    if ($conn->errno === 0) {
        // even if errno is 0, ensure we try to rollback if begin_transaction ran
        $conn->rollback();
    }
    
    header('Location: customer_dashboard.php?error=' . urlencode('Booking failed: ' . $e->getMessage()));
    exit;
}
