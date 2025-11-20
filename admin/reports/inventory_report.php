<?php
$page_title = 'Inventory Report - Admin';
include '../../config/config.php';
include '../../includes/header.php';
requireAdminOrInventoryManager();
$products = $conn->query("SELECT p.*, t.team_name, 
    (SELECT SUM(quantity_change) FROM inventory_history WHERE product_id = p.product_id AND transaction_type = 'restock') as total_restocked,
    (SELECT SUM(ABS(quantity_change)) FROM inventory_history WHERE product_id = p.product_id AND transaction_type = 'sale') as total_sold
    FROM products p 
    LEFT JOIN nba_teams t ON p.team_id = t.team_id 
    WHERE p.is_active = 1 
    ORDER BY p.product_name");
?>

<?php
if (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Inventory Manager') {
	include '../../includes/inventory_navbar.php';
} else {
	include '../../includes/admin_navbar.php';
}
?>

<div class="container my-5">
    <h2 class="mb-4">Inventory Report</h2>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>Team</th>
                            <th>Current Stock</th>
                            <th>Total Restocked</th>
                            <th>Total Sold</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($products->num_rows > 0): ?>
                            <?php while ($product = $products->fetch_assoc()): ?>
                                <tr class="<?php echo $product['stock_quantity'] < 10 ? 'table-warning' : ''; ?>">
                                    <td><?php echo $product['product_id']; ?></td>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['team_name'] ?: 'N/A'); ?></td>
                                    <td><strong><?php echo $product['stock_quantity']; ?></strong></td>
                                    <td><?php echo $product['total_restocked'] ?: 0; ?></td>
                                    <td><?php echo $product['total_sold'] ?: 0; ?></td>
                                    <td>
                                        <?php if ($product['stock_quantity'] == 0): ?>
											<span class="badge bg-danger">Out of Stock</span>
                                        <?php elseif ($product['stock_quantity'] < 10): ?>
											<span class="badge bg-warning text-dark">Low Stock</span>
                                        <?php else: ?>
											<span class="badge bg-success">In Stock</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No products found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

