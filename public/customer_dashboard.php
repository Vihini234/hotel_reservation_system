<?php
include '../config/db.php';
include '../includes/auth.php';
if (!is_logged_in() || !is_customer()) {
    header('Location: login.php');
    exit;
}

$customer_name = 'Customer';
if (isset($_SESSION['user'])) {
    if (!empty($_SESSION['user']['name'])) {
        $customer_name = $_SESSION['user']['name'];
    } elseif (!empty($_SESSION['user']['first_name']) || !empty($_SESSION['user']['last_name'])) {
        $customer_name = trim(($_SESSION['user']['first_name'] ?? '') . ' ' . ($_SESSION['user']['last_name'] ?? ''));
    }
}
// Handle booking success message
$ok = isset($_GET['ok']) ? $_GET['ok'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

$search = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search'])) : (isset($_COOKIE['search']) ? $_COOKIE['search'] : '');
if (isset($_GET['search'])) setcookie('search', $search, time()+3600, '/');

$num_rooms_needed = isset($_GET['num_rooms']) ? max(1, min(20, (int)$_GET['num_rooms'])) : (isset($_COOKIE['num_rooms']) ? (int)$_COOKIE['num_rooms'] : 1);
if (isset($_GET['num_rooms'])) setcookie('num_rooms', $num_rooms_needed, time()+3600, '/');

$start_date = isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : (isset($_COOKIE['start_date']) ? $_COOKIE['start_date'] : '');
if (isset($_GET['start_date'])) setcookie('start_date', $start_date, time()+3600, '/');

$end_date = isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : (isset($_COOKIE['end_date']) ? $_COOKIE['end_date'] : '');
if (isset($_GET['end_date'])) setcookie('end_date', $end_date, time()+3600, '/');

$adults = isset($_GET['adults']) ? max(1, (int)$_GET['adults']) : (isset($_COOKIE['adults']) ? (int)$_COOKIE['adults'] : 1);
if (isset($_GET['adults'])) setcookie('adults', $adults, time()+3600, '/');

$children = isset($_GET['children']) ? max(0, (int)$_GET['children']) : (isset($_COOKIE['children']) ? (int)$_COOKIE['children'] : 0);
if (isset($_GET['children'])) setcookie('children', $children, time()+3600, '/');

?>
<header class="header" style="position:sticky;top:0;z-index:100;">
    <div class="header-content" style="max-width:1200px;margin:0 auto;padding:0 2rem;display:flex;justify-content:space-between;align-items:center;">
        <div class="logo" style="display:flex;align-items:center;gap:0.5em;font-size:1.8rem;font-weight:bold;color:#2563eb;">
            <span style="font-size:2em;">🏨</span>
            <span>Marino Beach Hotel</span>
        </div>
        <nav>
            <ul class="nav-links" style="display:flex;gap:2em;list-style:none;margin:0;padding:0;align-items:center;">
                <li><a href="index.php" style="text-decoration:none;color:#2563eb;font-weight:600;padding:0.4em 1em;border-radius:6px;">Home</a></li>
                <li><a href="logout.php" style="text-decoration:none;color:#fff;background:#e74c3c;font-weight:600;padding:0.4em 1em;border-radius:6px;">Logout</a></li>
            </ul>
        </nav>
        <div class="user-info" style="margin-left:1em;">
            <span class="welcome-text" style="color:#2563eb;font-weight:500;">Welcome, <?php echo htmlspecialchars($customer_name); ?></span>
        </div>
    </div>
</header>
<style>
    .body-bg-blur {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        z-index: -1;
        background: url('assets/images/20baed86c269ff21ba8ab43f492fbe2f.jpg') center center/cover no-repeat;
        filter: blur(10px);
        width: 100vw;
        height: 100vh;
    }
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
// Show all rooms by default, only show available rooms when searching
$status_available = 'available';

if (isset($_GET['search']) && $search !== '') {
    $search_esc = $conn->real_escape_string(strtolower(trim($search)));
    $sql = "SELECT * FROM rooms WHERE LOWER(TRIM(availability)) = 'available' AND (LOWER(TRIM(room_number)) LIKE '%$search_esc%' OR LOWER(TRIM(type)) LIKE '%$search_esc%' OR LOWER(TRIM(description)) LIKE '%$search_esc%')";
    $searching = true;
} else {
    $sql = "SELECT * FROM rooms";
    $searching = false;
}
$result = $conn->query($sql);
?>
    <div class="container">
        <?php if ($ok): ?>
            <div class="alert success"><?php echo htmlspecialchars($ok); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert" style="background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;">Error: <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <h1 class="page-title">Available Rooms</h1>
        <form class="search-form" method="get" action="">
            <div class="form-row">
                <div class="form-group">
                        <label for="num_rooms">Number of Rooms Needed</label>
                        <input type="number" id="num_rooms" name="num_rooms" min="1" max="20" value="<?php echo $num_rooms_needed; ?>">
                </div>
                <div class="form-group">
                    <label for="start_date">Check-in</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="form-group">
                    <label for="end_date">Check-out</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="form-group">
                    <label for="adults">Adults</label>
                    <input type="number" id="adults" name="adults" min="1" value="<?php echo $adults; ?>">
                </div>
                <div class="form-group">
                    <label for="children">Children</label>
                    <input type="number" id="children" name="children" min="0" value="<?php echo $children; ?>">
                </div>
                <button type="submit" class="search-btn">Search</button>
            </div>
        </form>
        <div class="rooms-grid">
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <?php
                // When searching, only display available rooms (SQL already filters, but double check in PHP)
                $is_available = strtolower(trim($row['availability'])) === 'available';
                if ($searching && !$is_available) {
                    continue;
                }
                ?>
                <div class="room-card">
                    <div class="room-image">
                        <?php
                        $img = isset($row['image']) ? $row['image'] : '';
                        $img_path = '';
                        $debug_path = '';
                        if (!empty($img)) {
                            $try_path = dirname(__DIR__) . '/assets/images/' . $img;
                            $debug_path = $try_path;
                            if (file_exists($try_path)) {
                                $img_path = '../assets/images/' . $img;
                            }
                        }
                        // Removed debug output
                        if ($img_path): ?>
                            <img src="<?php echo htmlspecialchars($img_path); ?>" alt="Room Image">
                        <?php else: ?>
                            <div style="width:100%;height:100%;background:#eee;display:flex;align-items:center;justify-content:center;color:#aaa;">No image</div>
                        <?php endif; ?>
                    </div>
                    <div class="room-details">
                        <h3 class="room-title">Room <?php echo htmlspecialchars($row['room_number']); ?></h3>
                        <p class="room-subtitle"><?php echo htmlspecialchars($row['type']); ?><?php if (!empty($row['availability'])): ?> • <?php echo htmlspecialchars(ucfirst(trim($row['availability']))); ?><?php endif; ?></p>
                        <p class="room-description"><?php echo htmlspecialchars($row['description']); ?></p>
                    </div>
                    <div class="room-pricing">
                        <div class="price-label">Per Night</div>
                        <div class="price">$<?php echo number_format($row['price'], 2); ?></div>
                        <?php if ($row['discount'] > 0): ?>
                            <div class="price-note"><?php echo $row['discount']; ?>% OFF</div>
                        <?php endif; ?>
                        <?php if ($searching): ?>
                            <form method="post" action="book_room.php">
                                <input type="hidden" name="room_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="start_date" value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>">
                                <input type="hidden" name="end_date" value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>">
                                <input type="hidden" name="adults" value="<?php echo isset($_GET['adults']) ? (int)$_GET['adults'] : 1; ?>">
                                <input type="hidden" name="children" value="<?php echo isset($_GET['children']) ? (int)$_GET['children'] : 0; ?>">
                                <button type="submit" class="book-btn">Book Now</button>
                            </form>
                        <?php else: ?>
                            <?php if ($is_available): ?>
                                <form method="post" action="book_room.php">
                                    <input type="hidden" name="room_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="start_date" value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>">
                                    <input type="hidden" name="end_date" value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>">
                                    <input type="hidden" name="adults" value="<?php echo isset($_GET['adults']) ? (int)$_GET['adults'] : 1; ?>">
                                    <input type="hidden" name="children" value="<?php echo isset($_GET['children']) ? (int)$_GET['children'] : 0; ?>">
                                    <button type="submit" class="book-btn">Book Now</button>
                                </form>
                            <?php else: ?>
                                <div style="color:#888;font-size:0.95em;margin-top:0.5em;">Not available</div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-rooms">No rooms available at the moment.</div>
        <?php endif; ?>
        </div>
    </div>
<?php include '../includes/footer.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marino Beach Hotel - Available Rooms</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            line-height: 1.6;
        }

        .header {
            background: #fff;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            color: #2980b9;
            font-size: 1.8rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #34495e;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #2980b9;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-title {
            text-align: center;
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 2rem;
            font-weight: 300;
        }

        .search-form {
            background: #fff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 3rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
            gap: 1.5rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #34495e;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-group input {
            padding: 0.8rem;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            background: #fafbfc;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2980b9;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(41, 128, 185, 0.1);
        }

        .search-btn {
            background: linear-gradient(135deg, #2980b9, #3498db);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(41, 128, 185, 0.3);
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(41, 128, 185, 0.4);
        }

        .rooms-grid {
            display: grid;
            gap: 2rem;
        }

        .room-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            transition: all 0.3s;
            display: grid;
            grid-template-columns: 300px 1fr auto;
            min-height: 200px;
        }

        .room-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
        }

        .room-image {
            position: relative;
            overflow: hidden;
        }

        .room-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .room-card:hover .room-image img {
            transform: scale(1.05);
        }

        .room-details {
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .room-title {
            color: #2c3e50;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .room-subtitle {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .room-description {
            color: #5a6c7d;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .room-features {
            display: flex;
            gap: 1rem;
            margin-top: auto;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            color: #7f8c8d;
            font-size: 0.85rem;
        }

        .room-pricing {
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: #f8fafb;
            min-width: 200px;
        }

        .price-label {
            color: #7f8c8d;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }

        .price {
            font-size: 2rem;
            font-weight: bold;
            color: #27ae60;
            margin-bottom: 0.3rem;
        }

        .price-note {
            color: #95a5a6;
            font-size: 0.75rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .book-btn {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }

        .book-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
        }

        .no-rooms {
            text-align: center;
            padding: 4rem 2rem;
            color: #7f8c8d;
            font-size: 1.1rem;
        }

        .cart-sidebar {
            position: fixed;
            top: 120px;
            right: 2rem;
            background: #fff;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            min-width: 250px;
        }

        .cart-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .cart-total {
            font-size: 1.2rem;
            font-weight: bold;
            color: #27ae60;
        }

        @media (max-width: 1024px) {
            .room-card {
                grid-template-columns: 1fr;
            }
            
            .room-image {
                height: 200px;
            }
            
            .cart-sidebar {
                position: static;
                margin-top: 2rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                padding: 0 1rem;
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-links {
                gap: 1rem;
            }
            
            .container {
                padding: 1rem;
            }
            
            .room-details {
                padding: 1.5rem;
            }
            
            .room-pricing {
                padding: 1.5rem;
            }
        }

        .success-message {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            padding: 1rem 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
            border: 1px solid #c3e6cb;
        }

        .amenity-icon {
            width: 16px;
            height: 16px;
            fill: currentColor;
        }
    </style>
</head>
<body>
    <div class="body-bg-blur"></div>

                
            </nav>
        </div>
    </header>

        


    <div class="rooms-grid">
    </div>


    <script>
        // Simple search functionality
        document.querySelector('.search-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const searchTerm = document.getElementById('search').value.toLowerCase();
            const rooms = document.querySelectorAll('.room-card');
            
            rooms.forEach(room => {
                const title = room.querySelector('.room-title').textContent.toLowerCase();
                const description = room.querySelector('.room-description').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || description.includes(searchTerm) || searchTerm === '') {
                    room.style.display = 'grid';
                } else {
                    room.style.display = 'none';
                }
            });
        });

        // Book button functionality
        document.querySelectorAll('.book-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const roomCard = this.closest('.room-card');
                const roomTitle = roomCard.querySelector('.room-title').textContent;
                const price = roomCard.querySelector('.price').textContent;
                
                alert(`Booking ${roomTitle} for ${price} per night`);
            });
        });

        // Set default dates
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        
        document.getElementById('start_date').value = today.toISOString().split('T')[0];
        document.getElementById('end_date').value = tomorrow.toISOString().split('T')[0];
    </script>
</body>
</html>