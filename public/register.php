<?php
include '../config/db.php';
include '../includes/auth.php';
include '../includes/header.php';
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    if ($password !== $confirm) {
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
?>
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
<style>
body {
    font-family: Arial, sans-serif;
    background: #f6f8fa;
    margin: 0;
    padding: 0;
}
h2 {
    text-align: center;
    margin-top: 40px;
    color: #333;
}
form {
    background: #fff;
    max-width: 400px;
    margin: 40px auto 0 auto;
    padding: 30px 30px 20px 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
    gap: 15px;
}
label {
    font-weight: bold;
    margin-bottom: 5px;
    color: #444;
}
input[type="text"], input[type="password"] {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 15px;
    background: #f9f9f9;
    transition: border 0.2s;
}
input[type="text"]:focus, input[type="password"]:focus {
    border-color: #007bff;
    outline: none;
}
button[type="submit"] {
    background: #007bff;
    color: #fff;
    border: none;
    padding: 12px;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    margin-top: 10px;
    transition: background 0.2s;
}
button[type="submit"]:hover {
    background: #0056b3;
}
.alert {
    background: #ffe0e0;
    color: #a94442;
    border: 1px solid #f5c6cb;
    padding: 10px 15px;
    border-radius: 4px;
    margin: 20px auto 0 auto;
    max-width: 400px;
    text-align: center;
}
.alert.success {
    background: #e0ffe0;
    color: #31708f;
    border: 1px solid #b2dfdb;
}
</style>
