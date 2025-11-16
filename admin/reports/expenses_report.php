<?php
$page_title = 'Expenses Report - Admin';
include '../../config/config.php';
include '../../includes/header.php';
requireAdmin();

// Date filter (optional - applied only if both dates are provided)
$error = '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
$has_filter = (!empty($start_date) && !empty($end_date));

// Validate date formats if provided
if (!empty($start_date)) {
    $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($datePattern, $start_date)) {
        $error = 'Start date must be in format: YYYY-MM-DD (e.g. 2025-01-01)';
        $start_date = '';
        $has_filter = false;
    }
}

if (!empty($end_date)) {
    $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($datePattern, $end_date)) {
        $error = 'End date must be in format: YYYY-MM-DD (e.g. 2025-01-31)';
        $end_date = '';
        $has_filter = false;
    }
}

// Total expenses from restocking transactions (optionally filtered)
$total_expenses = 0;
if ($has_filter) {
	$sum_stmt = $conn->prepare("SELECT SUM(total_cost) AS total FROM restocking_transactions WHERE restock_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)");
	$sum_stmt->bind_param("ss", $start_date, $end_date);
	$sum_stmt->execute();
	$sum_res = $sum_stmt->get_result();
	if ($sum_res) {
		$row = $sum_res->fetch_assoc();
		$total_expenses = $row && $row['total'] ? floatval($row['total']) : 0;
	}
	$sum_stmt->close();
} else {
	$result = $conn->query("SELECT SUM(total_cost) AS total FROM restocking_transactions");
	if ($result) {
		$row = $result->fetch_assoc();
		$total_expenses = $row && $row['total'] ? floatval($row['total']) : 0;
	}
}

// Restocking transactions list (optionally filtered)
if ($has_filter) {
	$list_stmt = $conn->prepare("
		SELECT rt.*, s.supplier_name, u.first_name, u.last_name
		FROM restocking_transactions rt
		LEFT JOIN suppliers s ON rt.supplier_id = s.supplier_id
		LEFT JOIN users u ON rt.manager_id = u.user_id
		WHERE rt.restock_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
		ORDER BY rt.restock_date DESC
		LIMIT 200
	");
	$list_stmt->bind_param("ss", $start_date, $end_date);
	$list_stmt->execute();
	$restocks = $list_stmt->get_result();
} else {
	$restocks = $conn->query("
		SELECT rt.*, s.supplier_name, u.first_name, u.last_name 
		FROM restocking_transactions rt 
		LEFT JOIN suppliers s ON rt.supplier_id = s.supplier_id
		LEFT JOIN users u ON rt.manager_id = u.user_id
		ORDER BY rt.restock_date DESC
		LIMIT 50
	");
}
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
	<h2 class="mb-4">Expenses Report</h2>

	<?php if ($error): ?>
		<div class="alert alert-danger"><?php echo $error; ?></div>
	<?php endif; ?>

	<div class="card mb-4">
		<div class="card-body">
			<form method="GET" action="">
				<div class="row g-3 align-items-end">
					<div class="col-md-3">
						<label for="start_date" class="form-label">Start Date</label>
						<input type="text" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" placeholder="YYYY-MM-DD">
						<small class="text-muted">Format: YYYY-MM-DD (e.g. 2025-01-01)</small>
					</div>
					<div class="col-md-3">
						<label for="end_date" class="form-label">End Date</label>
						<input type="text" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" placeholder="YYYY-MM-DD">
						<small class="text-muted">Format: YYYY-MM-DD (e.g. 2025-01-31)</small>
					</div>
					<div class="col-md-3">
						<button type="submit" class="btn btn-primary">Apply Filter</button>
						<a href="<?php echo BASE_URL; ?>/admin/reports/expenses_report.php" class="btn btn-outline-secondary">Reset</a>
					</div>
				</div>
				<?php if ($has_filter): ?>
					<p class="text-muted mt-2 mb-0">Showing expenses from <strong><?php echo htmlspecialchars($start_date); ?></strong> to <strong><?php echo htmlspecialchars($end_date); ?></strong></p>
				<?php endif; ?>
			</form>
		</div>
	</div>

	<div class="row mb-4">
		<div class="col-md-3">
			<div class="card stats-card">
				<div class="card-body">
					<div class="number">₱<?php echo number_format($total_expenses, 2); ?></div>
					<div class="label">Total Expenses</div>
				</div>
			</div>
		</div>
	</div>

	<div class="card">
		<div class="card-body">
			<div class="table-responsive">
				<table class="table">
					<thead>
						<tr>
							<th>Restock ID</th>
							<th>Supplier</th>
							<th>Manager</th>
							<th>Total Cost</th>
							<th>Date</th>
							<th>Notes</th>
						</tr>
					</thead>
					<tbody>
						<?php if ($restocks && $restocks->num_rows > 0): ?>
							<?php while ($row = $restocks->fetch_assoc()): ?>
								<tr>
									<td>#<?php echo intval($row['restock_id']); ?></td>
									<td><?php echo htmlspecialchars($row['supplier_name'] ?: 'N/A'); ?></td>
									<td><?php echo htmlspecialchars(($row['first_name'] ?: '') . ' ' . ($row['last_name'] ?: '')); ?></td>
									<td>₱<?php echo number_format($row['total_cost'], 2); ?></td>
									<td><?php echo date('M d, Y h:i A', strtotime($row['restock_date'])); ?></td>
									<td><?php echo htmlspecialchars($row['notes'] ?: ''); ?></td>
								</tr>
							<?php endwhile; ?>
						<?php else: ?>
							<tr>
								<td colspan="6" class="text-center">No restocking transactions found.</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<?php include '../../includes/foot.php'; ?>


