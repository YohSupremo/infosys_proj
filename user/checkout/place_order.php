<?php
include '../../config/config.php';
requireLogin();

// get checkout data from session (set by checkout/index.php)
if (isset($_SESSION['checkout_data'])) {
	require '../../config/email_config.php';
	require '../../vendor/autoload.php';
    $user_id = $_SESSION['user_id'];
    $address_id = intval($_SESSION['checkout_data']['address_id'] ?? 0);
    $payment_method = sanitize($_SESSION['checkout_data']['payment_method'] ?? 'Cash on Delivery');
    $discount_code = sanitize($_SESSION['checkout_data']['discount_code'] ?? '');
    
    // Clear session data
    unset($_SESSION['checkout_data']);
    
    // verify address belongs to user
    $addr_stmt = $conn->prepare("SELECT address_id FROM user_addresses WHERE address_id = ? AND user_id = ?");
    $addr_stmt->bind_param("ii", $address_id, $user_id);
    $addr_stmt->execute();
    $addr_result = $addr_stmt->get_result();
    
    if ($addr_result->num_rows === 0) {
        header('Location: ' . BASE_URL . '/user/checkout/index.php?error=invalid_address');
        exit();
    }
    $addr_stmt->close();
    
    // get cart items
    $cart_stmt = $conn->prepare("SELECT cart_id FROM shopping_cart WHERE user_id = ?");
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    
    if ($cart_result->num_rows === 0) {
        header('Location: ' . BASE_URL . '/user/checkout/index.php?error=empty_cart');
        exit();
    }
    
    $cart = $cart_result->fetch_assoc();
    $cart_id = $cart['cart_id'];
    
    $items_stmt = $conn->prepare("SELECT ci.*, p.product_name, p.price, p.stock_quantity FROM cart_items ci JOIN products p ON ci.product_id = p.product_id WHERE ci.cart_id = ?");
    $items_stmt->bind_param("i", $cart_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    $cart_items = [];
    $subtotal = 0;
    
    while ($item = $items_result->fetch_assoc()) {
        if ($item['quantity'] > $item['stock_quantity']) {
            header('Location: ' . BASE_URL . '/user/checkout/index.php?error=insufficient_stock');
            exit();
        }
        $item_total = $item['price'] * $item['quantity'];
        $subtotal += $item_total;
        $cart_items[] = $item;
    }
    $items_stmt->close();
    $cart_stmt->close();
    
    // process discount
    $discount_id = null;
    $discount_amount = 0;
    
    if (!empty($discount_code)) {
        $disc_stmt = $conn->prepare("SELECT * FROM discount_codes WHERE code = ? AND is_active = 1 AND (expiration_date IS NULL OR expiration_date > NOW()) AND (usage_limit IS NULL OR times_used < usage_limit)");
        $disc_stmt->bind_param("s", $discount_code);
        $disc_stmt->execute();
        $disc_result = $disc_stmt->get_result();
        
        if ($disc_result->num_rows > 0) {
            $discount = $disc_result->fetch_assoc();
            
            // check minimum purchase amount first
            if (!$discount['min_purchase_amount'] || $subtotal >= $discount['min_purchase_amount']) {
                $discount_id = $discount['discount_id'];
                
                // calculate discount based on type
                if ($discount['discount_type'] === 'percentage') {
                    $discount_amount = ($subtotal * $discount['discount_value']) / 100;
                    if ($discount['max_discount_amount'] && $discount_amount > $discount['max_discount_amount']) {
                        $discount_amount = $discount['max_discount_amount'];
                    }
                } else {
                    $discount_amount = $discount['discount_value'];
                }
            }
        }
        $disc_stmt->close();
    }
    
    $total_amount = $subtotal - $discount_amount;
    if ($total_amount < 0) $total_amount = 0;
    
    // create order
    $order_stmt = $conn->prepare("INSERT INTO orders (user_id, address_id, discount_id, payment_method, subtotal, discount_amount, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $order_stmt->bind_param("iiisddd", $user_id, $address_id, $discount_id, $payment_method, $subtotal, $discount_amount, $total_amount);
    $order_stmt->execute();
    $order_id = $conn->insert_id;
    $order_stmt->close();
    
    // create order items and update stock
    foreach ($cart_items as $item) {
        $item_subtotal = $item['price'] * $item['quantity'];
        $order_item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
        $order_item_stmt->bind_param("iisidd", $order_id, $item['product_id'], $item['product_name'], $item['quantity'], $item['price'], $item_subtotal);
        $order_item_stmt->execute();
        $order_item_stmt->close();
        
        // update stock
        $new_stock = $item['stock_quantity'] - $item['quantity'];
        $update_stock = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
        $update_stock->bind_param("ii", $new_stock, $item['product_id']);
        $update_stock->execute();
        $update_stock->close();
        
        // record inventory history
        $inv_stmt = $conn->prepare("INSERT INTO inventory_history (product_id, transaction_type, quantity_change, previous_stock, new_stock, reference_id, reference_type) VALUES (?, 'sale', ?, ?, ?, ?, 'order')");
        $qty_change = -$item['quantity'];
        $inv_stmt->bind_param("iiiii", $item['product_id'], $qty_change, $item['stock_quantity'], $new_stock, $order_id);
        $inv_stmt->execute();
        $inv_stmt->close();
    }
    
    // update discount usage
    if ($discount_id) {
        $update_disc = $conn->prepare("UPDATE discount_codes SET times_used = times_used + 1 WHERE discount_id = ?");
        $update_disc->bind_param("i", $discount_id);
        $update_disc->execute();
        $update_disc->close();
        
        $disc_usage = $conn->prepare("INSERT INTO discount_usage (discount_id, user_id, order_id, discount_amount) VALUES (?, ?, ?, ?)");
        $disc_usage->bind_param("iiid", $discount_id, $user_id, $order_id, $discount_amount);
        $disc_usage->execute();
        $disc_usage->close();
    }
    
    // clear cart
    $clear_cart = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
    $clear_cart->bind_param("i", $cart_id);
    $clear_cart->execute();
    $clear_cart->close();
    
	// send order confirmation email to customer
	$user_stmt = $conn->prepare("SELECT first_name, last_name, email FROM users WHERE user_id = ?");
	$user_stmt->bind_param("i", $user_id);
	$user_stmt->execute();
	$user_res = $user_stmt->get_result();
	if ($user_res && $user_res->num_rows > 0) {
		$u = $user_res->fetch_assoc();
		// fetch items for email
		$oi_stmt = $conn->prepare("SELECT product_name, quantity, unit_price, subtotal FROM order_items WHERE order_id = ?");
		$oi_stmt->bind_param("i", $order_id);
		$oi_stmt->execute();
		$oi_res = $oi_stmt->get_result();
		$order_items = array();
		while ($row = $oi_res->fetch_assoc()) {
			$order_items[] = $row;
		}
		$oi_stmt->close();
		// send status email (Pending)
		sendOrderStatusEmail(
			$u['email'],
			$u['first_name'] . ' ' . $u['last_name'],
			$order_id,
			'Pending',
			$order_items,
			$subtotal,
			$discount_amount,
			$total_amount
		);
	}
	$user_stmt->close();
	
    header("Location: " . BASE_URL . "/user/checkout/success.php?order_id=$order_id");
    exit();
} else {
    header('Location: ' . BASE_URL . '/user/checkout/index.php');
    exit();
}
?>

