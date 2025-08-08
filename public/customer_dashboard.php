<?php
include '../config/db.php';
include '../includes/auth.php';
include '../includes/header.php';
if (!is_logged_in() || !is_customer()) {
    header('Location: login.php');
    exit;
}
// Handle booking success message
$success = isset($_GET['success']) ? $_GET['success'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';

// Build query with search
?>
<style>
    body {
        background: #f8f9fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    h2 {
        color: #2c3e50;
        margin-top: 1.5em;
        text-align: center;
    }
    .table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        margin-bottom: 2em;
    }
    .table th, .table td {
        padding: 0.85em 1em;
        border-bottom: 1px solid #e1e1e1;
        text-align: center;
    }
    .table th {
        background: #34495e;
        color: #fff;
        font-weight: 600;
    }
    .table tr:nth-child(even) {
        background: #f4f6f8;
    }
    form[method="get"] {
        background: #fff;
        padding: 1em 1.5em;
        border-radius: 8px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        max-width: 900px;
        margin: 1.5em auto 2em auto;
    }
    form[method="get"] input, form[method="get"] label, form[method="get"] button {
        font-size: 1em;
    }
    form[method="get"] input[type="text"], 
    form[method="get"] input[type="date"], 
    form[method="get"] input[type="number"] {
        padding: 0.4em 0.7em;
        border: 1px solid #bfc9d1;
        border-radius: 4px;
        margin-right: 0.5em;
        background: #f9fafb;
        transition: border 0.2s;
    }
    form[method="get"] input:focus {
        border: 1.5px solid #2980b9;
        outline: none;
    }
    form[method="get"] button {
        background: #2980b9;
        color: #fff;
        border: none;
        border-radius: 4px;
        padding: 0.5em 1.2em;
        cursor: pointer;
        transition: background 0.2s;
    }
    form[method="get"] button:hover {
        background: #1a5a85;
    }
    .alert.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
        padding: 0.8em 1em;
        border-radius: 5px;
        margin: 1em auto;
        max-width: 600px;
        text-align: center;
    }
    td img {
        border-radius: 6px;
        border: 1px solid #e1e1e1;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    }
    form[action="book_room.php"] button {
        background: #27ae60;
        color: #fff;
        border: none;
        border-radius: 4px;
        padding: 0.4em 1em;
        cursor: pointer;
        transition: background 0.2s;
    }
    form[action="book_room.php"] button:hover {
        background: #1e8449;
    }
    @media (max-width: 900px) {
        .table, .table th, .table td {
            font-size: 0.95em;
        }
        form[method="get"] {
            flex-direction: column;
            gap: 0.5em;
        }
    }
</style>
<?php
$sql = "SELECT * FROM rooms WHERE availability = 'available'";
if ($search !== '') {
    $search_esc = $conn->real_escape_string($search);
    $sql .= " AND (room_number LIKE '%$search_esc%' OR type LIKE '%$search_esc%' OR description LIKE '%$search_esc%')";
}
$result = $conn->query($sql);
?>
<h2>Available Rooms</h2>
<form method="get" action="" style="margin-bottom:1em;display:flex;gap:1em;align-items:center;flex-wrap:wrap;">
    <input type="text" name="search" placeholder="Search by type, number, description" value="<?php echo htmlspecialchars($search); ?>">
    <label for="start_date">Start Date:</label>
    <input type="date" name="start_date" id="start_date" value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>">
    <label for="end_date">End Date:</label>
    <input type="date" name="end_date" id="end_date" value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>">
    <label for="adults">Adults:</label>
    <input type="number" name="adults" id="adults" min="1" value="<?php echo isset($_GET['adults']) ? (int)$_GET['adults'] : 1; ?>" style="width:60px;">
    <label for="children">Children:</label>
    <input type="number" name="children" id="children" min="0" value="<?php echo isset($_GET['children']) ? (int)$_GET['children'] : 0; ?>" style="width:60px;">
    <button type="submit">Search</button>
</form>
<?php if ($success): ?><div class="alert success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($result->num_rows > 0): ?>
<table class="table">
    <tr>
        <th>Image</th>
        <th>Room Number</th>
        <th>Type</th>
        <th>Price</th>
        <th>Discount (%)</th>
        <th>Description</th>
        <th>Action</th>
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
        <td>
            <form method="post" action="book_room.php" style="margin:0;">
                <input type="hidden" name="room_id" value="<?php echo $row['id']; ?>">
                <input type="hidden" name="start_date" value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>">
                <input type="hidden" name="end_date" value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>">
                <input type="hidden" name="adults" value="<?php echo isset($_GET['adults']) ? (int)$_GET['adults'] : 1; ?>">
                <input type="hidden" name="children" value="<?php echo isset($_GET['children']) ? (int)$_GET['children'] : 0; ?>">
                <button type="submit">Book</button>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
<p>No rooms available at the moment.</p>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>

<!-- No additional code needed here for now. -->
