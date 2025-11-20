<?php
$page_title = 'Edit Review - NBA Shop';
include '../../config/config.php';
include '../../includes/header.php';
requireLogin();



$review_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if (!$review_id) {
    header('Location: ' . BASE_URL . '/user/products/index.php');
    exit();
}

$review_stmt = $conn->prepare("SELECT r.*, p.product_name, p.product_id FROM product_reviews r JOIN products p ON r.product_id = p.product_id WHERE r.review_id = ? AND r.user_id = ?");
$review_stmt->bind_param("ii", $review_id, $user_id);
$review_stmt->execute();
$review_result = $review_stmt->get_result();

if ($review_result->num_rows === 0) {
    header('Location: ' . BASE_URL . '/user/products/index.php');
    exit();
}

$review = $review_result->fetch_assoc();
$review_stmt->close();

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);
?>

<?php include '../../includes/navbar.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Edit Review</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <p><strong>Product:</strong> <?php echo htmlspecialchars($review['product_name']); ?></p>
                    
                    <form method="POST" action="update.php">
                        <input type="hidden" name="review_id" value="<?php echo $review_id; ?>">
                        <div class="mb-3">
                            <label for="rating" class="form-label">Rating *</label>
                            <select class="form-select" id="rating" name="rating">
                                <option value="0">Select Rating</option>
                                <option value="5" <?php echo $review['rating'] == 5 ? 'selected' : ''; ?>>5 Stars - Excellent</option>
                                <option value="4" <?php echo $review['rating'] == 4 ? 'selected' : ''; ?>>4 Stars - Very Good</option>
                                <option value="3" <?php echo $review['rating'] == 3 ? 'selected' : ''; ?>>3 Stars - Good</option>
                                <option value="2" <?php echo $review['rating'] == 2 ? 'selected' : ''; ?>>2 Stars - Fair</option>
                                <option value="1" <?php echo $review['rating'] == 1 ? 'selected' : ''; ?>>1 Star - Poor</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="review_text" class="form-label">Review *</label>
                            <textarea class="form-control" id="review_text" name="review_text" rows="5"><?php echo htmlspecialchars($review['review_text']); ?></textarea>
                            <small class="text-muted">Minimum 10 characters</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Review</button>
                        <a href="<?php echo BASE_URL; ?>/user/products/view.php?id=<?php echo $review['product_id']; ?>" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

