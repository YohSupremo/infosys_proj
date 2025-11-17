<?php
include '../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    
    if ($product_id > 0) {
        // HARD DELETE: remove dependent rows first to satisfy foreign keys

        // Delete inventory history for this product
        $history_stmt = $conn->prepare("DELETE FROM inventory_history WHERE product_id = ?");
        $history_stmt->bind_param("i", $product_id);
        $history_stmt->execute();
        $history_stmt->close();

        // Delete restocking items that reference this product
        $restock_items_stmt = $conn->prepare("DELETE FROM restocking_items WHERE product_id = ?");
        $restock_items_stmt->bind_param("i", $product_id);
        $restock_items_stmt->execute();
        $restock_items_stmt->close();

        // Delete order items that reference this product
        $orders_stmt = $conn->prepare("DELETE FROM order_items WHERE product_id = ?");
        $orders_stmt->bind_param("i", $product_id);
        $orders_stmt->execute();
        $orders_stmt->close();

        // Delete cart items that reference this product
        $cart_stmt = $conn->prepare("DELETE FROM cart_items WHERE product_id = ?");
        $cart_stmt->bind_param("i", $product_id);
        $cart_stmt->execute();
        $cart_stmt->close();

        // Get image URL before deleting product row
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
        
        // Now delete the product itself (other related tables use ON DELETE CASCADE)
        $delete_stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $delete_stmt->bind_param("i", $product_id);
        $delete_stmt->execute();
        $delete_stmt->close();
    }
}

header('Location: index.php');
exit();
?>

