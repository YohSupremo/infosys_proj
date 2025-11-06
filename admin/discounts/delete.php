<?php
include '../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $discount_id = intval($_POST['discount_id'] ?? 0);
    
    if ($discount_id > 0) {
        $delete_stmt = $conn->prepare("DELETE FROM discount_codes WHERE discount_id = ?");
        $delete_stmt->bind_param("i", $discount_id);
        $delete_stmt->execute();
        $delete_stmt->close();
    }
}

header('Location: index.php');
exit();
?>

