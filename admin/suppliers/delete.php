<?php
include '../../config/config.php';
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = intval($_POST['supplier_id'] ?? 0);
    
    if ($supplier_id > 0) {
        $delete_stmt = $conn->prepare("UPDATE suppliers SET is_active = 0 WHERE supplier_id = ?");
        $delete_stmt->bind_param("i", $supplier_id);
        $delete_stmt->execute();
        $delete_stmt->close();
    }
}

header('Location: index.php');
exit();
?>

