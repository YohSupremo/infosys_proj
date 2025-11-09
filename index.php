<?php
$page_title = 'NBA Shop - Official NBA Apparel';
include 'includes/header.php';
include 'config/config.php';

// Get featured products
$featured_query = "SELECT p.*, t.team_name, t.team_code 
                   FROM products p 
                   LEFT JOIN nba_teams t ON p.team_id = t.team_id 
                   WHERE p.is_active = 1 
                   ORDER BY p.created_at DESC 
                   LIMIT 8";
$featured_result = $conn->query($featured_query);
?>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>/index.php">
            <i class="bi bi-basketball"></i> NBA Shop
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/index.php">
                        <i class="bi bi-house"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/user/products/index.php">
                        <i class="bi bi-grid"></i> Products
                    </a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/user/cart/index.php">
                            <i class="bi bi-cart"></i> Cart
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/user/orders.php/index.php">
                            <i class="bi bi-bag"></i> My Orders
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/user/account/profile.php"><i class="bi bi-person"></i> My Profile</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/user/account/addresses.php"><i class="bi bi-geo-alt"></i> My Addresses</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Admin'): ?>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/dashboard.php"><i class="bi bi-shield-check"></i> Admin Panel</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/user/auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/user/auth/login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/user/auth/register.php">
                            <i class="bi bi-person-plus"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="hero-section">
    <div class="container">
        <h1>Welcome to NBA Shop</h1>
        <p>Get Your Favorite Team's Official Apparel</p>
        <a href="<?php echo BASE_URL; ?>/user/products/index.php" class="btn btn-primary btn-lg">Shop Now</a>
    </div>
</div>

<div class="container my-5">
    <h2 class="text-center mb-4">Featured Products</h2>
    <div class="row">
        <?php if ($featured_result && $featured_result->num_rows > 0): ?>
            <?php while ($product = $featured_result->fetch_assoc()): ?>
                <div class="col-md-3 mb-4">
                    <div class="card product-card" onclick="window.location='<?php echo BASE_URL; ?>/user/products/view.php?id=<?php echo $product['product_id']; ?>'">
                        <?php if ($product['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        <?php else: ?>
                            <img src="assets/images/placeholder.jpg" class="card-img-top" alt="No image">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title product-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                            <?php if ($product['team_name']): ?>
                                <p class="text-muted small"><?php echo htmlspecialchars($product['team_name']); ?></p>
                            <?php endif; ?>
                            <p class="product-price">₱<?php echo number_format($product['price'], 2); ?></p>
                            <a href="<?php echo BASE_URL; ?>/user/products/view.php?id=<?php echo $product['product_id']; ?>" class="btn btn-primary btn-sm w-100">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">No products available at the moment.</div>
            </div>
        <?php endif; ?>
    </div>
    <div class="text-center mt-4">
        <a href="<?php echo BASE_URL; ?>/user/products/index.php" class="btn btn-outline-primary">View All Products</a>
    </div>
</div>

<?php include 'includes/foot.php'; ?>

