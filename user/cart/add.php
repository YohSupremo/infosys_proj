<?php
include '../../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_message'] = 'You need to login to shop. Please login to add items to your cart.';
    // Store the product_id to redirect back after login
    if (isset($_POST['product_id'])) {
        $_SESSION['redirect_after_login'] = BASE_URL . '/user/products/view.php?id=' . intval($_POST['product_id']);
    }
    header('Location: ' . BASE_URL . '/user/auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    $user_id = $_SESSION['user_id'];
    
    // Validate quantity format
    if (empty($_POST['quantity']) || !is_numeric($_POST['quantity']) || intval($_POST['quantity']) < 1) {
        header('Location: ../products/view.php?id=' . $product_id . '&error=invalid_quantity');
        exit();
    }
    
    if ($product_id > 0 && $quantity > 0) {
        // Check product stock
        $product_stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = ? AND is_active = 1");
        $product_stmt->bind_param("i", $product_id);
        $product_stmt->execute();
        $product_result = $product_stmt->get_result();
        
        if ($product_result->num_rows > 0) {
            $product = $product_result->fetch_assoc();
            
            if ($quantity <= $product['stock_quantity']) {
                // Get or create cart
                $cart_stmt = $conn->prepare("SELECT cart_id FROM shopping_cart WHERE user_id = ?");
                $cart_stmt->bind_param("i", $user_id);
                $cart_stmt->execute();
                $cart_result = $cart_stmt->get_result();
                
                if ($cart_result->num_rows > 0) {
                    $cart = $cart_result->fetch_assoc();
                    $cart_id = $cart['cart_id'];
                } else {
                    $create_cart = $conn->prepare("INSERT INTO shopping_cart (user_id) VALUES (?)");
                    $create_cart->bind_param("i", $user_id);
                    $create_cart->execute();
                    $cart_id = $conn->insert_id;
                    $create_cart->close();
                }
                $cart_stmt->close();
                
                // Check if item already in cart
                $check_stmt = $conn->prepare("SELECT cart_item_id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
                $check_stmt->bind_param("ii", $cart_id, $product_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $item = $check_result->fetch_assoc();
                    $new_quantity = $item['quantity'] + $quantity;
                    if ($new_quantity <= $product['stock_quantity']) {
                        $update_stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
                        $update_stmt->bind_param("ii", $new_quantity, $item['cart_item_id']);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                } else {
                    $insert_stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
                    $insert_stmt->bind_param("iii", $cart_id, $product_id, $quantity);
                    $insert_stmt->execute();
                    $insert_stmt->close();
                }
                $check_stmt->close();
                
                header('Location: index.php?success=1');
            } else {
                header('Location: index.php?error=insufficient_stock');
            }
        } else {
            header('Location: index.php?error=product_not_found');
        }
        $product_stmt->close();
    } else {
        header('Location: index.php?error=invalid');
    }
} else {
    header('Location: index.php');
}
exit();
?>

