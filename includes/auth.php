<?php
session_start();
function is_logged_in() {
    return isset($_SESSION['user_id']);
}
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}
function is_customer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}
?>
