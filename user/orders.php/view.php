<?php
$page_title = 'Order Details - NBA Shop';
include '../../includes/header.php';
include '../../config/config.php';
requireLogin();

$order_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$order_id) {
    header('Location: index.php');
    exit();
}

// Get order
$order_stmt = $conn->prepare("SELECT o.*, ua.* FROM orders o JOIN user_addresses ua ON o.address_id = ua.address_id WHERE o.order_id = ? AND o.user_id = ?");
$order_stmt->bind_param("ii", $order_id, $user_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$order = $order_result->fetch_assoc();
$order_stmt->close();

// Get order items
$items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
?>

<?php include '../../includes/navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Order Details #<?php echo $order['order_id']; ?></h2>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Order Items</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = $items_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td>₱<?php echo number_format($item['subtotal'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Order Date:</strong><br><?php echo date('F d, Y h:i A', strtotime($order['order_date'])); ?></p>
                    <p><strong>Status:</strong><br>
                        <?php
                        $status_class = 'badge-warning';
                        if ($order['order_status'] === 'Delivered') $status_class = 'badge-success';
                        elseif ($order['order_status'] === 'Cancelled') $status_class = 'badge-danger';
                        ?>
                        <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($order['order_status']); ?></span>
                    </p>
                    <p><strong>Payment Method:</strong><br><?php echo htmlspecialchars($order['payment_method']); ?></p>
                    
                    <hr>
                    
                    <p><strong>Shipping Address:</strong><br>
                        <?php echo htmlspecialchars($order['address_line1']); ?><br>
                        <?php if ($order['address_line2']): ?>
                            <?php echo htmlspecialchars($order['address_line2']); ?><br>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['state']); ?> <?php echo htmlspecialchars($order['postal_code']); ?><br>
                        <?php echo htmlspecialchars($order['country']); ?>
                    </p>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <span>Subtotal:</span>
                        <span>₱<?php echo number_format($order['subtotal'], 2); ?></span>
                    </div>
                    <?php if ($order['discount_amount'] > 0): ?>
                        <div class="d-flex justify-content-between">
                            <span>Discount:</span>
                            <span>-₱<?php echo number_format($order['discount_amount'], 2); ?></span>
                        </div>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Total:</strong>
                        <strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-3">
        <a href="<?php echo BASE_URL; ?>/user/orders.php/index.php" class="btn btn-outline-primary">Back to Orders</a>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

