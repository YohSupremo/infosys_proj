<?php
$page_title = 'Order Details - NBA Shop';
include '../../config/config.php';
include '../../includes/header.php';
requireLogin();

$order_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$order_id) {
    header('Location: index.php');
    exit();
}

// Fetch order and item details using view plus product image
$details_stmt = $conn->prepare("SELECT v.*, p.image_url 
    FROM v_order_details v 
    LEFT JOIN products p ON v.product_id = p.product_id 
    WHERE v.order_id = ? AND v.user_id = ?");
$details_stmt->bind_param("ii", $order_id, $user_id);
$details_stmt->execute();
$details_result = $details_stmt->get_result();

if ($details_result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

// First row holds order-level info
$rows = [];
while ($row = $details_result->fetch_assoc()) {
    $rows[] = $row;
}
$details_stmt->close();

$order = $rows[0];

// Items come from rows that have order_item_id
$items = [];
foreach ($rows as $row) {
    if (!empty($row['order_item_id'])) {
        $items[] = $row;
    }
}

// Check which products can be reviewed (only if order is Delivered)
$can_review_products = [];
if ($order['order_status'] === 'Delivered') {
    foreach ($items as $item) {
        // Check if review already exists for this product and order
        $review_check = $conn->prepare("SELECT review_id FROM product_reviews WHERE user_id = ? AND product_id = ? AND order_id = ?");
        $review_check->bind_param("iii", $user_id, $item['product_id'], $order_id);
        $review_check->execute();
        $review_result = $review_check->get_result();
        
        $has_review = $review_result->num_rows > 0;
        $review_id = $has_review ? $review_result->fetch_assoc()['review_id'] : null;
        
        $can_review_products[$item['product_id']] = [
            'can_review' => true,
            'has_review' => $has_review,
            'review_id' => $review_id
        ];
        
        $review_check->close();
    }
}
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
								<th></th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
									<td style="width:60px">
										<?php
										$thumb = !empty($item['image_url']) ? $item['image_url'] : 'assets/images/placeholder.jpg';
										?>
										<img src="<?php echo BASE_URL . '/' . $thumb; ?>" alt="Product" style="width:50px;height:50px;object-fit:cover" class="rounded">
									</td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                            <br>
                                            <a href="<?php echo BASE_URL; ?>/user/products/view.php?id=<?php echo $item['product_id']; ?>" class="btn btn-sm btn-outline-primary mt-1">View Product</a>
                                        </div>
                                    </td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td>
                                        <div>₱<?php echo number_format($item['subtotal'], 2); ?></div>
                                        <?php if ($order['order_status'] === 'Delivered' && isset($can_review_products[$item['product_id']])): ?>
                                            <?php if ($can_review_products[$item['product_id']]['has_review']): ?>
                                                <a href="<?php echo BASE_URL; ?>/user/reviews/edit.php?id=<?php echo $can_review_products[$item['product_id']]['review_id']; ?>" class="btn btn-sm btn-success mt-2">
                                                    <i class="bi bi-star-fill"></i> Edit Review
                                                </a>
                                            <?php else: ?>
                                                <a href="<?php echo BASE_URL; ?>/user/reviews/create.php?product_id=<?php echo $item['product_id']; ?>&order_id=<?php echo $order_id; ?>" class="btn btn-sm btn-primary mt-2">
                                                    <i class="bi bi-star"></i> Write Review
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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
						$status_class = 'bg-warning text-dark';
						if ($order['order_status'] === 'Delivered') $status_class = 'bg-success';
						elseif ($order['order_status'] === 'Cancelled') $status_class = 'bg-danger';
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

