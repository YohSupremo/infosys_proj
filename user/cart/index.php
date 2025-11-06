<?php
$page_title = 'Shopping Cart - NBA Shop';
include '../../includes/header.php';
include '../../config/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

// Get cart
$cart_stmt = $conn->prepare("SELECT cart_id FROM shopping_cart WHERE user_id = ?");
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

$cart_items = [];
$total = 0;

if ($cart_result->num_rows > 0) {
    $cart = $cart_result->fetch_assoc();
    $cart_id = $cart['cart_id'];
    
    // Get cart items
    $items_stmt = $conn->prepare("SELECT ci.*, p.product_name, p.price, p.image_url, p.stock_quantity FROM cart_items ci JOIN products p ON ci.product_id = p.product_id WHERE ci.cart_id = ?");
    $items_stmt->bind_param("i", $cart_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    while ($item = $items_result->fetch_assoc()) {
        $item_total = $item['price'] * $item['quantity'];
        $total += $item_total;
        $cart_items[] = $item;
    }
    $items_stmt->close();
}
$cart_stmt->close();
?>

<?php include '../../includes/navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Shopping Cart</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?php 
            if ($error === 'insufficient_stock') echo 'Insufficient stock available.';
            elseif ($error === 'product_not_found') echo 'Product not found.';
            else echo 'An error occurred.';
            ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">Item added to cart successfully!</div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <?php if (count($cart_items) > 0): ?>
                <div class="card">
                    <div class="card-body">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <?php if ($item['image_url']): ?>
                                            <img src="../../<?php echo htmlspecialchars($item['image_url']); ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                        <?php else: ?>
                                            <img src="../../assets/images/placeholder.jpg" class="img-fluid rounded" alt="No image">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4">
                                        <h5><?php echo htmlspecialchars($item['product_name']); ?></h5>
                                        <p class="text-muted">₱<?php echo number_format($item['price'], 2); ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <form method="POST" action="update.php" class="d-inline">
                                            <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>" required>
                                                <button type="submit" class="btn btn-outline-primary">Update</button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-2">
                                        <p class="mb-0"><strong>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong></p>
                                    </div>
                                    <div class="col-md-1">
                                        <form method="POST" action="delete.php" class="d-inline">
                                            <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Remove this item?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">Your cart is empty.</div>
                <a href="../products/index.php" class="btn btn-primary">Continue Shopping</a>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <div class="card cart-summary">
                <div class="card-header">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>Subtotal:</span>
                        <span><strong>₱<?php echo number_format($total, 2); ?></strong></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <span><strong>Total:</strong></span>
                        <span><strong>₱<?php echo number_format($total, 2); ?></strong></span>
                    </div>
                    <?php if (count($cart_items) > 0): ?>
                        <a href="../checkout/index.php" class="btn btn-primary w-100">Proceed to Checkout</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

