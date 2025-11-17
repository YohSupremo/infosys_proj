<?php
include '../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    
    if ($product_id > 0) {
        // First check if product is referenced in inventory_history
        $history_stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM inventory_history WHERE product_id = ?");
        $history_stmt->bind_param("i", $product_id);
        $history_stmt->execute();
        $history_result = $history_stmt->get_result();
        $history_row = $history_result->fetch_assoc();
        $history_count = $history_row ? intval($history_row['cnt']) : 0;
        $history_stmt->close();

        // Also check if product is referenced in order_items
        $orders_stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM order_items WHERE product_id = ?");
        $orders_stmt->bind_param("i", $product_id);
        $orders_stmt->execute();
        $orders_result = $orders_stmt->get_result();
        $orders_row = $orders_result->fetch_assoc();
        $orders_count = $orders_row ? intval($orders_row['cnt']) : 0;
        $orders_stmt->close();

        if ($history_count > 0 || $orders_count > 0) {
            // Do not delete if there is related history or orders
            $_SESSION['error'] = 'Cannot delete this product because it has related inventory history or order records. You may set it to Inactive instead.';
        } else {
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
            
            // Safe to delete product
            $delete_stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
            $delete_stmt->bind_param("i", $product_id);
            $delete_stmt->execute();
            $delete_stmt->close();
        }
    }
}

header('Location: index.php');
exit();
?>

