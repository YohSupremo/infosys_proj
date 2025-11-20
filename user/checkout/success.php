<?php
$page_title = 'Order Placed - NBA Shop';
include '../../config/config.php';
include '../../includes/header.php';
requireLogin();

$order_id = intval($_GET['order_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$order_id) {
    header('Location: ../orders.php/index.php');
    exit();
}

$order_stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$order_stmt->bind_param("ii", $order_id, $user_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows === 0) {
    header('Location: ../orders.php/index.php');
    exit();
}

$order = $order_result->fetch_assoc();
$order_stmt->close();
?>

<?php include '../../includes/navbar.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    <h2 class="mt-3">Order Placed Successfully!</h2>
                    <p class="text-muted">Thank you for your purchase.</p>
                    
                    <div class="mt-4">
                        <p><strong>Order ID:</strong> #<?php echo $order['order_id']; ?></p>
                        <p><strong>Total Amount:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?></p>
                        <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                        <p><strong>Order Status:</strong> <span class="badge badge-warning"><?php echo htmlspecialchars($order['order_status']); ?></span></p>
                    </div>
                    
                    <div class="mt-4">
                        <a href="<?php echo BASE_URL; ?>/user/orders.php/view.php?id=<?php echo $order['order_id']; ?>" class="btn btn-primary">View Order Details</a>
                        <a href="<?php echo BASE_URL; ?>/user/products/index.php" class="btn btn-outline-primary">Continue Shopping</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

