<?php
$page_title = 'Inventory - Admin';
include '../../config/config.php';
include '../../includes/header.php';
requireAdminOrInventoryManager();
// main ui
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? 'active';
$allowed_statuses = ['active', 'inactive', 'all'];
if (!in_array($status_filter, $allowed_statuses, true)) {
    $status_filter = 'active';
}

$current_inventory_url = BASE_URL . '/admin/inventory/index.php';
$query_string = $_SERVER['QUERY_STRING'] ?? '';
if (!empty($query_string)) {
    $current_inventory_url .= '?' . $query_string;
}

$inventory_query = "SELECT p.*, t.team_name FROM products p LEFT JOIN nba_teams t ON p.team_id = t.team_id WHERE 1=1";
if ($status_filter === 'active') {
    $inventory_query .= " AND p.is_active = 1";
} elseif ($status_filter === 'inactive') {
    $inventory_query .= " AND p.is_active = 0";
}

if ($search !== '') {
    $inventory_query .= " AND (p.product_name LIKE ? OR CAST(p.product_id AS CHAR) LIKE ?)";
}

$inventory_query .= " ORDER BY p.stock_quantity ASC, p.product_name";

$inventory_stmt = $conn->prepare($inventory_query);
if ($search !== '') {
    $like = '%' . $search . '%';
    $inventory_stmt->bind_param('ss', $like, $like);
}
$inventory_stmt->execute();
$products = $inventory_stmt->get_result();
?>

<?php
if (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Inventory Manager') {
	include '../../includes/inventory_navbar.php';
} else {
	include '../../includes/admin_navbar.php';
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Inventory</h2>
        <div class="d-flex gap-2">
            <a href="<?php echo BASE_URL; ?>/admin/inventory/restock.php" class="btn btn-primary">Restock Products</a>
            <a href="<?php echo BASE_URL; ?>/admin/inventory/adjust_stock.php" class="btn btn-outline-primary">Adjust Stock</a>
            <a href="<?php echo BASE_URL; ?>/admin/inventory/history.php" class="btn btn-outline-secondary">View History</a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end mb-4">
                <div class="col-md-6">
                    <label for="inventorySearch" class="form-label">Search Inventory</label>
                    <input type="text" id="inventorySearch" name="search" class="form-control" placeholder="Search by Product Name or ID" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label for="inventoryStatus" class="form-label">Status</label>
                    <select id="inventoryStatus" name="status" class="form-select">
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Show All</option>
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
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>Team</th>
                            <th>Stock Quantity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($products->num_rows > 0): ?>
                            <?php while ($product = $products->fetch_assoc()): ?>
                                <tr class="<?php echo $product['stock_quantity'] < 10 ? 'table-warning' : ''; ?>">
                                    <td><?php echo $product['product_id']; ?></td>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['team_name'] ?: 'N/A'); ?></td>
                                    <td>
                                        <strong><?php echo $product['stock_quantity']; ?></strong>
                                        <?php if ($product['stock_quantity'] < 10): ?>
                                            <span class="badge badge-warning">Low Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($product['stock_quantity'] == 0): ?>
                                            <span class="badge badge-danger">Out of Stock</span>
                                        <?php elseif ($product['stock_quantity'] < 10): ?>
                                            <span class="badge badge-warning">Low Stock</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">In Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group mb-2" role="group">
                                            <a href="<?php echo BASE_URL; ?>/admin/inventory/restock.php?product_id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-primary">Restock</a>
                                            <a href="<?php echo BASE_URL; ?>/admin/inventory/adjust_stock.php?product_id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-warning">Adjust</a>
                                        </div>
                                        <?php if ($product['is_active']): ?>
                                            <form method="POST" action="<?php echo BASE_URL; ?>/admin/products/delete.php" class="d-inline">
                                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($current_inventory_url); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Deactivate this product?')">Deactivate</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="<?php echo BASE_URL; ?>/admin/products/reactivate.php" class="d-inline">
                                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($current_inventory_url); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Reactivate this product?')">Reactivate</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No products found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
if (isset($inventory_stmt) && $inventory_stmt instanceof mysqli_stmt) {
    $inventory_stmt->close();
}
?>

<?php include '../../includes/foot.php'; ?>

