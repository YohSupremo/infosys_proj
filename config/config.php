<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'infosys';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to check user role
function hasRole($role_name) {
    if (!isLoggedIn()) {
        return false;
    }
    return isset($_SESSION['role_name']) && $_SESSION['role_name'] === $role_name;
}

// Helper function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../user/auth/login.php');
        exit();
    }
}

// Helper function to require admin
function requireAdmin() {
    requireLogin();
    if (!hasRole('Admin')) {
        header('Location: ../index.php');
        exit();
    }
}

// Helper function to sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>

