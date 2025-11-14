<?php
$page_title = 'My Orders - NBA Shop';
include '../../config/config.php';
include '../../includes/header.php';
requireLogin();

$user_id = $_SESSION['user_id'];

$orders_stmt = $conn->prepare("SELECT o.*, ua.address_line1, ua.city, ua.state FROM orders o JOIN user_addresses ua ON o.address_id = ua.address_id WHERE o.user_id = ? ORDER BY o.order_date DESC");
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
?>

<?php include '../../includes/navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">My Orders</h2>
    
    <?php if ($orders_result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Shipping Address</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['order_id']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                            <td><?php echo htmlspecialchars($order['address_line1'] . ', ' . $order['city'] . ', ' . $order['state']); ?></td>
                            <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <?php
                                $status_class = 'badge-warning';
                                if ($order['order_status'] === 'Delivered') $status_class = 'badge-success';
                                elseif ($order['order_status'] === 'Cancelled') $status_class = 'badge-danger';
                                ?>
                                <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($order['order_status']); ?></span>
                            </td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>/user/orders.php/view.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-primary">View</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">You have no orders yet.</div>
        <a href="<?php echo BASE_URL; ?>/user/products/index.php" class="btn btn-primary">Start Shopping</a>
    <?php endif; ?>
</div>

<?php include '../../includes/foot.php'; ?>

