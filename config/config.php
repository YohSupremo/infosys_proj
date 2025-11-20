<?php
define('BASE_URL', '/infosys_proj');

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'infosys';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($role_name) {
    if (!isLoggedIn()) {
        return false;
    }
    return isset($_SESSION['role_name']) && $_SESSION['role_name'] === $role_name;
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_message'] = 'Please login to access this page.';
        header('Location: ' . BASE_URL . '/user/auth/login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!hasRole('Admin')) {
        $_SESSION['redirect_message'] = 'You do not have permissions to access this page.';
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }
}

function requireAdminOrInventoryManager() {
    requireLogin();
    if (!hasRole('Admin') && !hasRole('Inventory Manager')) {
        $_SESSION['redirect_message'] = 'You do not have permissions to access this page.';
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>

