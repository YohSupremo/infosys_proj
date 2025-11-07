<?php
// Calculate base path to admin directory
$admin_base = '';

?>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo $admin_base; ?>dashboard.php">
            <i class="bi bi-shield-check"></i> Admin Panel
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $admin_base; ?>dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="productsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-box"></i> Products
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $admin_base; ?>products/index.php"><i class="bi bi-list"></i> All Products</a></li>
                        <li><a class="dropdown-item" href="<?php echo $admin_base; ?>products/create.php"><i class="bi bi-plus-circle"></i> Add Product</a></li>
                        <li><a class="dropdown-item" href="<?php echo $admin_base; ?>categories/index.php"><i class="bi bi-tags"></i> Categories</a></li>
                        <li><a class="dropdown-item" href="<?php echo $admin_base; ?>teams/index.php"><i class="bi bi-trophy"></i> Teams</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $admin_base; ?>orders/index.php">
                        <i class="bi bi-bag-check"></i> Orders
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="usersDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-people"></i> Users
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $admin_base; ?>users/index.php"><i class="bi bi-person-lines-fill"></i> All Users</a></li>
                        <li><a class="dropdown-item" href="<?php echo $admin_base; ?>users/create.php"><i class="bi bi-person-plus"></i> Add User</a></li>
                        <li><a class="dropdown-item" href="<?php echo $admin_base; ?>roles/index.php"><i class="bi bi-shield"></i> Roles</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="inventoryDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-boxes"></i> Inventory
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $admin_base; ?>inventory/index.php"><i class="bi bi-clipboard-data"></i> Stock Levels</a></li>
                        <li><a class="dropdown-item" href="<?php echo $admin_base; ?>inventory/restock.php"><i class="bi bi-arrow-down-up"></i> Restock</a></li>
                        <li><a class="dropdown-item" href="<?php echo $admin_base; ?>inventory/history.php"><i class="bi bi-clock-history"></i> History</a></li>
                        <li><a class="dropdown-item" href="<?php echo $admin_base; ?>suppliers/index.php"><i class="bi bi-truck"></i> Suppliers</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $admin_base; ?>discounts/index.php">
                        <i class="bi bi-percent"></i> Discounts
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-graph-up"></i> Reports
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo $admin_base; ?>reports/sales_report.php"><i class="bi bi-cash-stack"></i> Sales</a></li>
                        <li><a class="dropdown-item" href="<?php echo $admin_base; ?>reports/inventory_report.php"><i class="bi bi-box-seam"></i> Inventory</a></li>
                        <li><a class="dropdown-item" href="<?php echo $admin_base; ?>reports/discount_report.php"><i class="bi bi-tag"></i> Discounts</a></li>
                        <li><a class="dropdown-item" href="<?php echo $admin_base; ?>reports/user_report.php"><i class="bi bi-people"></i> Users</a></li>
                    </ul>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $admin_base; ?>../index.php">
                        <i class="bi bi-house"></i> View Site
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $admin_base; ?>../user/auth/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

