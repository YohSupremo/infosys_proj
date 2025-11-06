<?php
$page_title = 'Orders - Admin';
include '../../includes/header.php';
include '../../config/config.php';
requireAdmin();

$orders = $conn->query("SELECT o.*, u.first_name, u.last_name FROM orders o JOIN users u ON o.user_id = u.user_id ORDER BY o.order_date DESC");
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Orders</h2>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders->num_rows > 0): ?>
                            <?php while ($order = $orders->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <?php
                                        $status_class = 'badge-warning';
                                        if ($order['order_status'] === 'Delivered') $status_class = 'badge-success';
                                        elseif ($order['order_status'] === 'Cancelled') $status_class = 'badge-danger';
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($order['order_status']); ?></span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <a href="view.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                        <a href="update_status.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-primary">Update Status</a>
                                        <form method="POST" action="delete.php" class="d-inline">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this order?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No orders found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

