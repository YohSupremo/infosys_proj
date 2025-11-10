<?php
$page_title = 'Inventory History - Admin';
include '../../includes/header.php';
include '../../config/config.php';
requireAdminOrInventoryManager();

$history = $conn->query("SELECT ih.*, p.product_name, u.first_name, u.last_name FROM inventory_history ih JOIN products p ON ih.product_id = p.product_id LEFT JOIN users u ON ih.created_by = u.user_id ORDER BY ih.created_at DESC LIMIT 100");
?>

<?php
if (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Inventory Manager') {
	include '../../includes/inventory_navbar.php';
} else {
	include '../../includes/admin_navbar.php';
}
?>

<div class="container my-5">
    <h2 class="mb-4">Inventory History</h2>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Quantity Change</th>
                            <th>Previous Stock</th>
                            <th>New Stock</th>
                            <th>Changed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($history->num_rows > 0): ?>
                            <?php while ($item = $history->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y h:i A', strtotime($item['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td>
                                        <?php
										$type_class = 'bg-info text-dark';
										if ($item['transaction_type'] === 'sale') $type_class = 'bg-danger';
										elseif ($item['transaction_type'] === 'restock') $type_class = 'bg-success';
                                        ?>
										<span class="badge <?php echo $type_class; ?>"><?php echo htmlspecialchars($item['transaction_type']); ?></span>
                                    </td>
                                    <td>
                                        <strong class="<?php echo $item['quantity_change'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo $item['quantity_change'] > 0 ? '+' : ''; ?><?php echo $item['quantity_change']; ?>
                                        </strong>
                                    </td>
                                    <td><?php echo $item['previous_stock']; ?></td>
                                    <td><strong><?php echo $item['new_stock']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name'] ?: 'System'); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No history found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

