<?php
include '../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_id = intval($_POST['role_id'] ?? 0);
    
    if ($role_id > 0) {
        $delete_stmt = $conn->prepare("DELETE FROM roles WHERE role_id = ?");
        $delete_stmt->bind_param("i", $role_id);
        $delete_stmt->execute();
        $delete_stmt->close();
    }
}

header('Location: index.php');
exit();
?>

