<?php
$page_title = 'Orders - Admin';
include '../../config/config.php';
include '../../includes/header.php';

requireAdmin();

$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? 'all';

$default_statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Completed', 'Cancelled'];
$status_options = $default_statuses;
$status_result = $conn->query("SELECT DISTINCT order_status FROM orders ORDER BY order_status");
if ($status_result) {
    $dynamic_statuses = [];
    while ($row = $status_result->fetch_assoc()) {
        if (!empty($row['order_status'])) {
            $dynamic_statuses[] = $row['order_status'];
        }
    }
    if (!empty($dynamic_statuses)) {
        $status_options = $dynamic_statuses;
    }
    $status_result->free();
}
$status_options = array_unique($status_options);

if ($status_filter !== 'all' && !in_array($status_filter, $status_options, true)) {
    $status_filter = 'all';
}

$order_query = "SELECT o.*, u.first_name, u.last_name FROM orders o JOIN users u ON o.user_id = u.user_id WHERE 1=1";
$order_types = '';
$order_params = [];

if ($status_filter !== 'all') {
    $order_query .= " AND o.order_status = ?";
    $order_types .= 's';
    $order_params[] = $status_filter;
}

if ($search !== '') {
    $order_query .= " AND (CAST(o.order_id AS CHAR) LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
    $like = '%' . $search . '%';
    $order_types .= 'ss';
    $order_params[] = $like;
    $order_params[] = $like;
}

$order_query .= " ORDER BY o.order_date DESC";
$order_stmt = $conn->prepare($order_query);
if ($order_types !== '') {
    $order_stmt->bind_param($order_types, ...$order_params);
}
$order_stmt->execute();
$orders = $order_stmt->get_result();
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Orders</h2>
    
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end mb-4">
                <div class="col-md-6">
                    <label for="orderSearch" class="form-label">Search Orders</label>
                    <input type="text" class="form-control" id="orderSearch" name="search" placeholder="Search by Order ID or Customer" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label for="orderStatus" class="form-label">Status</label>
                    <select id="orderStatus" name="status" class="form-select">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Show All</option>
                        <?php foreach ($status_options as $option): ?>
                            <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $status_filter === $option ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 text-md-end">
                    <button type="submit" class="btn btn-primary me-2">Apply</button>
                    <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
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
										$status_class = 'bg-warning text-dark';
										if ($order['order_status'] === 'Delivered') $status_class = 'bg-success';
										elseif ($order['order_status'] === 'Cancelled') $status_class = 'bg-danger';
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

<?php
if (isset($order_stmt) && $order_stmt instanceof mysqli_stmt) {
    $order_stmt->close();
}
?>

<?php include '../../includes/foot.php'; ?>

