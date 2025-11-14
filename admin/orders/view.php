<?php
$page_title = 'Order Details - Admin';
include '../../config/config.php';
include '../../includes/header.php';
requireAdmin();

$order_id = intval($_GET['id'] ?? 0);

if (!$order_id) {
    header('Location: index.php');
    exit();
}

$order_stmt = $conn->prepare("SELECT o.*, u.first_name, u.last_name, u.email, ua.* FROM orders o JOIN users u ON o.user_id = u.user_id JOIN user_addresses ua ON o.address_id = ua.address_id WHERE o.order_id = ?");
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$order = $order_result->fetch_assoc();
$order_stmt->close();

$items_stmt = $conn->prepare("SELECT oi.*, p.image_url FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
?>

<?php include '../../includes/admin_navbar.php'; ?>

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
								<th></th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = $items_result->fetch_assoc()): ?>
                                <tr>
									<td style="width:60px">
										<?php
										$thumb = !empty($item['image_url']) ? $item['image_url'] : 'assets/images/placeholder.jpg';
										?>
										<img src="<?php echo BASE_URL . '/' . $thumb; ?>" alt="Product" style="width:50px;height:50px;object-fit:cover" class="rounded">
									</td>
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
                    <p><strong>Customer:</strong><br><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?><br><?php echo htmlspecialchars($order['email']); ?></p>
                    <p><strong>Order Date:</strong><br><?php echo date('F d, Y h:i A', strtotime($order['order_date'])); ?></p>
					<p><strong>Status:</strong><br>
						<?php
						$status_class = 'bg-warning text-dark';
						if ($order['order_status'] === 'Delivered') $status_class = 'bg-success';
						elseif ($order['order_status'] === 'Cancelled') $status_class = 'bg-danger';
						?>
						<span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($order['order_status']); ?></span>
                    </p>
                    <p><strong>Payment Method:</strong><br><?php echo htmlspecialchars($order['payment_method']); ?></p>
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
        <a href="index.php" class="btn btn-outline-primary">Back to Orders</a>
        <a href="update_status.php?id=<?php echo $order['order_id']; ?>" class="btn btn-primary">Update Status</a>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

