<?php
include '../config/db.php';
include '../includes/auth.php';
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate username
    $username = trim($_POST['username']);
    $username = filter_var($username, FILTER_SANITIZE_STRING);
    if (empty($username) || strlen($username) < 3 || strlen($username) > 30 || !preg_match('/^[A-Za-z0-9_]+$/', $username)) {
        $error = "Invalid username. Only letters, numbers, and underscores (3-30 chars) allowed.";
    } else {
        // 
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];
        if (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $error = "Username already exists.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $role = 'customer';
                $stmt2 = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                $stmt2->bind_param("sss", $username, $hash, $role);
                if ($stmt2->execute()) {
                    // Set a cookie for the username (expires in 7 days)
                    setcookie('registered_username', $username, time() + (7 * 24 * 60 * 60), "/", "", false, true);
                    header('Location: login.php');
                    exit;
                } else {
                    $error = "Registration failed. Try again.";
                }
                $stmt2->close();
            }
            $stmt->close();
        }
    }
}
?>
<header class="main-header">
    <nav class="nav">
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
        <a href="register.php" class="active">Register</a>
    </nav>
</header>
<div class="container">
    <h2>Register</h2>
    <?php if ($error): ?><div class="alert"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert success"><?php echo $success; ?></div><?php endif; ?>
    <form method="post" action="">
        <label>Username:</label>
        <input type="text" name="username" required>
        <label>Password:</label>
        <input type="password" name="password" required>
        <label>Confirm Password:</label>
        <input type="password" name="confirm_password" required>
        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a>.</p>
</div>
<style>
    .main-header {
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        padding: 1.2em 0;
        margin-bottom: 2em;
    }
    .main-header .nav {
        display: flex;
        justify-content: flex-start;
        align-items: center;
        gap: 2em;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2em;
    }
    .main-header .nav a {
        color: #2563eb;
        font-weight: 600;
        text-decoration: none;
        font-size: 1.1em;
        padding: 0.3em 1em;
        border-radius: 6px;
        transition: background 0.2s, color 0.2s;
    }
    .main-header .nav a:hover, .main-header .nav a.active {
        background: #2563eb;
        color: #fff;
    }
    body {
        font-family: Arial, sans-serif;
        background: #f4f6f8;
        margin: 0;
        padding: 0;
    }
    .container {
        max-width: 400px;
        margin: 60px auto;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 32px 24px;
    }
    h2 {
        text-align: center;
        margin-bottom: 24px;
        color: #333;
    }
    form label {
        display: block;
        margin-bottom: 6px;
        color: #555;
        font-weight: 500;
    }
    form input[type="text"],
    form input[type="password"] {
        width: 100%;
        padding: 10px 8px;
        margin-bottom: 18px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 15px;
        background: #fafbfc;
    }
    form button {
        width: 100%;
        padding: 10px 0;
        background: #007bff;
        color: #fff;
        border: none;
        border-radius: 4px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.2s;
    }
    form button:hover {
        background: #0056b3;
    }
    .alert {
        background: #ffe0e0;
        color: #b30000;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 18px;
        text-align: center;
        border: 1px solid #ffb3b3;
    }
    .alert.success {
        background: #e0ffe0;
        color: #31708f;
        border: 1px solid #b2dfdb;
    }
    p {
        text-align: center;
        margin-top: 18px;
    }
    a {
        color: #007bff;
        text-decoration: none;
    }
    a:hover {
        text-decoration: underline;
    }
</style>
