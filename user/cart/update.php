<?php
include '../../config/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_item_id = intval($_POST['cart_item_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    $user_id = $_SESSION['user_id'];
    
    if ($cart_item_id > 0 && $quantity > 0) {
        // Verify cart ownership and check stock
        $check_stmt = $conn->prepare("SELECT ci.cart_item_id, p.stock_quantity FROM cart_items ci JOIN shopping_cart sc ON ci.cart_id = sc.cart_id JOIN products p ON ci.product_id = p.product_id WHERE ci.cart_item_id = ? AND sc.user_id = ?");
        $check_stmt->bind_param("ii", $cart_item_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $item = $check_result->fetch_assoc();
            if ($quantity <= $item['stock_quantity']) {
                $update_stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
                $update_stmt->bind_param("ii", $quantity, $cart_item_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
        $check_stmt->close();
    }
}

header('Location: index.php');
exit();
?>

