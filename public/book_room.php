<?php
include '../config/db.php';
include '../includes/auth.php';
if (!is_logged_in() || !is_customer()) {
    header('Location: login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_id'])) {
    $room_id = intval($_POST['room_id']);
    $user_id = $_SESSION['user_id'];
    // Check if room is still available
    $stmt = $conn->prepare("SELECT status FROM rooms WHERE id=?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $stmt->bind_result($status);
    $stmt->fetch();
    $stmt->close();
    if ($status === 'available') {
        // Book the room
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, room_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $room_id);
        if ($stmt->execute()) {
            // Update room status
            $stmt2 = $conn->prepare("UPDATE rooms SET status='booked' WHERE id=?");
            $stmt2->bind_param("i", $room_id);
            $stmt2->execute();
            $stmt2->close();
            header('Location: customer_dashboard.php?success=Room booked successfully!');
            exit;
        } else {
            $error = 'Booking failed. Please try again.';
        }
        $stmt->close();
    } else {
        $error = 'Room is no longer available.';
    }
} else {
    $error = 'Invalid request.';
}
if (isset($error)) {
    header('Location: customer_dashboard.php?success=' . urlencode($error));
    exit;
}
