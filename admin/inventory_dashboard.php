<?php
$page_title = 'Inventory Dashboard';
include '../config/config.php';
include '../includes/header.php';

requireAdminOrInventoryManager();

// Stats for inventory-focused dashboard
$stats = array();

// Total active products
$result = $conn->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
$stats['products'] = $result ? intval($result->fetch_assoc()['count']) : 0;

// Low stock (threshold 10)
$result = $conn->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1 AND stock_quantity < 10");
$stats['low_stock'] = $result ? intval($result->fetch_assoc()['count']) : 0;

// Out of stock
$result = $conn->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1 AND stock_quantity = 0");
$stats['out_of_stock'] = $result ? intval($result->fetch_assoc()['count']) : 0;

// Recent restocks
$recent_restocks = $conn->query("SELECT ih.*, p.product_name FROM inventory_history ih JOIN products p ON ih.product_id = p.product_id WHERE ih.transaction_type = 'restock' ORDER BY ih.created_at DESC LIMIT 10");
?>

<?php include '../includes/inventory_navbar.php'; ?>

<div class="container my-5">
	<h2 class="mb-4">Inventory Dashboard</h2>

	<div class="row mb-4">
		<div class="col-md-3 mb-3">
			<div class="card stats-card">
				<div class="card-body">
					<div class="number"><?php echo $stats['products']; ?></div>
					<div class="label">Active Products</div>
				</div>
			</div>
		</div>
		<div class="col-md-3 mb-3">
			<div class="card stats-card">
				<div class="card-body">
					<div class="number"><?php echo $stats['low_stock']; ?></div>
					<div class="label">Low Stock</div>
				</div>
			</div>
		</div>
		<div class="col-md-3 mb-3">
			<div class="card stats-card">
				<div class="card-body">
					<div class="number"><?php echo $stats['out_of_stock']; ?></div>
					<div class="label">Out of Stock</div>
				</div>
			</div>
		</div>
		<div class="col-md-3 mb-3">
			<div class="card stats-card">
				<div class="card-body">
					<a href="<?php echo BASE_URL; ?>/admin/inventory/restock.php" class="btn btn-primary w-100">Restock Products</a>
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-header">
			<h5 class="mb-0">Recent Restocks</h5>
		</div>
		<div class="card-body">
			<?php if ($recent_restocks && $recent_restocks->num_rows > 0): ?>
				<div class="table-responsive">
					<table class="table">
						<thead>
							<tr>
								<th>Product</th>
								<th>Quantity Change</th>
								<th>Previous Stock</th>
								<th>New Stock</th>
								<th>Date</th>
							</tr>
						</thead>
						<tbody>
							<?php while ($row = $recent_restocks->fetch_assoc()): ?>
								<tr>
									<td><?php echo htmlspecialchars($row['product_name']); ?></td>
									<td><span class="badge bg-success"><?php echo intval($row['quantity_change']); ?></span></td>
									<td><?php echo intval($row['previous_stock']); ?></td>
									<td><?php echo intval($row['new_stock']); ?></td>
									<td><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td>
								</tr>
							<?php endwhile; ?>
						</tbody>
					</table>
				</div>
			<?php else: ?>
				<p class="text-muted mb-0">No recent restock activity.</p>
			<?php endif; ?>
		</div>
	</div>
</div>
`
<?php include '../includes/foot.php'; ?>


