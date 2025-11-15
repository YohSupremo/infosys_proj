<?php
include '../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $contact_number = sanitize($_POST['contact_number'] ?? '');
    $role_id = intval($_POST['role_id'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($email) || empty($password) || empty($first_name) || empty($last_name) || $role_id <= 0) {
        $_SESSION['error'] = 'Please fill in all required fields.';
        header('Location: create.php');
        exit();
    } elseif (strlen($password) < 6) {
        $_SESSION['error'] = 'Password must be at least 6 characters long.';
        header('Location: create.php');
        exit();
    } else {
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $_SESSION['error'] = 'Email already exists.';
            $check_stmt->close();
            header('Location: create.php');
            exit();
        }
        $check_stmt->close();
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (role_id, email, password_hash, first_name, last_name, contact_number, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssi", $role_id, $email, $password_hash, $first_name, $last_name, $contact_number, $is_active);
        
        if ($stmt->execute()) {
            $stmt->close();
            header('Location: index.php?success=1');
            exit();
        } else {
            $stmt->close();
            $_SESSION['error'] = 'Failed to add user.';
            header('Location: create.php');
            exit();
        }
    }
} else {
    header('Location: create.php');
    exit();
}
?>
