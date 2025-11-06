<?php
include '../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = intval($_POST['category_id'] ?? 0);
    
    if ($category_id > 0) {
        $delete_stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
        $delete_stmt->bind_param("i", $category_id);
        $delete_stmt->execute();
        $delete_stmt->close();
    }
}

header('Location: index.php');
exit();
?>

