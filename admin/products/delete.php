<?php
include '../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    
    if ($product_id > 0) {
        // Get image URL before deleting
        $stmt = $conn->prepare("SELECT image_url FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            // Delete image file if exists
            if ($product['image_url'] && file_exists('../../' . $product['image_url'])) {
                unlink('../../' . $product['image_url']);
            }
        }
        $stmt->close();
        
        // Delete product (cascade will handle related records)
        $delete_stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $delete_stmt->bind_param("i", $product_id);
        $delete_stmt->execute();
        $delete_stmt->close();
    }
}

header('Location: index.php');
exit();
?>

