<?php
$page_title = 'Checkout - NBA Shop';
include '../../config/config.php';
include '../../includes/header.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$error = '';
$discount_message = '';
$discount_amount = 0;

// fetch lahat ng laman ng nasa cart
$cart_stmt = $conn->prepare("SELECT cart_id FROM shopping_cart WHERE user_id = ?");
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

$cart_items = []; //array para sa laman ng cart, to be used later
$subtotal = 0;

if ($cart_result->num_rows > 0) {
    $cart = $cart_result->fetch_assoc();
    $cart_id = $cart['cart_id'];
    
    //fetch yung mga products na naka-anchor sa cart id
    $items_stmt = $conn->prepare("
        SELECT ci.*, p.product_name, p.price, p.stock_quantity 
        FROM cart_items ci 
        JOIN products p ON ci.product_id = p.product_id 
        WHERE ci.cart_id = ?
    ");
    $items_stmt->bind_param("i", $cart_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    while ($item = $items_result->fetch_assoc()) {
        //check yung quantity if valid
        if ($item['quantity'] > $item['stock_quantity']) {
            $error = "Insufficient stock for {$item['product_name']}";
            break;
        }
        //para makuha yung total as a whole
        $item_total = $item['price'] * $item['quantity'];
        $subtotal += $item_total;
        $cart_items[] = $item; //ilalagay yung item sa array, lahat ng finetch
    }
    $items_stmt->close();
} else {
    $error = "Your cart is empty.";
}
$cart_stmt->close();

// fetch addresses
$addresses_stmt = $conn->prepare("
    SELECT * FROM user_addresses 
    WHERE user_id = ? 
    ORDER BY is_default DESC, created_at DESC
");
$addresses_stmt->bind_param("i", $user_id);
$addresses_stmt->execute();
$addresses_result = $addresses_stmt->get_result();

//discount code logic 
// Handle "Apply Discount"
if (isset($_POST['apply_discount'])) {
    $entered_code = trim($_POST['discount_code']);
    $_SESSION['discount_code'] = $entered_code;

    if ($entered_code !== '') {
        $discounts_stmt = $conn->prepare("
            SELECT * FROM discount_codes 
            WHERE is_active = 1 
            AND (expiration_date IS NULL OR expiration_date > NOW()) 
            AND (usage_limit IS NULL OR times_used < usage_limit)
            AND code = ?
            LIMIT 1
        ");
        $discounts_stmt->bind_param("s", $entered_code);
        $discounts_stmt->execute();
        $result = $discounts_stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $discount = $result->fetch_assoc();
            $discount_type = $discount['discount_type'];
            $discount_value = $discount['discount_value'];
            $applies_to = $discount['applies_to'];
            
            // Calculate eligible subtotal based on applies_to
            $eligible_subtotal = 0;
            
            if ($applies_to === 'all') {
                // Discount applies to entire cart
                $eligible_subtotal = $subtotal;
            } elseif ($applies_to === 'specific_products') {
                // Get product IDs that this discount applies to
                $prod_disc_stmt = $conn->prepare("SELECT product_id FROM discount_products WHERE discount_id = ?");
                $prod_disc_stmt->bind_param("i", $discount['discount_id']);
                $prod_disc_stmt->execute();
                $prod_disc_result = $prod_disc_stmt->get_result();
                $discount_product_ids = [];
                while ($row = $prod_disc_result->fetch_assoc()) {
                    $discount_product_ids[] = intval($row['product_id']);
                }
                $prod_disc_stmt->close();
                
                // If no products are specified, discount doesn't apply
                if (empty($discount_product_ids)) {
                    $eligible_subtotal = 0;
                } else {
                    // Calculate subtotal only for matching products
                    foreach ($cart_items as $item) {
                        if (in_array(intval($item['product_id']), $discount_product_ids)) {
                            $eligible_subtotal += $item['price'] * $item['quantity'];
                        }
                    }
                }
            } elseif ($applies_to === 'specific_categories') {
                // Get category IDs that this discount applies to
                $cat_disc_stmt = $conn->prepare("SELECT category_id FROM discount_categories WHERE discount_id = ?");
                $cat_disc_stmt->bind_param("i", $discount['discount_id']);
                $cat_disc_stmt->execute();
                $cat_disc_result = $cat_disc_stmt->get_result();
                $discount_category_ids = [];
                while ($row = $cat_disc_result->fetch_assoc()) {
                    $discount_category_ids[] = intval($row['category_id']);
                }
                $cat_disc_stmt->close();
                
                // If no categories are specified, discount doesn't apply
                if (empty($discount_category_ids)) {
                    $eligible_subtotal = 0;
                } else {
                    // Get product categories for cart items
                    foreach ($cart_items as $item) {
                        $prod_cat_stmt = $conn->prepare("SELECT category_id FROM product_categories WHERE product_id = ?");
                        $prod_cat_stmt->bind_param("i", $item['product_id']);
                        $prod_cat_stmt->execute();
                        $prod_cat_result = $prod_cat_stmt->get_result();
                        $item_categories = [];
                        while ($row = $prod_cat_result->fetch_assoc()) {
                            $item_categories[] = intval($row['category_id']);
                        }
                        $prod_cat_stmt->close();
                        
                        // Check if any category matches
                        $has_match = false;
                        foreach ($item_categories as $cat_id) {
                            if (in_array($cat_id, $discount_category_ids)) {
                                $has_match = true;
                                break;
                            }
                        }
                        
                        if ($has_match) {
                            $eligible_subtotal += $item['price'] * $item['quantity'];
                        }
                    }
                }
            }
            
            // Check if there are eligible items for specific product/category discounts
            if ($applies_to !== 'all' && $eligible_subtotal == 0) {
                unset($_SESSION['discount_code'], $_SESSION['discount_value'], $_SESSION['discount_type'], $_SESSION['discount_id']);
                $discount_message = "This discount code does not apply to any items in your cart.";
            }
            // Check minimum purchase amount
            elseif ($discount['min_purchase_amount'] && $eligible_subtotal < $discount['min_purchase_amount']) {
                unset($_SESSION['discount_code'], $_SESSION['discount_value'], $_SESSION['discount_type'], $_SESSION['discount_id']);
                $discount_message = "Minimum purchase amount of ₱" . number_format($discount['min_purchase_amount'], 2) . " required for this discount.";
            } else {
                // Calculate discount based on eligible subtotal
                if ($discount_type == 'fixed_amount') {
                    $discount_amount = $discount_value;
                } elseif ($discount_type == 'percentage') {
                    $discount_amount = $eligible_subtotal * ($discount_value / 100);
                    if ($discount['max_discount_amount'] && $discount_amount > $discount['max_discount_amount']) {
                        $discount_amount = $discount['max_discount_amount'];
                    }
                }
                
                $_SESSION['discount_value'] = $discount_amount;
                $_SESSION['discount_type'] = $discount_type;
                $_SESSION['discount_id'] = $discount['discount_id'];
                $_SESSION['eligible_subtotal'] = $eligible_subtotal; // Store for reference
                $discount_message = "Discount applied: {$discount_value}" . ($discount_type == 'percentage' ? "%" : " PHP");
                if ($applies_to !== 'all') {
                    $discount_message .= " (applies to eligible items only)";
                }
            }
        } else {
            unset($_SESSION['discount_code'], $_SESSION['discount_value'], $_SESSION['discount_type'], $_SESSION['discount_id']);
            $discount_message = "Invalid or expired discount code.";
        }

        $discounts_stmt->close();
    }
}

// Apply any stored discount
if (!empty($_SESSION['discount_value'])) {
    $discount_amount = $_SESSION['discount_value'];
}

//for place order
if (isset($_POST['order_placed'])) {
    // Server-side validation
    $address_id = intval($_POST['address_id'] ?? 0);
    $payment_method = sanitize($_POST['payment_method'] ?? '');
    
    if (empty($address_id) || empty($payment_method)) {
        $error = "Please select a shipping address and payment method.";
    } else {
        // Get discount code from session or form
        $discount_code = sanitize($_POST['discount_code'] ?? $_SESSION['discount_code'] ?? '');
        
        // redirect to place_order.php with POST data via form submission
        // we'll use a hidden form to submit the data
        // i-send sa place_order.php
        $_SESSION['checkout_data'] = [
            'address_id' => $address_id,
            'payment_method' => $payment_method,
            'discount_code' => $discount_code
        ];
        header("Location: " . BASE_URL . "/user/checkout/place_order.php");
        exit();
    }
}

include '../../includes/navbar.php';
?>

<div class="container my-5">
    <h2 class="mb-4">Checkout</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <a href="<?php echo BASE_URL; ?>/user/cart/index.php" class="btn btn-primary">Back to Cart</a>
    <?php elseif (count($cart_items) == 0): ?>
        <div class="alert alert-info">Your cart is empty.</div>
        <a href="<?php echo BASE_URL; ?>/user/products/index.php" class="btn btn-primary">Continue Shopping</a>
    <?php else: ?>
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-8">
                    <!-- Shipping Address -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Shipping Address</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($addresses_result->num_rows > 0): ?>
                                <?php while ($address = $addresses_result->fetch_assoc()): ?>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="address_id" id="address_<?php echo $address['address_id']; ?>" value="<?php echo $address['address_id']; ?>" <?php echo $address['is_default'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="address_<?php echo $address['address_id']; ?>">
                                            <strong><?php echo htmlspecialchars($address['address_line1']); ?></strong><br>
                                            <?php if ($address['address_line2']): ?>
                                                <?php echo htmlspecialchars($address['address_line2']); ?><br>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?> <?php echo htmlspecialchars($address['postal_code']); ?><br>
                                            <?php echo htmlspecialchars($address['country']); ?>
                                        </label>
                                    </div>
                                <?php endwhile; ?>
                                <a href="<?php echo BASE_URL; ?>/user/account/add_address.php" class="btn btn-outline-primary btn-sm">Add New Address</a>
                            <?php else: ?>
                                <div class="alert alert-warning">No addresses found. Please add a shipping address.</div>
                                <a href="<?php echo BASE_URL; ?>/user/account/add_address.php" class="btn btn-primary">Add Address</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Payment Method</h5>
                        </div>
                        <div class="card-body">
                            <select class="form-select" name="payment_method">
                                <option value="Cash on Delivery" selected>Cash on Delivery</option>
                                <option value="GCash">GCash</option>
                                <option value="PayPal">PayPal</option>
                                <option value="Credit Card">Credit Card</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Discount Code -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Discount Code (Optional)</h5>
                        </div>
                        <div class="card-body">
                            <div class="input-group">
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    name="discount_code" 
                                    placeholder="Enter discount code"
                                    value="<?php echo htmlspecialchars($_SESSION['discount_code'] ?? ''); ?>"
                                >
                                <button type="submit" name="apply_discount" class="btn btn-outline-primary">Apply</button>
                            </div>
                            <?php if (!empty($discount_message)): ?>
                                <div class="mt-2 alert alert-info"><?php echo $discount_message; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- ORDER SUMMARY -->
                <div class="col-md-4">
                    <div class="card cart-summary">
                        <div class="card-header">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?php echo htmlspecialchars($item['product_name']); ?> x<?php echo $item['quantity']; ?></span>
                                    <span>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Subtotal:</span>
                                <span><strong>₱<?php echo number_format($subtotal, 2); ?></strong></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Discount:</span>
                                <span id="discount_amount">-₱<?php echo number_format($discount_amount, 2); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <span><strong>Total:</strong></span>
                                <span><strong id="total_amount">₱<?php echo number_format(max(0, $subtotal - $discount_amount), 2); ?></strong></span>
                            </div>
                            <button type="submit" name="order_placed" class="btn btn-primary w-100" <?php echo $addresses_result->num_rows == 0 ? 'disabled' : ''; ?>>Place Order</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include '../../includes/foot.php'; ?>
