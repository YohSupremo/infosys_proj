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

// Get product images (MP1 Requirement - Multiple Photos)
$images_stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, display_order ASC");
$images_stmt->bind_param("i", $product_id);
$images_stmt->execute();
$images_result = $images_stmt->get_result();
$product_images = [];
while ($img = $images_result->fetch_assoc()) {
    $product_images[] = $img;
}
$images_stmt->close();

// Get reviews (MP4 Requirement)
$reviews_stmt = $conn->prepare("SELECT r.*, u.first_name, u.last_name FROM product_reviews r JOIN users u ON r.user_id = u.user_id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$reviews_stmt->bind_param("i", $product_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();

// Check if current user can review (has completed order)
$user_id = $_SESSION['user_id'];
$can_review = false;
$reviewable_orders = [];
if (hasRole('Customer')) {
    $order_check = $conn->prepare("SELECT DISTINCT o.order_id FROM orders o JOIN order_items oi ON o.order_id = oi.order_id WHERE o.user_id = ? AND oi.product_id = ? AND o.order_status = 'Delivered'");
    $order_check->bind_param("ii", $user_id, $product_id);
    $order_check->execute();
    $order_result = $order_check->get_result();
    if ($order_result->num_rows > 0) {
        $can_review = true;
        while ($order = $order_result->fetch_assoc()) {
            $reviewable_orders[] = $order['order_id'];
        }
    }
    $order_check->close();
}
?>

<?php include '../../includes/navbar.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-6">
            <!-- Product Images (MP1 - Multiple Photos) -->
            <?php if (count($product_images) > 0): ?>
                <!-- Main Image -->
                <div class="mb-3">
                    <img id="mainProductImage" src="../../<?php echo htmlspecialchars($product_images[0]['image_url']); ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($product['product_name']); ?>" style="max-height: 500px; object-fit: contain;">
                </div>
                <!-- Thumbnail Gallery -->
                <?php if (count($product_images) > 1): ?>
                    <div class="row">
                        <?php foreach ($product_images as $index => $img): ?>
                            <div class="col-3 mb-2">
                                <img src="../../<?php echo htmlspecialchars($img['image_url']); ?>" 
                                     class="img-thumbnail product-thumbnail <?php echo $index === 0 ? 'active border-primary' : ''; ?>" 
                                     alt="Image <?php echo $index + 1; ?>"
                                     style="cursor: pointer; width: 100%; height: 100px; object-fit: cover; <?php echo $index === 0 ? 'border-width: 3px !important;' : ''; ?>"
                                     onclick="document.getElementById('mainProductImage').src = this.src; document.querySelectorAll('.product-thumbnail').forEach(t => { t.classList.remove('active', 'border-primary'); t.style.borderWidth = ''; }); this.classList.add('active', 'border-primary'); this.style.borderWidth = '3px';">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php elseif ($product['image_url']): ?>
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
                        <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg">Add to Cart</button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">Out of Stock</div>
            <?php endif; ?>
            
            <?php if ($can_review): ?>
                <div class="mt-3">
                    <a href="<?php echo BASE_URL; ?>/user/reviews/create.php?product_id=<?php echo $product_id; ?>&order_id=<?php echo $reviewable_orders[0]; ?>" class="btn btn-outline-primary">Write a Review</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Reviews Section (MP4 Requirement) -->
    <div class="row mt-5">
        <div class="col-12">
            <h3>Customer Reviews</h3>
            <?php if ($reviews_result->num_rows > 0): ?>
                <?php while ($review = $reviews_result->fetch_assoc()): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></h5>
                                    <div class="mb-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $review['rating']): ?>
                                                <span class="text-warning">★</span>
                                            <?php else: ?>
                                                <span class="text-muted">★</span>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        <span class="ms-2">(<?php echo $review['rating']; ?>/5)</span>
                                    </div>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                                </div>
                                <?php if ($review['user_id'] == $user_id): ?>
                                    <a href="<?php echo BASE_URL; ?>/user/reviews/edit.php?id=<?php echo $review['review_id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">No reviews yet. Be the first to review this product!</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

