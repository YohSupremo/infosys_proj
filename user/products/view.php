<?php
$page_title = 'Product Details - NBA Shop';
include '../../includes/header.php';
include '../../config/config.php';
requireLogin();

$product_id = intval($_GET['id'] ?? 0);

if (!$product_id) {
    header('Location: index.php');
    exit();
}

$stmt = $conn->prepare("SELECT p.*, t.team_name, t.team_code FROM products p LEFT JOIN nba_teams t ON p.team_id = t.team_id WHERE p.product_id = ? AND p.is_active = 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$product = $result->fetch_assoc();

// Get categories
$cat_stmt = $conn->prepare("SELECT c.category_name FROM categories c JOIN product_categories pc ON c.category_id = pc.category_id WHERE pc.product_id = ?");
$cat_stmt->bind_param("i", $product_id);
$cat_stmt->execute();
$categories = $cat_stmt->get_result();
?>

<?php include '../../includes/navbar.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-6">
            <?php if ($product['image_url']): ?>
                <img src="../../<?php echo htmlspecialchars($product['image_url']); ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
            <?php else: ?>
                <img src="../../assets/images/placeholder.jpg" class="img-fluid rounded" alt="No image">
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <h2><?php echo htmlspecialchars($product['product_name']); ?></h2>
            <?php if ($product['team_name']): ?>
                <p class="text-muted"><strong>Team:</strong> <?php echo htmlspecialchars($product['team_name']); ?></p>
            <?php endif; ?>
            
            <?php if ($categories->num_rows > 0): ?>
                <p class="text-muted">
                    <strong>Categories:</strong> 
                    <?php 
                    $cat_names = [];
                    while ($cat = $categories->fetch_assoc()) {
                        $cat_names[] = htmlspecialchars($cat['category_name']);
                    }
                    echo implode(', ', $cat_names);
                    ?>
                </p>
            <?php endif; ?>
            
            <h3 class="product-price">₱<?php echo number_format($product['price'], 2); ?></h3>
            
            <p class="mt-3"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            
            <p><strong>Stock Available:</strong> <?php echo $product['stock_quantity']; ?></p>
            
            <?php if ($product['stock_quantity'] > 0): ?>
                <form method="POST" action="../cart/add.php">
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg">Add to Cart</button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">Out of Stock</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

