<?php
$page_title = 'Checkout - NBA Shop';
include '../../includes/header.php';
include '../../config/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$error = '';
$discount_message = '';
$discount_amount = 0;

// ========================
// FETCH CART
// ========================
$cart_stmt = $conn->prepare("SELECT cart_id FROM shopping_cart WHERE user_id = ?");
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

$cart_items = [];
$subtotal = 0;

if ($cart_result->num_rows > 0) {
    $cart = $cart_result->fetch_assoc();
    $cart_id = $cart['cart_id'];
    
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
        if ($item['quantity'] > $item['stock_quantity']) {
            $error = "Insufficient stock for {$item['product_name']}";
            break;
        }
        $item_total = $item['price'] * $item['quantity'];
        $subtotal += $item_total;
        $cart_items[] = $item;
    }
    $items_stmt->close();
} else {
    $error = "Your cart is empty.";
}
$cart_stmt->close();

// ========================
// FETCH ADDRESSES
// ========================
$addresses_stmt = $conn->prepare("
    SELECT * FROM user_addresses 
    WHERE user_id = ? 
    ORDER BY is_default DESC, created_at DESC
");
$addresses_stmt->bind_param("i", $user_id);
$addresses_stmt->execute();
$addresses_result = $addresses_stmt->get_result();

// ========================
// DISCOUNT CODE LOGIC
// ========================

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

            if ($discount_type == 'fixed_amount') {
                $discount_amount = $discount_value;
            } elseif ($discount_type == 'percentage') {
                $discount_amount = $subtotal * ($discount_value / 100);
            }

            $_SESSION['discount_value'] = $discount_amount;
            $_SESSION['discount_type'] = $discount_type;
            $discount_message = "Discount applied: {$discount_value}" . ($discount_type == 'percentage' ? "%" : " PHP");
        } else {
            unset($_SESSION['discount_code'], $_SESSION['discount_value'], $_SESSION['discount_type']);
            $discount_message = "Invalid or expired discount code.";
        }

        $discounts_stmt->close();
    }
}

// Apply any stored discount
if (!empty($_SESSION['discount_value'])) {
    $discount_amount = $_SESSION['discount_value'];
    $subtotal -= $discount_amount;
    if ($subtotal < 0) $subtotal = 0;
}

// ========================
// HANDLE PLACE ORDER
// ========================
if (isset($_POST['order_placed'])) {
    // Server-side validation
    $address_id = intval($_POST['address_id'] ?? 0);
    $payment_method = sanitize($_POST['payment_method'] ?? '');
    
    if (empty($address_id) || empty($payment_method)) {
        $error = "Please select a shipping address and payment method.";
    } else {
        // Get discount code from session or form
        $discount_code = sanitize($_POST['discount_code'] ?? $_SESSION['discount_code'] ?? '');
        
        // Redirect to place_order.php with POST data via form submission
        // We'll use a hidden form to submit the data
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
                                <span><strong>₱<?php echo number_format($subtotal + $discount_amount, 2); ?></strong></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Discount:</span>
                                <span id="discount_amount">₱<?php echo number_format($discount_amount, 2); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <span><strong>Total:</strong></span>
                                <span><strong id="total_amount">₱<?php echo number_format($subtotal, 2); ?></strong></span>
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
