<?php
$page_title = 'Edit Review - NBA Shop';
include '../../includes/header.php';
include '../../config/config.php';
requireLogin();

$review_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if (!$review_id) {
    header('Location: ' . BASE_URL . '/user/products/index.php');
    exit();
}

// Get review and verify ownership
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 0);
    $review_text = sanitize($_POST['review_text'] ?? '');
    
    // Server-side validation
    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a valid rating (1-5 stars).';
    } elseif (empty($review_text)) {
        $error = 'Please write a review.';
    } elseif (strlen($review_text) < 10) {
        $error = 'Review must be at least 10 characters long.';
    } else {
        // Apply regex filter for bad words (MP4 Requirement)
        $bad_words = ['bad', 'terrible', 'awful', 'horrible', 'worst']; // Add more as needed
        $filtered_text = $review_text;
        foreach ($bad_words as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
            $filtered_text = preg_replace($pattern, str_repeat('*', strlen($word)), $filtered_text);
        }
        
        $update_stmt = $conn->prepare("UPDATE product_reviews SET rating = ?, review_text = ? WHERE review_id = ? AND user_id = ?");
        $update_stmt->bind_param("isii", $rating, $filtered_text, $review_id, $user_id);
        
        if ($update_stmt->execute()) {
            $success = 'Review updated successfully!';
            header('Location: ' . BASE_URL . '/user/products/view.php?id=' . $review['product_id'] . '&success=1');
            exit();
        } else {
            $error = 'Failed to update review.';
        }
        $update_stmt->close();
    }
}
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
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <p><strong>Product:</strong> <?php echo htmlspecialchars($review['product_name']); ?></p>
                    
                    <form method="POST" action="">
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

