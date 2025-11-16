
<?php 
if (!defined('BASE_URL')) {
    define('BASE_URL', '/infosys_proj');
}
$base_url = BASE_URL;
$role_name = isset($_SESSION['role_name']) ? $_SESSION['role_name'] : null;
$is_admin = $role_name === 'Admin';
$is_inventory_manager = $role_name === 'Inventory Manager';
?>

<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container-fluid">
		<a class="navbar-brand" href="<?= $base_url ?><?= $is_inventory_manager && !$is_admin ? '/admin/inventory_dashboard.php' : '/admin/dashboard.php' ?>">
			<i class="bi bi-shield-check"></i> <?= $is_inventory_manager && !$is_admin ? 'Inventory Panel' : 'Admin Panel' ?>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto">
				<li class="nav-item">
					<a class="nav-link" href="<?= $base_url ?><?= $is_inventory_manager && !$is_admin ? '/admin/inventory_dashboard.php' : '/admin/dashboard.php' ?>">
						<i class="bi bi-speedometer2"></i> Dashboard
					</a>
				</li>

                <!-- Products -->
				<?php if ($is_admin): ?>
				<li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="productsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-box"></i> Products
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= $base_url ?>/admin/products/index.php"><i class="bi bi-list"></i> All Products</a></li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/admin/products/create.php"><i class="bi bi-plus-circle"></i> Add Product</a></li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/admin/categories/index.php"><i class="bi bi-tags"></i> Categories</a></li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/admin/teams/index.php"><i class="bi bi-trophy"></i> Teams</a></li>
                    </ul>
                </li>
				<?php endif; ?>

                <!-- Orders -->
				<?php if ($is_admin): ?>
				<li class="nav-item">
                    <a class="nav-link" href="<?= $base_url ?>/admin/orders/index.php">
                        <i class="bi bi-bag-check"></i> Orders
                    </a>
                </li>
				<?php endif; ?>

                <!-- Users -->
				<?php if ($is_admin): ?>
				<li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="usersDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-people"></i> Users
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= $base_url ?>/admin/users/index.php"><i class="bi bi-person-lines-fill"></i> All Users</a></li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/admin/users/create.php"><i class="bi bi-person-plus"></i> Add User</a></li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/admin/roles/index.php"><i class="bi bi-shield"></i> Roles</a></li>
                    </ul>
                </li>
				<?php endif; ?>

                <!-- Inventory -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="inventoryDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-boxes"></i> Inventory
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= $base_url ?>/admin/inventory/index.php"><i class="bi bi-clipboard-data"></i> Stock Levels</a></li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/admin/inventory/restock.php"><i class="bi bi-arrow-down-up"></i> Restock</a></li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/admin/inventory/history.php"><i class="bi bi-clock-history"></i> History</a></li>
						<?php if ($is_admin): ?>
						<li><a class="dropdown-item" href="<?= $base_url ?>/admin/suppliers/index.php"><i class="bi bi-truck"></i> Suppliers</a></li>
						<?php endif; ?>
                    </ul>
                </li>

                <!-- Discounts -->
				<?php if ($is_admin): ?>
				<li class="nav-item">
                    <a class="nav-link" href="<?= $base_url ?>/admin/discounts/index.php">
                        <i class="bi bi-percent"></i> Discounts
                    </a>
                </li>
				<?php endif; ?>

                <!-- Reports -->
				<li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-graph-up"></i> Reports
                    </a>
                    <ul class="dropdown-menu">
						<?php if ($is_admin): ?>
						<li><a class="dropdown-item" href="<?= $base_url ?>/admin/reports/sales_report.php"><i class="bi bi-cash-stack"></i> Sales</a></li>
						<li><a class="dropdown-item" href="<?= $base_url ?>/admin/reports/order_details_report.php"><i class="bi bi-receipt-cutoff"></i> Order Details</a></li>
						<?php endif; ?>
                        <li><a class="dropdown-item" href="<?= $base_url ?>/admin/reports/inventory_report.php"><i class="bi bi-box-seam"></i> Inventory</a></li>
						<?php if ($is_admin): ?>
						<li><a class="dropdown-item" href="<?= $base_url ?>/admin/reports/expenses_report.php"><i class="bi bi-receipt"></i> Expenses</a></li>
						<li><a class="dropdown-item" href="<?= $base_url ?>/admin/reports/net_income_report.php"><i class="bi bi-calculator"></i> Net Income</a></li>
						<li><a class="dropdown-item" href="<?= $base_url ?>/admin/reports/discount_report.php"><i class="bi bi-tag"></i> Discounts</a></li>
						<li><a class="dropdown-item" href="<?= $base_url ?>/admin/reports/user_report.php"><i class="bi bi-people"></i> Users</a></li>
						<?php endif; ?>
                    </ul>
                </li>
            </ul>

            <!-- Right side -->
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
