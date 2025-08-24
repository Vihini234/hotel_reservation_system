<?php
include_once '../includes/auth.php';
include_once '../config/db.php'; 
if (!isset($conn) || !$conn) {
    die('Database connection failed. Please check your db.php configuration.');
}

//  is admin
if (!is_logged_in() || !is_admin()) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';
$edit_room = null;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_number = trim($_POST['room_number']);
    $type = trim($_POST['type']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $availability = isset($_POST['status']) ? strtolower(trim($_POST['status'])) : 'available';
    $discount = floatval($_POST['discount'] ?? 0);
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/';
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file_extension = strtolower(pathinfo($_FILES['room_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = 'room_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            if (move_uploaded_file($_FILES['room_image']['tmp_name'], $upload_path)) {
                $image_path = $new_filename; 
            } else {
                $error = 'Error uploading image file.';
            }
        } else {
            $error = 'Invalid image format. Please upload JPG, JPEG, PNG, or GIF files only.';
        }
                    // Sanitize and validate inputs
                    $room_number = htmlspecialchars(trim($_POST['room_number'] ?? ''));
                    $type = htmlspecialchars(trim($_POST['type'] ?? ''));
                    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
                    $description = htmlspecialchars(trim($_POST['description'] ?? ''));
                    $availability = isset($_POST['status']) ? strtolower(htmlspecialchars(trim($_POST['status']))) : 'available';
                    $discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;

                    
                    if ($price < 0) $price = 0;
                    if ($discount < 0) $discount = 0;
    }
    
    // V
    if (empty($room_number) || empty($type) || $price <= 0 || empty($description)) {
        $error = 'All fields are required and price must be greater than 0.';
    } else if (empty($error)) {
        if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
        
            $edit_id = intval($_POST['edit_id']);

            // If new image uploaded, update with image, otherwise update without changing image
            if (!empty($image_path)) {
                // Get old image to delete it
                $old_img_stmt = $conn->prepare("SELECT image FROM rooms WHERE id = ?");
                $old_img_stmt->bind_param("i", $edit_id);
                $old_img_stmt->execute();
                $old_img_result = $old_img_stmt->get_result();
                $old_img_data = $old_img_result->fetch_assoc();
                
                if ($old_img_data && !empty($old_img_data['image']) && file_exists('../assets/images/' . $old_img_data['image'])) {
                    unlink('../assets/images/' . $old_img_data['image']);
                }
                $stmt = $conn->prepare("UPDATE rooms SET room_number = ?, type = ?, price = ?, description = ?, availability = ?, discount = ?, image = ? WHERE id = ?");
                $stmt->bind_param("ssdssdsi", $room_number, $type, $price, $description, $availability, $discount, $image_path, $edit_id);
            } else {
                $stmt = $conn->prepare("UPDATE rooms SET room_number = ?, type = ?, price = ?, description = ?, availability = ?, discount = ? WHERE id = ?");
                $stmt->bind_param("ssdssdi", $room_number, $type, $price, $description, $availability, $discount, $edit_id);
            }
            
            if ($stmt->execute()) {
                $success = 'Room updated successfully!';
            } else {
                $error = 'Error updating room: ' . $conn->error;
            }
        } else {
            // Check if room number already exists
            $check_stmt = $conn->prepare("SELECT id FROM rooms WHERE room_number = ?");
            $check_stmt->bind_param("s", $room_number);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Room number already exists!';
            } else {
                
                if (empty($image_path)) {
                    $error = 'Please upload a room image.';
                } else {
                    $stmt = $conn->prepare("INSERT INTO rooms (room_number, type, price, description, availability, discount, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssdssds", $room_number, $type, $price, $description, $availability, $discount, $image_path);
                    if ($stmt->execute()) {
                        header('Location: admin_dashboard.php?success=Room added successfully!');
                        exit;
                    } else {
                        $error = 'Error adding room: ' . $conn->error;
                    }
                }
            }
        }
    }
}


if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    // Check if room has any bookings
    $check_bookings = $conn->prepare("SELECT id FROM bookings WHERE room_id = ?");
    $check_bookings->bind_param("i", $delete_id);
    $check_bookings->execute();
    $booking_result = $check_bookings->get_result();
    
    if ($booking_result->num_rows > 0) {
        $error = 'Cannot delete room. It has existing bookings.';
    } else {
        
        $img_stmt = $conn->prepare("SELECT image FROM rooms WHERE id = ?");
        $img_stmt->bind_param("i", $delete_id);
        $img_stmt->execute();
        $img_result = $img_stmt->get_result();
        $img_data = $img_result->fetch_assoc();
        
        $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        
        if ($stmt->execute()) {
            // Delete image file
            if ($img_data && !empty($img_data['image']) && file_exists('../' . $img_data['image'])) {
                unlink('../' . $img_data['image']);
            }
            $success = 'Room deleted successfully!';
        } else {
            $error = 'Error deleting room: ' . $conn->error;
        }
    }
}

// get room data
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $edit_room = $result->fetch_assoc();
    }
}

// Get all rooms
$sql = "SELECT * FROM rooms ORDER BY room_number ASC";
$rooms_result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marino Beach Hotel - Manage Rooms</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            line-height: 1.6;
        }

        /* Header */
        .header {
            background: #333;
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            background: #4a90e2;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }

        .nav-links {
            display: flex;
            gap: 30px;
            list-style: none;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background 0.3s;
        }

        .nav-links a:hover, .nav-links a.active {
            background: #4a90e2;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .welcome-text {
            color: #ccc;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #c82333;
        }

        /* Main Content */
        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .page-title {
            font-size: 2rem;
            color: #333;
            margin-bottom: 30px;
        }

        /* Form Styles */
        .form-section {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #4a90e2;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        /* Image Upload Styles */
        .image-upload-container {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: border-color 0.3s;
        }

        .image-upload-container:hover {
            border-color: #4a90e2;
        }

        .image-upload-container.has-image {
            border-style: solid;
            border-color: #4a90e2;
        }

        .current-image {
            max-width: 200px;
            max-height: 150px;
            border-radius: 6px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .upload-text {
            color: #666;
            margin-bottom: 10px;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }

        .file-input-label {
            background: #4a90e2;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .file-input-label:hover {
            background: #357ab8;
        }

        .image-info {
            font-size: 0.9rem;
            color: #666;
            margin-top: 10px;
        }

        /* Button Styles */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #4a90e2;
            color: white;
        }

        .btn-primary:hover {
            background: #357ab8;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .btn-warning {
            background: #ffc107;
            color: #333;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-start;
            margin-top: 20px;
        }

        /* Alert Styles */
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Rooms List */
        .rooms-list {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .room-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
            transition: box-shadow 0.3s;
            display: flex;
            gap: 20px;
        }

        .room-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .room-image-container {
            flex-shrink: 0;
        }

        .room-image {
            width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .room-content {
            flex: 1;
        }

        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .room-info h3 {
            font-size: 1.3rem;
            color: #333;
            margin-bottom: 5px;
        }

        .room-type {
            color: #4a90e2;
            font-weight: 500;
            text-transform: capitalize;
        }

        .room-price {
            text-align: right;
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }

        .price-period {
            font-size: 0.9rem;
            color: #666;
            font-weight: normal;
        }

        .discount-info {
            font-size: 0.85rem;
            color: #dc3545;
            font-weight: 500;
        }

        .room-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .room-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            margin-bottom: 15px;
        }

        .room-status.available {
            background: #d4edda;
            color: #155724;
        }

        .room-status.occupied {
            background: #f8d7da;
            color: #721c24;
        }

        .room-status.maintenance {
            background: #fff3cd;
            color: #856404;
        }

        .room-actions {
            display: flex;
            gap: 10px;
        }

        .no-rooms {
            text-align: center;
            color: #666;
            font-size: 1.1rem;
            padding: 40px 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-links {
                gap: 15px;
            }
            
            .user-info {
                flex-direction: column;
                gap: 10px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .room-card {
                flex-direction: column;
            }

            .room-image {
                width: 100%;
                max-width: 300px;
                margin: 0 auto;
            }
            
            .room-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .room-price {
                text-align: left;
                margin-top: 10px;
            }

            .room-actions {
                flex-direction: column;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">🏨</div>
                <span>Marino Beach Hotel</span>
            </div>
            <nav>
                <ul class="nav-links">
                    <li><a href="admin_dashboard.php">Dashboard</a></li>
                    <li><a href="manage_rooms.php" class="active">Rooms</a></li>
                </ul>
            </nav>
            <div class="user-info">
                <span class="welcome-text">Welcome, Admin</span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </header>

    <div class="main-content">
        <h1 class="page-title">Manage Rooms</h1>
        
        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add/Edit Room Form -->
        <div class="form-section">
            <h2 class="section-title">
                <?php echo $edit_room ? 'Edit Room' : 'Add New Room'; ?>
            </h2>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <?php if ($edit_room): ?>
                    <input type="hidden" name="edit_id" value="<?php echo $edit_room['id']; ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="room_number">Room Number *</label>
                        <input 
                            type="text" 
                            id="room_number" 
                            name="room_number" 
                            value="<?php echo $edit_room ? htmlspecialchars($edit_room['room_number']) : ''; ?>" 
                            required
                            placeholder="e.g., 101, A-12, Suite 1"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Room Type *</label>
                        <select id="type" name="type" required>
                            <option value="">Select Room Type</option>
                            <option value="single" <?php echo ($edit_room && $edit_room['type'] === 'single') ? 'selected' : ''; ?>>Single Room</option>
                            <option value="double" <?php echo ($edit_room && $edit_room['type'] === 'double') ? 'selected' : ''; ?>>Double Room</option>
                            <option value="deluxe" <?php echo ($edit_room && $edit_room['type'] === 'deluxe') ? 'selected' : ''; ?>>Deluxe Room</option>
                            <option value="suite" <?php echo ($edit_room && $edit_room['type'] === 'suite') ? 'selected' : ''; ?>>Suite</option>
                            <option value="presidential" <?php echo ($edit_room && $edit_room['type'] === 'presidential') ? 'selected' : ''; ?>>Presidential Suite</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price per Night ($) *</label>
                        <input 
                            type="number" 
                            id="price" 
                            name="price" 
                            min="0" 
                            step="0.01" 
                            value="<?php echo $edit_room ? $edit_room['price'] : ''; ?>" 
                            required
                            placeholder="99.99"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="discount">Discount (%)</label>
                        <input 
                            type="number" 
                            id="discount" 
                            name="discount" 
                            min="0" 
                            max="100" 
                            step="0.01" 
                            value="<?php echo $edit_room ? $edit_room['discount'] : '0'; ?>" 
                            placeholder="0.00"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="available" <?php echo ($edit_room && $edit_room['availability'] === 'available') ? 'selected' : ''; ?>>Available</option>
                            <option value="occupied" <?php echo ($edit_room && $edit_room['availability'] === 'occupied') ? 'selected' : ''; ?>>Occupied</option>
                            <option value="maintenance" <?php echo ($edit_room && $edit_room['availability'] === 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="room_image">Room Image <?php echo !$edit_room ? '*' : '(Optional - leave empty to keep current image)'; ?></label>
                        <div class="image-upload-container <?php echo ($edit_room && !empty($edit_room['image'])) ? 'has-image' : ''; ?>">
                            <?php if ($edit_room && !empty($edit_room['image'])): ?>
                                <img src="../<?php echo htmlspecialchars($edit_room['image']); ?>" alt="Current room image" class="current-image">
                                <div class="upload-text">Current Image - Upload a new one to replace</div>
                            <?php else: ?>
                                <div class="upload-text">📷 Upload Room Image</div>
                            <?php endif; ?>
                            
                            <div class="file-input-wrapper">
                                <input type="file" id="room_image" name="room_image" accept="image/*" <?php echo !$edit_room ? 'required' : ''; ?>>
                                <label for="room_image" class="file-input-label">Choose Image</label>
                            </div>
                            <div class="image-info">Supported formats: JPG, JPEG, PNG, GIF (Max 5MB)</div>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="description">Description *</label>
                        <textarea 
                            id="description" 
                            name="description" 
                            required
                            placeholder="Describe the room features, amenities, view, etc..."
                        ><?php echo $edit_room ? htmlspecialchars($edit_room['description']) : ''; ?></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $edit_room ? '💾 Update Room' : '➕ Add Room'; ?>
                    </button>
                    
                    <?php if ($edit_room): ?>
                        <a href="manage_rooms.php" class="btn btn-secondary">❌ Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        
        <div class="rooms-list">
            <h2 class="section-title">All Rooms</h2>
            
            <?php if ($rooms_result->num_rows > 0): ?>
                <?php while($room = $rooms_result->fetch_assoc()): ?>
                <div class="room-card">
                    <div class="room-image-container">
                       
                        <?php 
                        $img = $room['image'];
                        $img_path = '';
                        if (!empty($img)) {
                            // Use correct absolute path for file_exists
                            $try_path = dirname(__DIR__) . '/assets/images/' . $img;
                            if (file_exists($try_path)) {
                                $img_path = '../assets/images/' . $img;
                            }
                        }
                        if ($img_path) {
                            echo '<img src="' . htmlspecialchars($img_path) . '" alt="Room ' . htmlspecialchars($room['room_number']) . '" class="room-image">';
                        } else {
                            echo '<div class="room-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666; font-size: 3rem;">📷</div>';
                        }
                        ?>
                    </div>
                    
                    <div class="room-content">
                        <div class="room-header">
                            <div class="room-info">
                                <h3>Room <?php echo htmlspecialchars($room['room_number']); ?></h3>
                                <div class="room-type"><?php echo ucfirst(htmlspecialchars($room['type'])); ?></div>
                            </div>
                            <div class="room-price">
                                $<?php echo number_format($room['price'], 2); ?>
                                <div class="price-period">per night</div>
                                <?php if ($room['discount'] > 0): ?>
                                    <div class="discount-info"><?php echo $room['discount']; ?>% OFF</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="room-status <?php echo $room['availability']; ?>">
                            <?php echo ucfirst($room['availability']); ?>
                        </div>
                        
                        <p class="room-description"><?php echo htmlspecialchars($room['description']); ?></p>
                        
                        <div class="room-actions">
                            <a href="manage_rooms.php?edit=<?php echo $room['id']; ?>" class="btn btn-warning">
                                ✏️ Edit
                            </a>
                            <a href="manage_rooms.php?delete=<?php echo $room['id']; ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Are you sure you want to delete Room <?php echo htmlspecialchars($room['room_number']); ?>? This action cannot be undone and will delete the room image as well.');">
                                🗑️ Delete
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-rooms">
                    <p>No rooms found. Add your first room using the form above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-hide success messages after 5 seconds
        setTimeout(function() {
            const successAlert = document.querySelector('.alert.success');
            if (successAlert) {
                successAlert.style.opacity = '0';
                successAlert.style.transition = 'opacity 0.5s';
                setTimeout(() => successAlert.remove(), 500);
            }
        }, 5000);
    </script>