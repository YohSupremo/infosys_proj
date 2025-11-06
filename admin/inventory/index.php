<?php
$page_title = 'Inventory - Admin';
include '../../includes/header.php';
include '../../config/config.php';
requireAdmin();

$products = $conn->query("SELECT p.*, t.team_name FROM products p LEFT JOIN nba_teams t ON p.team_id = t.team_id WHERE p.is_active = 1 ORDER BY p.stock_quantity ASC, p.product_name");
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Inventory</h2>
        <a href="restock.php" class="btn btn-primary">Restock Products</a>
    </div>
    
    <div class="card">
        <div class="card-body">
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
                                        <a href="restock.php?product_id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-primary">Restock</a>
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

<?php include '../../includes/foot.php'; ?>

