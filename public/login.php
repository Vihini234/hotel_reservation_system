<?php
include '../config/db.php';
include '../includes/auth.php';
$error = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(string: $_POST['username']);
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($id, $hash, $role);
    if ($stmt->fetch() && password_verify($password, $hash)) {
        $_SESSION['user_id'] = $id;
        $_SESSION['role'] = $role;
        if ($role === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: customer_dashboard.php");
        }
        exit;
    } else {
        $error = "Invalid credentials or not registered.";
    }
    $stmt->close();
}
?>
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
<header class="main-header">
    <nav class="nav">
        <a href="index.php">Home</a>
        <a href="login.php" class="active">Login</a>
        <a href="register.php">Register</a>
    </nav>
</header>
<div class="container">
    <h2>Login</h2>
    <?php if ($error): ?><div class="alert"><?php echo $error; ?></div><?php endif; ?>
    <form method="post" action="">
        <label>Username:</label>
        <input type="text" name="username" required>
        <label>Password:</label>
        <input type="password" name="password" required>
        <button type="submit">Login</button>
    </form>
    <p>Not registered? <a href="register.php">Register here</a>.</p>
</div>
