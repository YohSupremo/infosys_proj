<?php
$page_title = 'Write Review - NBA Shop';
include '../../config/config.php';
include '../../includes/header.php';
requireLogin();



$product_id = intval($_GET['product_id'] ?? 0);
$order_id = intval($_GET['order_id'] ?? 0);
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if (!$product_id || !$order_id) {
    header('Location: ' . BASE_URL . '/user/products/index.php');
    exit();
}

$order_check = $conn->prepare("SELECT o.order_id, o.order_status, oi.product_id 
                                FROM orders o 
                                JOIN order_items oi ON o.order_id = oi.order_id 
                                WHERE o.order_id = ? AND o.user_id = ? AND oi.product_id = ? AND o.order_status = 'Delivered'");
$order_check->bind_param("iii", $order_id, $user_id, $product_id);
$order_check->execute();
$order_result = $order_check->get_result();

if ($order_result->num_rows === 0) {
    $error = 'You can only review products from completed orders.';
    $order_check->close();
} else {
    $order_check->close();
    
    $existing_review = $conn->prepare("SELECT review_id FROM product_reviews WHERE user_id = ? AND product_id = ? AND order_id = ?");
    $existing_review->bind_param("iii", $user_id, $product_id, $order_id);
    $existing_review->execute();
    $existing_result = $existing_review->get_result();
    
    if ($existing_result->num_rows > 0) {
        $review = $existing_result->fetch_assoc();
        header('Location: edit.php?id=' . $review['review_id']);
        exit();
    }
    $existing_review->close();
}

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);

$product_stmt = $conn->prepare("SELECT product_name FROM products WHERE product_id = ?");
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();
$product = $product_result->fetch_assoc();
$product_stmt->close();
?>

<?php include '../../includes/navbar.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Write Review</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($product)): ?>
                        <p><strong>Product:</strong> <?php echo htmlspecialchars($product['product_name']); ?></p>
                    <?php endif; ?>
                    
                    <form method="POST" action="store.php">
                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                        <div class="mb-3">
                            <label for="rating" class="form-label">Rating *</label>
                            <select class="form-select" id="rating" name="rating">
                                <option value="0">Select Rating</option>
                                <option value="5">5 Stars - Excellent</option>
                                <option value="4">4 Stars - Very Good</option>
                                <option value="3">3 Stars - Good</option>
                                <option value="2">2 Stars - Fair</option>
                                <option value="1">1 Star - Poor</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="review_text" class="form-label">Review *</label>
                            <textarea class="form-control" id="review_text" name="review_text" rows="5"><?php echo htmlspecialchars($_POST['review_text'] ?? ''); ?></textarea>
                            <small class="text-muted">Minimum 10 characters</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Review</button>
                        <a href="<?php echo BASE_URL; ?>/user/products/view.php?id=<?php echo $product_id; ?>" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

