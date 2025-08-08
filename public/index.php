<?php
include '../includes/auth.php';
include '../includes/header.php';
?>
<h2>Welcome to the Hotel Reservation System</h2>
<p>Please <a href="login.php">login</a> or <a href="register.php">register</a> to continue.</p>

<h3>Available Rooms</h3>
<?php
include '../config/db.php';
$sql = "SELECT * FROM rooms WHERE availability = 'available'";
$result = $conn->query($sql);
if ($result->num_rows > 0): ?>
<table class="table">
	<tr>
		<th>Image</th>
		<th>Room Number</th>
		<th>Type</th>
		<th>Price</th>
		<th>Discount (%)</th>
		<th>Description</th>
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
		<td><?php echo htmlspecialchars($row['discount']); ?></td>
		<td><?php echo htmlspecialchars($row['description']); ?></td>
	</tr>
	<?php endwhile; ?>
</table>
<?php else: ?>
<p>No rooms available at the moment.</p>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>
