<?php
$page_title = 'Net Income Report - Admin';
include '../../includes/header.php';
include '../../config/config.php';
requireAdmin();

// Date filter (optional - requires both start and end to apply)
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
$has_filter = (!empty($start_date) && !empty($end_date));

// Revenue: orders not Cancelled (optionally filtered by order_date)
$total_revenue = 0;
if ($has_filter) {
	$rev_stmt = $conn->prepare("SELECT SUM(total_amount) AS total_revenue FROM orders WHERE order_status != 'Cancelled' AND order_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)");
	$rev_stmt->bind_param("ss", $start_date, $end_date);
} else {
	$rev_stmt = $conn->prepare("SELECT SUM(total_amount) AS total_revenue FROM orders WHERE order_status != 'Cancelled'");
}
$rev_stmt->execute();
$rev_res = $rev_stmt->get_result();
if ($rev_res && $rev_res->num_rows > 0) {
	$row = $rev_res->fetch_assoc();
	$total_revenue = $row['total_revenue'] ? floatval($row['total_revenue']) : 0;
}
$rev_stmt->close();

// Expenses: sum of restocking total cost
$total_expenses = 0;
if ($has_filter) {
	$exp_stmt = $conn->prepare("SELECT SUM(total_cost) AS total_expenses FROM restocking_transactions WHERE restock_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)");
	$exp_stmt->bind_param("ss", $start_date, $end_date);
} else {
	$exp_stmt = $conn->prepare("SELECT SUM(total_cost) AS total_expenses FROM restocking_transactions");
}
$exp_stmt->execute();
$exp_res = $exp_stmt->get_result();
if ($exp_res && $exp_res->num_rows > 0) {
	$row = $exp_res->fetch_assoc();
	$total_expenses = $row['total_expenses'] ? floatval($row['total_expenses']) : 0;
}
$exp_stmt->close();

$net_income = $total_revenue - $total_expenses;

// Recent period summaries (last 30 days)
$rev30_stmt = $conn->prepare("SELECT SUM(total_amount) AS revenue_30 FROM orders WHERE order_status != 'Cancelled' AND order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$rev30_stmt->execute();
$rev30 = $rev30_stmt->get_result()->fetch_assoc();
$revenue_30 = $rev30 && $rev30['revenue_30'] ? floatval($rev30['revenue_30']) : 0;
$rev30_stmt->close();

$exp30_stmt = $conn->prepare("SELECT SUM(total_cost) AS expenses_30 FROM restocking_transactions WHERE restock_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$exp30_stmt->execute();
$exp30 = $exp30_stmt->get_result()->fetch_assoc();
$expenses_30 = $exp30 && $exp30['expenses_30'] ? floatval($exp30['expenses_30']) : 0;
$exp30_stmt->close();
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
	<h2 class="mb-4">Net Income Report</h2>

	<div class="card mb-4">
		<div class="card-body">
			<form method="GET" action="">
				<div class="row g-3 align-items-end">
					<div class="col-md-3">
						<label for="start_date" class="form-label">Start Date</label>
						<input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
					</div>
					<div class="col-md-3">
						<label for="end_date" class="form-label">End Date</label>
						<input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
					</div>
					<div class="col-md-3">
						<button type="submit" class="btn btn-primary">Apply Filter</button>
						<a href="<?php echo BASE_URL; ?>/admin/reports/net_income_report.php" class="btn btn-outline-secondary">Reset</a>
					</div>
				</div>
				<?php if ($has_filter): ?>
					<p class="text-muted mt-2 mb-0">Showing results from <strong><?php echo htmlspecialchars($start_date); ?></strong> to <strong><?php echo htmlspecialchars($end_date); ?></strong></p>
				<?php endif; ?>
			</form>
		</div>
	</div>

	<div class="row mb-4">
		<div class="col-md-4 mb-3">
			<div class="card stats-card">
				<div class="card-body">
					<div class="label">Total Revenue</div>
					<div class="number">₱<?php echo number_format($total_revenue, 2); ?></div>
				</div>
			</div>
		</div>
		<div class="col-md-4 mb-3">
			<div class="card stats-card">
				<div class="card-body">
					<div class="label">Total Expenses</div>
					<div class="number">₱<?php echo number_format($total_expenses, 2); ?></div>
				</div>
			</div>
		</div>
		<div class="col-md-4 mb-3">
			<div class="card stats-card">
				<div class="card-body">
					<div class="label">Net Income</div>
					<div class="number <?php echo $net_income >= 0 ? 'text-success' : 'text-danger'; ?>">₱<?php echo number_format($net_income, 2); ?></div>
				</div>
			</div>
		</div>
	</div>

	<div class="row mb-4">
		<div class="col-md-6 mb-3">
			<div class="card">
				<div class="card-header">
					<h5 class="mb-0">Last 30 Days</h5>
				</div>
				<div class="card-body">
					<div class="d-flex justify-content-between">
						<span>Revenue</span>
						<strong>₱<?php echo number_format($revenue_30, 2); ?></strong>
					</div>
					<div class="d-flex justify-content-between">
						<span>Expenses</span>
						<strong>₱<?php echo number_format($expenses_30, 2); ?></strong>
					</div>
					<hr>
					<div class="d-flex justify-content-between">
						<span>Net</span>
						<strong class="<?php echo ($revenue_30 - $expenses_30) >= 0 ? 'text-success' : 'text-danger'; ?>">
							₱<?php echo number_format($revenue_30 - $expenses_30, 2); ?>
						</strong>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php include '../../includes/foot.php'; ?>


