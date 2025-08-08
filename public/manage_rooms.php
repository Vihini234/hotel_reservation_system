<?php
include '../config/db.php';
include '../includes/auth.php';
include '../includes/header.php';
if (!is_logged_in() || !is_admin()) {
    header('Location: login.php');
    exit;
}

$room_number = $type = $price = $description = $discount = $availability = $image = '';
$edit_mode = false;
$success = '';
$error = '';
// Handle add/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_number = trim($_POST['room_number']);
    $type = trim($_POST['type']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;
    $availability = isset($_POST['availability']) ? $_POST['availability'] : 'available';
    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $img_name = uniqid('room_') . '_' . basename($_FILES['image']['name']);
        $target_dir = '../public/assets/images/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . $img_name;
        if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image = $img_name;
            } else {
                echo '<div class="alert">Failed to move uploaded file.</div>';
            }
        } else {
            echo '<div class="alert">Upload error: ' . $_FILES['image']['error'] . '</div>';
        }
    }
    if (isset($_POST['room_id']) && $_POST['room_id'] !== '') {
        // Update
        $room_id = intval($_POST['room_id']);
        if ($image) {
            $stmt = $conn->prepare("UPDATE rooms SET room_number=?, type=?, price=?, description=?, discount=?, availability=?, image=? WHERE id=?");
            $stmt->bind_param("ssdssdsi", $room_number, $type, $price, $description, $discount, $availability, $image, $room_id);
        } else {
            $stmt = $conn->prepare("UPDATE rooms SET room_number=?, type=?, price=?, description=?, discount=?, availability=? WHERE id=?");
            $stmt->bind_param("ssdsssi", $room_number, $type, $price, $description, $discount, $availability, $room_id);
        }
        if ($stmt->execute()) {
            $success = 'Room updated successfully!';
        } else {
            $error = 'Update failed.';
        }
        $stmt->close();
    } else {
        // Add
        $stmt = $conn->prepare("INSERT INTO rooms (room_number, type, price, description, discount, availability, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsdss", $room_number, $type, $price, $description, $discount, $availability, $image);
        if ($stmt->execute()) {
            $success = 'Room added successfully!';
        } else {
            $error = 'Add failed. Room number may already exist.';
        }
        $stmt->close();
    }
    header('Location: admin_dashboard.php?success=' . urlencode($success ? $success : $error));
    exit;
}
// Handle edit
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $room_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM rooms WHERE id=?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $room_number = $row['room_number'];
        $type = $row['type'];
        $price = $row['price'];
        $description = $row['description'];
        $discount = $row['discount'];
        $availability = $row['availability'];
        $image = $row['image'];
    }
    $stmt->close();
}
// Handle delete
if (isset($_GET['delete'])) {
    $room_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM rooms WHERE id=?");
    $stmt->bind_param("i", $room_id);
    if ($stmt->execute()) {
        $success = 'Room deleted successfully!';
    } else {
        $error = 'Delete failed.';
    }
    $stmt->close();
    header('Location: admin_dashboard.php?success=' . urlencode($success ? $success : $error));
    exit;
}
?>
<h2><?php echo $edit_mode ? 'Edit Room' : 'Add New Room'; ?></h2>
<form method="post" action="" enctype="multipart/form-data">
    <input type="hidden" name="room_id" value="<?php echo $edit_mode ? htmlspecialchars($room_id) : ''; ?>">
    <label>Room Number:</label>
    <input type="text" name="room_number" value="<?php echo htmlspecialchars($room_number); ?>" required>
    <label>Type:</label>
    <input type="text" name="type" value="<?php echo htmlspecialchars($type); ?>" required>
    <label>Price:</label>
    <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($price); ?>" required>
    <label>Description:</label>
    <textarea name="description" required><?php echo htmlspecialchars($description); ?></textarea>
    <label>Discount (%):</label>
    <input type="number" step="0.01" name="discount" value="<?php echo htmlspecialchars($discount); ?>">
    <label>Availability:</label>
    <select name="availability">
        <option value="available" <?php if($availability=="available") echo 'selected'; ?>>Available</option>
        <option value="booked" <?php if($availability=="booked") echo 'selected'; ?>>Booked</option>
    </select>
    <label>Room Image:</label>
    <input type="file" name="image" accept="image/*">
    <?php if ($edit_mode && $image): ?>
        <img src="assets/images/<?php echo htmlspecialchars($image); ?>" alt="Room Image" style="max-width:150px;display:block;margin:1em 0;">
    <?php endif; ?>
    <button type="submit"><?php echo $edit_mode ? 'Update Room' : 'Add Room'; ?></button>
</form>
<p><a href="admin_dashboard.php">Back to Dashboard</a></p>
<?php include '../includes/footer.php'; ?>
