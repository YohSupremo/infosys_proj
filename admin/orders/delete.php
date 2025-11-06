<?php
include '../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = intval($_POST['order_id'] ?? 0);
    
    if ($order_id > 0) {
        $delete_stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
        $delete_stmt->bind_param("i", $order_id);
        $delete_stmt->execute();
        $delete_stmt->close();
    }
}

header('Location: index.php');
exit();
?>

