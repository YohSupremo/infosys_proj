<?php
include '../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_id = intval($_POST['role_id'] ?? 0);
    $role_name = sanitize($_POST['role_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    
    if (!$role_id) {
        header('Location: index.php');
        exit();
    }
    
    if (empty($role_name)) {
        $_SESSION['error'] = 'Role name is required.';
        header('Location: edit.php?id=' . $role_id);
        exit();
    }
    
    $update_stmt = $conn->prepare("UPDATE roles SET role_name = ?, description = ? WHERE role_id = ?");
    $update_stmt->bind_param("ssi", $role_name, $description, $role_id);
    
    if ($update_stmt->execute()) {
        $update_stmt->close();
        header('Location: index.php?success=1');
        exit();
    } else {
        $update_stmt->close();
        $_SESSION['error'] = 'Failed to update role.';
        header('Location: edit.php?id=' . $role_id);
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}
?>
