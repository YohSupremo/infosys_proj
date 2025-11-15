<?php
include '../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_name = sanitize($_POST['role_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    
    if (empty($role_name)) {
        $_SESSION['error'] = 'Role name is required.';
        header('Location: create.php');
        exit();
    }
    
    $stmt = $conn->prepare("INSERT INTO roles (role_name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $role_name, $description);
    
    if ($stmt->execute()) {
        $stmt->close();
        header('Location: index.php?success=1');
        exit();
    } else {
        $stmt->close();
        $_SESSION['error'] = 'Failed to add role.';
        header('Location: create.php');
        exit();
    }
} else {
    header('Location: create.php');
    exit();
}
?>
