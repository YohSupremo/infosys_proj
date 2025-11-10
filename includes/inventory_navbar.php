<?php 
if (!defined('BASE_URL')) {
	define('BASE_URL', '/infosys_proj');
}
$base_url = BASE_URL;
?>

<nav class="navbar navbar-expand-lg navbar-light">
	<div class="container-fluid">
		<a class="navbar-brand" href="<?= $base_url ?>/admin/inventory_dashboard.php">
			<i class="bi bi-boxes"></i> Inventory Panel
		</a>

		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#inventoryNavbar">
			<span class="navbar-toggler-icon"></span>
		</button>

		<div class="collapse navbar-collapse" id="inventoryNavbar">
			<ul class="navbar-nav me-auto">
				<li class="nav-item">
					<a class="nav-link" href="<?= $base_url ?>/admin/inventory_dashboard.php">
						<i class="bi bi-speedometer2"></i> Dashboard
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= $base_url ?>/admin/inventory/index.php">
						<i class="bi bi-clipboard-data"></i> Stock Levels
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= $base_url ?>/admin/inventory/restock.php">
						<i class="bi bi-arrow-down-up"></i> Restock
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= $base_url ?>/admin/inventory/history.php">
						<i class="bi bi-clock-history"></i> History
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= $base_url ?>/admin/reports/inventory_report.php">
						<i class="bi bi-box-seam"></i> Inventory Report
					</a>
				</li>
			</ul>

			<ul class="navbar-nav">
				<li class="nav-item">
					<a class="nav-link" href="<?= $base_url ?>/index.php">
						<i class="bi bi-house"></i> View Site
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= $base_url ?>/user/auth/logout.php">
						<i class="bi bi-box-arrow-right"></i> Logout
					</a>
				</li>
			</ul>
		</div>
	</div>
</nav>


