<?php
$page_title = 'Checkout - NBA Shop';
include '../../includes/header.php';
include '../../config/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$error = '';

// Get cart
$cart_stmt = $conn->prepare("SELECT cart_id FROM shopping_cart WHERE user_id = ?");
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

$cart_items = [];
$subtotal = 0;

if ($cart_result->num_rows > 0) {
    $cart = $cart_result->fetch_assoc();
    $cart_id = $cart['cart_id'];
    
    $items_stmt = $conn->prepare("SELECT ci.*, p.product_name, p.price, p.stock_quantity FROM cart_items ci JOIN products p ON ci.product_id = p.product_id WHERE ci.cart_id = ?");
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

// Get addresses
$addresses_stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$addresses_stmt->bind_param("i", $user_id);
$addresses_stmt->execute();
$addresses_result = $addresses_stmt->get_result();

// Get discount codes
$discounts_stmt = $conn->query("SELECT * FROM discount_codes WHERE is_active = 1 AND (expiration_date IS NULL OR expiration_date > NOW()) AND (usage_limit IS NULL OR times_used < usage_limit) ORDER BY code");
?>

<?php include '../../includes/navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Checkout</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <a href="../cart/index.php" class="btn btn-primary">Back to Cart</a>
    <?php elseif (count($cart_items) == 0): ?>
        <div class="alert alert-info">Your cart is empty.</div>
        <a href="../products/index.php" class="btn btn-primary">Continue Shopping</a>
    <?php else: ?>
        <form method="POST" action="place_order.php">
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
                                        <input class="form-check-input" type="radio" name="address_id" id="address_<?php echo $address['address_id']; ?>" value="<?php echo $address['address_id']; ?>" <?php echo $address['is_default'] ? 'checked' : ''; ?> required>
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
                                <a href="../account/add_address.php" class="btn btn-outline-primary btn-sm">Add New Address</a>
                            <?php else: ?>
                                <div class="alert alert-warning">No addresses found. Please add a shipping address.</div>
                                <a href="../account/add_address.php" class="btn btn-primary">Add Address</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Payment Method</h5>
                        </div>
                        <div class="card-body">
                            <select class="form-select" name="payment_method" required>
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
                            <input type="text" class="form-control" name="discount_code" placeholder="Enter discount code">
                        </div>
                    </div>
                </div>
                
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
                                <span id="discount_amount">₱0.00</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <span><strong>Total:</strong></span>
                                <span><strong id="total_amount">₱<?php echo number_format($subtotal, 2); ?></strong></span>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" <?php echo $addresses_result->num_rows == 0 ? 'disabled' : ''; ?>>Place Order</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include '../../includes/foot.php'; ?>

