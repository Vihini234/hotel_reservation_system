<?php
include '../includes/auth.php';
include '../config/db.php';
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
        // Always use absolute path for assets/images/
        $target_dir = dirname(__DIR__) . '/assets/images/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . $img_name;
        // Debug: check if file exists after upload
        if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image = $img_name;
                // Debug output: check if file exists after upload
                if (!file_exists($target_file)) {
                    echo '<div class="alert">Upload failed: file not found at ' . htmlspecialchars($target_file) . '</div>';
                }
            } else {
                echo '<div class="alert">Failed to move uploaded file to ' . htmlspecialchars($target_file) . '</div>';
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
            session_write_close();
            header('Location: admin_dashboard.php?success=' . urlencode($success));
            exit;
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
        session_write_close();
        // Redirect to admin dashboard with success or error message
        $redirect_msg = $success ? $success : $error;
        header('Location: admin_dashboard.php?success=' . urlencode($redirect_msg));
        exit;
    }
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
    session_write_close();
    header('Location: admin_dashboard.php?success=' . urlencode($success ? $success : $error));
    exit;
}
?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success" style="background:#d4edda;color:#155724;padding:12px 20px;border-radius:6px;margin:20px 0;">
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger" style="background:#f8d7da;color:#721c24;padding:12px 20px;border-radius:6px;margin:20px 0;">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>
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
    <button type="submit"><?php echo $edit_mode ? 'Confirm Edit' : 'Add Room'; ?></button>
    <?php if ($edit_mode): ?>
        <a href="admin_dashboard.php" class="cancel-btn" style="margin-left:10px;">Cancel</a>
    <?php endif; ?>
</form>
<p><a href="admin_dashboard.php">Back to Dashboard</a></p>
<?php include '../includes/footer.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Edit Room' : 'Add New Room'; ?> - Hotel Reservation System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            padding: 24px 24px 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }

        .modal-subtitle {
            font-size: 14px;
            color: #6b7280;
        }

        .modal-body {
            padding: 24px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.2s ease;
            background: #fff;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            background: white;
            cursor: pointer;
            transition: all 0.2s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
            padding-right: 40px;
        }

        .form-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            resize: vertical;
            min-height: 80px;
            transition: all 0.2s ease;
        }

        .form-textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .room-type-selector {
            position: relative;
        }

        .room-type-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            pointer-events: none;
            z-index: 1;
        }

        .form-select.with-icon {
            padding-left: 48px;
        }

        .modal-footer {
            padding: 16px 24px 24px;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .breadcrumb {
            position: absolute;
            top: 20px;
            left: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: white;
            font-size: 14px;
            z-index: 999;
        }

        .breadcrumb a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .breadcrumb a:hover {
            color: white;
        }

        .breadcrumb-separator {
            color: rgba(255, 255, 255, 0.6);
        }

        .input-group {
            position: relative;
        }

        .input-prefix {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
            pointer-events: none;
        }

        .form-input.with-prefix {
            padding-left: 48px;
        }

        .form-help {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }

        .file-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .file-upload-area:hover {
            border-color: #3b82f6;
            background: #f8fafc;
        }

        .file-upload-area.dragover {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .current-image {
            max-width: 150px;
            border-radius: 8px;
            margin: 10px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 640px) {
            .modal {
                margin: 20px;
                max-width: calc(100vw - 40px);
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .modal-footer {
                flex-direction: column;
            }

            .btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Breadcrumb Navigation -->
    <nav class="breadcrumb">
        <a href="admin_dashboard.php">Admin Dashboard</a>
        <span class="breadcrumb-separator">></span>
        <span><?php echo $edit_mode ? 'Edit Room' : 'Add New Room'; ?></span>
    </nav>

    <!-- Modal Overlay -->
    <div class="modal-overlay">
        <div class="modal">
            <!-- Modal Header -->
            <div class="modal-header">
                <h2 class="modal-title"><?php echo $edit_mode ? 'Update room information' : 'Add new room'; ?></h2>
                <p class="modal-subtitle"><?php echo $edit_mode ? 'Modify the details for this hotel room' : 'Create a new room entry for the hotel'; ?></p>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        ✅ <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        ❌ <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="" enctype="multipart/form-data" id="roomForm">
                    <input type="hidden" name="room_id" value="<?php echo $edit_mode ? htmlspecialchars($room_id) : ''; ?>">
                    
                    <!-- Room Number -->
                    <div class="form-group">
                        <label class="form-label" for="room_number">Room Number</label>
                        <input 
                            type="text" 
                            id="room_number" 
                            name="room_number"
                            class="form-input" 
                            value="<?php echo htmlspecialchars($room_number); ?>"
                            placeholder="Enter room number"
                            required
                        >
                    </div>

                    <!-- Room Type -->
                    <div class="form-group">
                        <label class="form-label" for="type">Type</label>
                        <div class="room-type-selector">
                            <span class="room-type-icon">🏨</span>
                            <input 
                                type="text" 
                                id="type" 
                                name="type"
                                class="form-input with-icon" 
                                value="<?php echo htmlspecialchars($type); ?>"
                                placeholder="e.g., Presidential Ocean Suite, Deluxe Double Room"
                                required
                            >
                        </div>
                    </div>

                    <!-- Price and Discount Row -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="price">Price</label>
                            <div class="input-group">
                                <span class="input-prefix">$</span>
                                <input 
                                    type="number" 
                                    id="price" 
                                    name="price"
                                    class="form-input with-prefix" 
                                    value="<?php echo htmlspecialchars($price); ?>"
                                    placeholder="0.00"
                                    step="0.01"
                                    required
                                >
                            </div>
                            <p class="form-help">Price per night (USD)</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="discount">Discount (%)</label>
                            <input 
                                type="number" 
                                id="discount" 
                                name="discount"
                                class="form-input" 
                                value="<?php echo htmlspecialchars($discount); ?>"
                                placeholder="0.00"
                                step="0.01"
                                min="0"
                                max="100"
                            >
                            <p class="form-help">Optional discount percentage</p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea 
                            id="description" 
                            name="description"
                            class="form-textarea"
                            placeholder="Enter room description..."
                            required
                        ><?php echo htmlspecialchars($description); ?></textarea>
                    </div>

                    <!-- Availability -->
                    <div class="form-group">
                        <label class="form-label" for="availability">Availability</label>
                        <select id="availability" name="availability" class="form-select">
                            <option value="available" <?php if($availability=="available") echo 'selected'; ?>>Available</option>
                            <option value="booked" <?php if($availability=="booked") echo 'selected'; ?>>Booked</option>
                        </select>
                    </div>

                    <!-- Room Image -->
                    <div class="form-group">
                        <label class="form-label" for="image">Room Image</label>
                        <div class="file-upload-area" onclick="document.getElementById('image').click();">
                            <input type="file" id="image" name="image" accept="image/*" style="display: none;" onchange="previewImage(this)">
                            <div id="upload-text">
                                📸 Click to upload room image<br>
                                <small>Supported formats: JPG, PNG, GIF</small>
                            </div>
                        </div>
                        <?php if ($edit_mode && $image): ?>
                            <img src="assets/images/<?php echo htmlspecialchars($image); ?>" alt="Current Room Image" class="current-image" id="current-image">
                            <p class="form-help">Current image (upload new image to replace)</p>
                        <?php endif; ?>
                        <img id="image-preview" style="display: none; max-width: 150px; margin-top: 10px; border-radius: 8px;">
                    </div>

                    <!-- Form Actions -->
                    <div class="modal-footer">
                        <a href="admin_dashboard.php" class="btn btn-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <?php echo $edit_mode ? '💾 Update Room' : '➕ Add Room'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Image preview functionality
        function previewImage(input) {
            const preview = document.getElementById('image-preview');
            const uploadText = document.getElementById('upload-text');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    uploadText.innerHTML = '✅ Image selected: ' + input.files[0].name + '<br><small>Click to change</small>';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Form validation
        function validateForm() {
            const roomNumber = document.getElementById('room_number').value.trim();
            const type = document.getElementById('type').value.trim();
            const price = document.getElementById('price').value;
            const description = document.getElementById('description').value.trim();

            if (!roomNumber) {
                alert('Room number is required');
                return false;
            }

            if (!type) {
                alert('Room type is required');
                return false;
            }

            if (!price || parseFloat(price) <= 0) {
                alert('Please enter a valid price');
                return false;
            }

            if (!description) {
                alert('Room description is required');
                return false;
            }

            return true;
        }

        // Form submission with loading state
        document.getElementById('roomForm').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }

            const submitBtn = document.getElementById('submit-btn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '⏳ Processing...';
            submitBtn.disabled = true;

            // Re-enable button after a timeout in case of errors
            setTimeout(() => {
                if (submitBtn.disabled) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            }, 10000);
        });

        // Price input formatting
        document.getElementById('price').addEventListener('blur', function() {
            const value = parseFloat(this.value);
            if (!isNaN(value)) {
                this.value = value.toFixed(2);
            }
        });

        // Discount input formatting
        document.getElementById('discount').addEventListener('blur', function() {
            const value = parseFloat(this.value);
            if (!isNaN(value)) {
                this.value = value.toFixed(2);
            }
        });

        // Auto-resize textarea
        const textarea = document.getElementById('description');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });

        // Drag and drop for image upload
        const uploadArea = document.querySelector('.file-upload-area');
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('image').files = files;
                previewImage(document.getElementById('image'));
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                document.getElementById('roomForm').submit();
            }
        });
    </script>
</body>
</html>