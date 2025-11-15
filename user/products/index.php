<?php
$page_title = 'Products - NBA Shop';
include '../../config/config.php';
include '../../includes/header.php';
// Allow unauthenticated users to view products

$search = sanitize($_GET['search'] ?? '');
$team_id = intval($_GET['team_id'] ?? 0);
$category_id = intval($_GET['category_id'] ?? 0);

// Build query
$query = "SELECT DISTINCT p.*, t.team_name, t.team_code 
          FROM products p 
          LEFT JOIN nba_teams t ON p.team_id = t.team_id 
          WHERE p.is_active = 1";
$params = [];
$types = "";

if ($search) {
    $query .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if ($team_id > 0) {
    $query .= " AND p.team_id = ?";
    $params[] = $team_id;
    $types .= "i";
}

if ($category_id > 0) {
    $query .= " AND p.product_id IN (SELECT product_id FROM product_categories WHERE category_id = ?)";
    $params[] = $category_id;
    $types .= "i";
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get teams for filter
$teams_result = $conn->query("SELECT * FROM nba_teams ORDER BY team_name");

// Get categories for filter
$categories_result = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY category_name");
?>

<?php include '../../includes/navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Products</h2>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <input type="text" class="form-control" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <select class="form-select" name="team_id">
                            <option value="0">All Teams</option>
                            <?php while ($team = $teams_result->fetch_assoc()): ?>
                                <option value="<?php echo $team['team_id']; ?>" <?php echo $team_id == $team['team_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($team['team_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <select class="form-select" name="category_id">
                            <option value="0">All Categories</option>
                            <?php while ($category = $categories_result->fetch_assoc()): ?>
                                <option value="<?php echo $category['category_id']; ?>" <?php echo $category_id == $category['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Products Grid -->
    <div class="row">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($product = $result->fetch_assoc()): ?>
                <div class="col-md-3 mb-4">
                    <div class="card product-card" onclick="window.location='view.php?id=<?php echo $product['product_id']; ?>'">
                        <?php if ($product['image_url']): ?>
                            <img src="../../<?php echo htmlspecialchars($product['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        <?php else: ?>
                            <img src="../../assets/images/placeholder.jpg" class="card-img-top" alt="No image">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title product-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                            <?php if ($product['team_name']): ?>
                                <p class="text-muted small"><?php echo htmlspecialchars($product['team_name']); ?></p>
                            <?php endif; ?>
                            <p class="product-price">₱<?php echo number_format($product['price'], 2); ?></p>
                            <p class="text-muted small">Stock: <?php echo $product['stock_quantity']; ?></p>
                            <a href="view.php?id=<?php echo $product['product_id']; ?>" class="btn btn-primary btn-sm w-100">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">No products found.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Scroll to Top Button -->
<button id="scrollToTopBtn" onclick="window.scrollTo({top: 0, behavior: 'smooth'});" style="display: none; position: fixed; bottom: 20px; right: 20px; z-index: 1000; width: 50px; height: 50px; border-radius: 50%; background-color: #007bff; color: white; border: none; cursor: pointer; font-size: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">
    ↑
</button>

<script>
window.addEventListener('scroll', function() {
    var btn = document.getElementById('scrollToTopBtn');
    if (window.pageYOffset > 300) {
        btn.style.display = 'block';
    } else {
        btn.style.display = 'none';
    }
});
</script>

<?php include '../../includes/foot.php'; ?>

