<?php
$page_title = 'Order Details Report - Admin';
include '../../config/config.php';
include '../../includes/header.php';
requireAdmin();

$error = '';
$start_date = sanitize($_GET['start_date'] ?? '');
$end_date = sanitize($_GET['end_date'] ?? '');
$order_status = sanitize($_GET['order_status'] ?? '');
$order_id = sanitize($_GET['order_id'] ?? '');
$customer_search = sanitize($_GET['customer_search'] ?? '');
$payment_method = sanitize($_GET['payment_method'] ?? '');
$min_amount = sanitize($_GET['min_amount'] ?? '');
$max_amount = sanitize($_GET['max_amount'] ?? '');
$product_search = sanitize($_GET['product_search'] ?? '');
$city = sanitize($_GET['city'] ?? '');
$limit = intval($_GET['limit'] ?? 50);
$limit = max(10, min(500, $limit)); // Limit between 10 and 500

// Validate date formats if provided
if (!empty($_GET['start_date'])) {
    $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($datePattern, $start_date)) {
        $error = 'Start date must be in format: YYYY-MM-DD (e.g. 2025-01-01)';
        $start_date = '';
    }
}

if (!empty($_GET['end_date'])) {
    $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($datePattern, $end_date)) {
        $error = 'End date must be in format: YYYY-MM-DD (e.g. 2025-01-31)';
        $end_date = '';
    }
}

// Validate numeric fields
if (!empty($min_amount) && (!is_numeric($min_amount) || $min_amount < 0)) {
    $error = 'Minimum amount must be a valid positive number';
    $min_amount = '';
}

if (!empty($max_amount) && (!is_numeric($max_amount) || $max_amount < 0)) {
    $error = 'Maximum amount must be a valid positive number';
    $max_amount = '';
}

if (!empty($order_id) && !is_numeric($order_id)) {
    $error = 'Order ID must be a valid number';
    $order_id = '';
}

// Build query using v_order_details view
$query = "SELECT * FROM v_order_details WHERE 1=1";
$params = [];
$types = "";

// Date range filter
if (!empty($start_date) && !empty($end_date)) {
    $query .= " AND DATE(order_date) BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
} elseif (!empty($start_date)) {
    $query .= " AND DATE(order_date) >= ?";
    $params[] = $start_date;
    $types .= "s";
} elseif (!empty($end_date)) {
    $query .= " AND DATE(order_date) <= ?";
    $params[] = $end_date;
    $types .= "s";
}

// Order status filter
if (!empty($order_status)) {
    $query .= " AND order_status = ?";
    $params[] = $order_status;
    $types .= "s";
}

// Order ID filter
if (!empty($order_id)) {
    $query .= " AND order_id = ?";
    $params[] = $order_id;
    $types .= "i";
}

// Customer search (name or email)
if (!empty($customer_search)) {
    $query .= " AND (CONCAT(first_name, ' ', last_name) LIKE ? OR email LIKE ?)";
    $customer_like = "%{$customer_search}%";
    $params[] = $customer_like;
    $params[] = $customer_like;
    $types .= "ss";
}

// Payment method filter
if (!empty($payment_method)) {
    $query .= " AND payment_method = ?";
    $params[] = $payment_method;
    $types .= "s";
}

// Amount range filters (need to check unique orders, so we'll filter after grouping)
// Product search (will filter after grouping)
// City filter
if (!empty($city)) {
    $query .= " AND city LIKE ?";
    $city_like = "%{$city}%";
    $params[] = $city_like;
    $types .= "s";
}

$query .= " ORDER BY order_date DESC, order_id DESC, order_item_id ASC LIMIT ?";
$params[] = $limit * 10; // Get more records to account for multiple items per order
$types .= "i";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Group orders and their items
$orders = [];
while ($row = $result->fetch_assoc()) {
    $order_id_key = $row['order_id'];
    if (!isset($orders[$order_id_key])) {
        $orders[$order_id_key] = [
            'order_info' => [
                'order_id' => $row['order_id'],
                'user_id' => $row['user_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'],
                'address_id' => $row['address_id'],
                'address_line1' => $row['address_line1'],
                'address_line2' => $row['address_line2'],
                'city' => $row['city'],
                'state' => $row['state'],
                'postal_code' => $row['postal_code'],
                'country' => $row['country'],
                'discount_id' => $row['discount_id'],
                'discount_code' => $row['discount_code'],
                'order_status' => $row['order_status'],
                'payment_method' => $row['payment_method'],
                'subtotal' => $row['subtotal'],
                'discount_amount' => $row['discount_amount'],
                'total_amount' => $row['total_amount'],
                'order_date' => $row['order_date'],
                'updated_at' => $row['updated_at']
            ],
            'items' => []
        ];
    }
    
    // Add order item if it exists
    if ($row['order_item_id']) {
        $orders[$order_id_key]['items'][] = [
            'order_item_id' => $row['order_item_id'],
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'unit_price' => $row['unit_price'],
            'item_subtotal' => $row['item_subtotal']
        ];
    }
}

$stmt->close();

// Apply additional filters after grouping (amount range and product search)
$filtered_orders = [];
foreach ($orders as $order) {
    // Amount range filter
    if (!empty($min_amount) && $order['order_info']['total_amount'] < floatval($min_amount)) {
        continue;
    }
    if (!empty($max_amount) && $order['order_info']['total_amount'] > floatval($max_amount)) {
        continue;
    }
    
    // Product search filter
    if (!empty($product_search)) {
        $found = false;
        foreach ($order['items'] as $item) {
            if (stripos($item['product_name'], $product_search) !== false) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            continue;
        }
    }
    
    $filtered_orders[] = $order;
    
    // Limit the number of orders displayed
    if (count($filtered_orders) >= $limit) {
        break;
    }
}

$orders = $filtered_orders;

// Get unique payment methods for filter dropdown
$payment_methods_query = "SELECT DISTINCT payment_method FROM v_order_details WHERE payment_method IS NOT NULL AND payment_method != '' ORDER BY payment_method";
$payment_methods_result = $conn->query($payment_methods_query);
$available_payment_methods = [];
while ($row = $payment_methods_result->fetch_assoc()) {
    $available_payment_methods[] = $row['payment_method'];
}
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Order Details Report</h2>
    <p class="text-muted mb-4">Comprehensive order details using v_order_details view</p>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> Filter Options</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="order_id" class="form-label">Order ID</label>
                        <input type="number" class="form-control" id="order_id" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>" placeholder="Search by Order ID">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="customer_search" class="form-label">Customer (Name/Email)</label>
                        <input type="text" class="form-control" id="customer_search" name="customer_search" value="<?php echo htmlspecialchars($customer_search); ?>" placeholder="Search customer">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="product_search" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="product_search" name="product_search" value="<?php echo htmlspecialchars($product_search); ?>" placeholder="Search product">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="order_status" class="form-label">Order Status</label>
                        <select class="form-control" id="order_status" name="order_status">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?php echo $order_status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Processing" <?php echo $order_status === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="Shipped" <?php echo $order_status === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="Delivered" <?php echo $order_status === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="Cancelled" <?php echo $order_status === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-control" id="payment_method" name="payment_method">
                            <option value="">All Methods</option>
                            <?php foreach ($available_payment_methods as $pm): ?>
                                <option value="<?php echo htmlspecialchars($pm); ?>" <?php echo $payment_method === $pm ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pm); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="city" class="form-label">City</label>
                        <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($city); ?>" placeholder="Filter by city">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="min_amount" class="form-label">Min Amount (₱)</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="min_amount" name="min_amount" value="<?php echo htmlspecialchars($min_amount); ?>" placeholder="0.00">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="max_amount" class="form-label">Max Amount (₱)</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="max_amount" name="max_amount" value="<?php echo htmlspecialchars($max_amount); ?>" placeholder="0.00">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="limit" class="form-label">Results Per Page</label>
                        <select class="form-control" id="limit" name="limit">
                            <option value="25" <?php echo $limit === 25 ? 'selected' : ''; ?>>25</option>
                            <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100</option>
                            <option value="200" <?php echo $limit === 200 ? 'selected' : ''; ?>>200</option>
                            <option value="500" <?php echo $limit === 500 ? 'selected' : ''; ?>>500</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Apply Filters</button>
                            <a href="order_details_report.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Clear Filters</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Orders Summary</h5>
                <div>
                    <span class="badge bg-primary me-2">Showing: <?php echo count($orders); ?> orders</span>
                    <?php if (count($orders) >= $limit): ?>
                        <span class="badge bg-warning text-dark">Maximum results reached</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php 
            $grand_total = 0;
            $total_items = 0;
            foreach ($orders as $order) {
                $grand_total += $order['order_info']['total_amount'];
                $total_items += count($order['items']);
            }
            ?>
            <div class="row">
                <div class="col-md-3">
                    <strong>Total Revenue:</strong><br>
                    <span class="h5 text-success">₱<?php echo number_format($grand_total, 2); ?></span>
                </div>
                <div class="col-md-3">
                    <strong>Average Order Value:</strong><br>
                    <span class="h5">₱<?php echo count($orders) > 0 ? number_format($grand_total / count($orders), 2) : '0.00'; ?></span>
                </div>
                <div class="col-md-3">
                    <strong>Total Items:</strong><br>
                    <span class="h5"><?php echo number_format($total_items); ?></span>
                </div>
                <div class="col-md-3">
                    <strong>Avg Items/Order:</strong><br>
                    <span class="h5"><?php echo count($orders) > 0 ? number_format($total_items / count($orders), 1) : '0.0'; ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (count($orders) > 0): ?>
        <?php foreach ($orders as $order): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Order #<?php echo $order['order_info']['order_id']; ?></h5>
                            <small class="text-muted">Date: <?php echo date('F d, Y h:i A', strtotime($order['order_info']['order_date'])); ?></small>
                        </div>
                        <div>
                            <?php
                            $status_class = 'bg-warning text-dark';
                            if ($order['order_info']['order_status'] === 'Delivered') $status_class = 'bg-success';
                            elseif ($order['order_info']['order_status'] === 'Cancelled') $status_class = 'bg-danger';
                            elseif ($order['order_info']['order_status'] === 'Shipped') $status_class = 'bg-info';
                            elseif ($order['order_info']['order_status'] === 'Processing') $status_class = 'bg-primary';
                            ?>
                            <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($order['order_info']['order_status']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Customer Information</h6>
                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order['order_info']['first_name'] . ' ' . $order['order_info']['last_name']); ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['order_info']['email']); ?></p>
                            <p class="mb-1"><strong>User ID:</strong> <?php echo $order['order_info']['user_id']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Shipping Address</h6>
                            <p class="mb-1"><?php echo htmlspecialchars($order['order_info']['address_line1']); ?></p>
                            <?php if ($order['order_info']['address_line2']): ?>
                                <p class="mb-1"><?php echo htmlspecialchars($order['order_info']['address_line2']); ?></p>
                            <?php endif; ?>
                            <p class="mb-1"><?php echo htmlspecialchars($order['order_info']['city']); ?>, <?php echo htmlspecialchars($order['order_info']['state']); ?> <?php echo htmlspecialchars($order['order_info']['postal_code']); ?></p>
                            <p class="mb-1"><?php echo htmlspecialchars($order['order_info']['country']); ?></p>
                        </div>
                    </div>
                    
                    <h6>Order Items</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product ID</th>
                                    <th>Product Name</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($order['items']) > 0): ?>
                                    <?php foreach ($order['items'] as $item): ?>
                                        <tr>
                                            <td>#<?php echo $item['product_id']; ?></td>
                                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                            <td>₱<?php echo number_format($item['item_subtotal'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No items found for this order</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['order_info']['payment_method']); ?></p>
                            <?php if ($order['order_info']['discount_code']): ?>
                                <p class="mb-1"><strong>Discount Code:</strong> <?php echo htmlspecialchars($order['order_info']['discount_code']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 text-end">
                            <p class="mb-1"><strong>Subtotal:</strong> ₱<?php echo number_format($order['order_info']['subtotal'], 2); ?></p>
                            <?php if ($order['order_info']['discount_amount'] > 0): ?>
                                <p class="mb-1"><strong>Discount:</strong> -₱<?php echo number_format($order['order_info']['discount_amount'], 2); ?></p>
                            <?php endif; ?>
                            <p class="mb-0"><strong>Total Amount:</strong> ₱<?php echo number_format($order['order_info']['total_amount'], 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center">
                <p class="text-muted">No orders found for the selected criteria.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/foot.php'; ?>

