<?php
$page_title = 'Product Details - NBA Shop';
include '../../includes/header.php';
include '../../config/config.php';
// Allow unauthenticated users to view product details

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
// Include main product image from products table as the first image
$product_images = [];

// Add main product image if it exists
if (!empty($product['image_url'])) {
    $product_images[] = [
        'image_url' => $product['image_url'],
        'is_primary' => 1,
        'display_order' => 0
    ];
}

// Get additional images from product_images table
$images_stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, display_order ASC");
$images_stmt->bind_param("i", $product_id);
$images_stmt->execute();
$images_result = $images_stmt->get_result();
while ($img = $images_result->fetch_assoc()) {
    // Skip if this image is already in the array (shouldn't happen, but just in case)
    $exists = false;
    foreach ($product_images as $existing) {
        if ($existing['image_url'] === $img['image_url']) {
            $exists = true;
            break;
        }
    }
    if (!$exists) {
        $product_images[] = $img;
    }
}
$images_stmt->close();

// Get reviews (MP4 Requirement)
$reviews_stmt = $conn->prepare("SELECT r.*, u.first_name, u.last_name FROM product_reviews r JOIN users u ON r.user_id = u.user_id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$reviews_stmt->bind_param("i", $product_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();

// Check if current user can review (has completed order) - Any user who purchased can review
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$can_review = false;
$reviewable_orders = [];
$user_has_review = false;
$user_review_id = null;

if ($user_id) {
    // Check if user has any delivered orders containing this product
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
    
    // Check if user already has a review for this product
    $user_review_check = $conn->prepare("SELECT review_id FROM product_reviews WHERE user_id = ? AND product_id = ? LIMIT 1");
    $user_review_check->bind_param("ii", $user_id, $product_id);
    $user_review_check->execute();
    $user_review_result = $user_review_check->get_result();
    $user_has_review = $user_review_result->num_rows > 0;
    $user_review_id = $user_has_review ? $user_review_result->fetch_assoc()['review_id'] : null;
    $user_review_check->close();
}
?>

<?php include '../../includes/navbar.php'; ?>

<div class="container my-5">
    <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Review deleted successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
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
                <?php else: ?>
                    <!-- Single image - no thumbnail gallery needed -->
                <?php endif; ?>
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
                <?php if (isLoggedIn()): ?>
                    <form method="POST" action="../cart/add.php">
                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg">Add to Cart</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> <strong>You need to login to shop.</strong>
                        <p class="mb-2 mt-2">Please login or create an account to add items to your cart.</p>
                        <div class="d-flex gap-2">
                            <a href="<?php echo BASE_URL; ?>/user/auth/login.php" class="btn btn-primary">Login</a>
                            <a href="<?php echo BASE_URL; ?>/user/auth/register.php" class="btn btn-outline-primary">Register</a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-warning">Out of Stock</div>
            <?php endif; ?>
            
            <?php if ($can_review): ?>
                <div class="mt-3 p-3 bg-light rounded">
                    <h5>Write a Review</h5>
                    <p class="text-muted small">You purchased this product. Share your experience!</p>
                    <?php if ($user_has_review): ?>
                        <a href="<?php echo BASE_URL; ?>/user/reviews/edit.php?id=<?php echo $user_review_id; ?>" class="btn btn-success">
                            <i class="bi bi-pencil"></i> Edit Your Review
                        </a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/user/reviews/create.php?product_id=<?php echo $product_id; ?>&order_id=<?php echo $reviewable_orders[0]; ?>" class="btn btn-primary">
                            <i class="bi bi-star"></i> Write a Review
                        </a>
                    <?php endif; ?>
                </div>
            <?php elseif (!hasRole('Admin') && !hasRole('Inventory Manager')): ?>
                <div class="mt-3 p-3 bg-light rounded">
                    <p class="text-muted small mb-0">
                        <i class="bi bi-info-circle"></i> You can review this product after your order is delivered.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Reviews Section (MP4 Requirement) -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Customer Reviews</h3>
                <?php if ($can_review && !$user_has_review): ?>
                    <a href="<?php echo BASE_URL; ?>/user/reviews/create.php?product_id=<?php echo $product_id; ?>&order_id=<?php echo $reviewable_orders[0]; ?>" class="btn btn-primary">
                        <i class="bi bi-star"></i> Write a Review
                    </a>
                <?php endif; ?>
            </div>
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
                                <div class="d-flex gap-2">
                                    <?php if ($user_id && $review['user_id'] == $user_id): ?>
                                        <a href="<?php echo BASE_URL; ?>/user/reviews/edit.php?id=<?php echo $review['review_id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <a href="<?php echo BASE_URL; ?>/user/reviews/delete.php?id=<?php echo $review['review_id']; ?>&product_id=<?php echo $product_id; ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to delete your review? This action cannot be undone.');">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($user_id && hasRole('Admin')): ?>
                                        <a href="<?php echo BASE_URL; ?>/admin/reviews/delete.php?id=<?php echo $review['review_id']; ?>&product_id=<?php echo $product_id; ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to delete this review? This action cannot be undone.');">
                                            <i class="bi bi-trash"></i> Admin Delete
                                        </a>
                                    <?php endif; ?>
                                </div>
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

