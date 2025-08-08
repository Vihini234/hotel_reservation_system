<?php
include '../config/db.php';
include '../includes/auth.php';
include '../includes/header.php';
if (!is_logged_in() || !is_admin()) {
    header('Location: login.php');
    exit;
}
// Handle success message
$success = isset($_GET['success']) ? $_GET['success'] : '';
// Fetch all rooms
$sql = "SELECT * FROM rooms";
$result = $conn->query($sql);
?>
<h2>Admin Dashboard</h2>
<?php if ($success): ?><div class="alert success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<p><a href="manage_rooms.php">Add New Room</a></p>
<h3>All Rooms</h3>
<?php if ($result->num_rows > 0): ?>
<table class="table">
    <tr style="background-color: #f2f2f2; color: #333; font-weight: bold;">
        <th style="padding: 10px; border-bottom: 2px solid #ccc;">Image</th>
        <th style="padding: 10px; border-bottom: 2px solid #ccc;">Room Number</th>
        <th style="padding: 10px; border-bottom: 2px solid #ccc;">Type</th>
        <th style="padding: 10px; border-bottom: 2px solid #ccc;">Price</th>
        <th style="padding: 10px; border-bottom: 2px solid #ccc;">Status</th>
        <th style="padding: 10px; border-bottom: 2px solid #ccc;">Description</th>
        <th style="padding: 10px; border-bottom: 2px solid #ccc;">Actions</th>
    </tr>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td>
            <?php if (!empty($row['image'])): ?>
                <img src="assets/images/<?php echo htmlspecialchars($row['image']); ?>" alt="Room Image" style="max-width:80px;max-height:60px;">
            <?php else: ?>
                No image
            <?php endif; ?>
        </td>
        <td><?php echo htmlspecialchars($row['room_number']); ?></td>
        <td><?php echo htmlspecialchars($row['type']); ?></td>
        <td><?php echo htmlspecialchars($row['price']); ?></td>
        <td><?php echo htmlspecialchars($row['status']); ?></td>
        <td><?php echo htmlspecialchars($row['description']); ?></td>
        <td>
            <a href="manage_rooms.php?edit=<?php echo $row['id']; ?>">Edit</a> |
            <a href="manage_rooms.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this room?');">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
<p>No rooms found.</p>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>
