<?php
$page_title = 'Admin Dashboard - NBA Shop';
include '../config/config.php';
include '../includes/header.php';

requireAdmin();

// Get statistics
$stats = [];

// Total products
$result = $conn->query("SELECT COUNT(*) as count FROM products");
$stats['products'] = $result->fetch_assoc()['count'];

// Total orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
$stats['orders'] = $result->fetch_assoc()['count'];

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['users'] = $result->fetch_assoc()['count'];

// Total revenue
// Cash payments are counted only when Delivered.
// Non-cash payments are counted when not Cancelled.
$result = $conn->query("
    SELECT SUM(total_amount) AS total 
    FROM orders 
    WHERE 
        (payment_method LIKE 'Cash%' AND order_status = 'Delivered')
        OR
        (payment_method NOT LIKE 'Cash%' AND order_status != 'Cancelled')
");
$row = $result->fetch_assoc();
$revenue = $row && $row['total'] ? $row['total'] : 0;
$stats['revenue'] = $revenue;

// Pending orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'Pending'");
$stats['pending_orders'] = $result->fetch_assoc()['count'];

// Low stock products
$result = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity < 10 AND is_active = 1");
$stats['low_stock'] = $result->fetch_assoc()['count'];

// Recent orders
$recent_orders = $conn->query("SELECT o.*, u.first_name, u.last_name FROM orders o JOIN users u ON o.user_id = u.user_id ORDER BY o.order_date DESC LIMIT 5");

?>

<?php include '../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Dashboard</h2>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="number"><?php echo $stats['products']; ?></div>
                    <div class="label">Total Products</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="number"><?php echo $stats['orders']; ?></div>
                    <div class="label">Total Orders</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="number"><?php echo $stats['users']; ?></div>
                    <div class="label">Total Users</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="number">₱<?php echo number_format($stats['revenue'], 2); ?></div>
                    <div class="label">Total Revenue</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="number"><?php echo $stats['pending_orders']; ?></div>
                    <div class="label">Pending Orders</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="number"><?php echo $stats['low_stock']; ?></div>
                    <div class="label">Low Stock Products</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Orders -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Recent Orders</h5>
        </div>
        <div class="card-body">
            <?php if ($recent_orders->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $recent_orders->fetch_assoc()): ?>
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
                                        <a href="orders/view.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>

                           
                            <?php endwhile; ?>
                             
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No recent orders.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/foot.php'; ?>

