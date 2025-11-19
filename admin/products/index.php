<?php
$page_title = 'Products - Admin';
include '../../config/config.php';
include '../../includes/header.php';
requireAdminOrInventoryManager();
$is_admin = hasRole('Admin');
$is_inventory_manager = hasRole('Inventory Manager');
// main UI
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);

$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? 'active';
$allowed_product_statuses = ['active', 'inactive', 'all'];
if (!in_array($status_filter, $allowed_product_statuses, true)) {
    $status_filter = 'active';
}

$current_products_url = BASE_URL . '/admin/products/index.php';
$product_query_string = $_SERVER['QUERY_STRING'] ?? '';
if (!empty($product_query_string)) {
    $current_products_url .= '?' . $product_query_string;
}

$product_query = "SELECT p.*, t.team_name FROM products p LEFT JOIN nba_teams t ON p.team_id = t.team_id WHERE 1=1";
$product_types = '';
$product_params = [];
if ($status_filter === 'active') {
    $product_query .= " AND p.is_active = 1";
} elseif ($status_filter === 'inactive') {
    $product_query .= " AND p.is_active = 0";
}

if ($search !== '') {
    $product_query .= " AND (p.product_name LIKE ? OR CAST(p.product_id AS CHAR) LIKE ?)";
    $like = '%' . $search . '%';
    $product_types .= 'ss';
    $product_params[] = $like;
    $product_params[] = $like;
}

$product_query .= " ORDER BY p.created_at DESC";

$product_stmt = $conn->prepare($product_query);
if ($product_types !== '') {
    $product_stmt->bind_param($product_types, ...$product_params);
}
$product_stmt->execute();
$products = $product_stmt->get_result();
?>

<?php
if ($is_inventory_manager && !$is_admin) {
    include '../../includes/inventory_navbar.php';
} else {
    include '../../includes/admin_navbar.php';
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Products</h2>
        <?php if ($is_admin): ?>
            <a href="create.php" class="btn btn-primary">Add New Product</a>
        <?php endif; ?>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end mb-4">
                <div class="col-md-6">
                    <label for="productSearch" class="form-label">Search Products</label>
                    <input type="text" id="productSearch" name="search" class="form-control" placeholder="Search by Product Name or ID" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label for="productStatus" class="form-label">Status</label>
                    <select id="productStatus" name="status" class="form-select">
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Show All</option>
                    </select>
                </div>
                <div class="col-md-3 text-md-end">
                    <button type="submit" class="btn btn-primary mt-4 me-2">Apply</button>
                    <a href="index.php" class="btn btn-outline-secondary mt-4">Reset</a>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Team</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($products->num_rows > 0): ?>
                            <?php while ($product = $products->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $product['product_id']; ?></td>
                                    <td>
                                        <?php if ($product['image_url']): ?>
                                            <img src="../../<?php echo htmlspecialchars($product['image_url']); ?>" style="width: 50px; height: 50px; object-fit: cover;" class="rounded">
                                        <?php else: ?>
                                            <span class="text-muted">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo ($product['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['team_name'] ?: 'N/A'); ?></td>
                                    <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                    <td><?php echo $product['stock_quantity']; ?></td>
                                    <td>
                                        <?php if ($product['is_active']): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($is_admin): ?>
                                            <a href="edit.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <?php endif; ?>
                                        <?php if ($product['is_active']): ?>
                                            <form method="POST" action="delete.php" class="d-inline">
                                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($current_products_url); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Deactivate this product?')">Deactivate</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="reactivate.php" class="d-inline">
                                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($current_products_url); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Reactivate this product?')">Reactivate</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No products found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
if (isset($product_stmt) && $product_stmt instanceof mysqli_stmt) {
    $product_stmt->close();
}
?>

<?php include '../../includes/foot.php'; ?>

