<?php
$page_title = 'Write Review - NBA Shop';
include '../../includes/header.php';
include '../../config/config.php';
requireLogin();

$bad_words = [
    "fuck", "shit", "bitch", "asshole",
    "putangina", "gago", "punyeta", "tarantado",
    "hayop", "leche", "walang hiya", "pakshet"
];

function censorText($text, $bad_words) {
    // Map common character substitutions for obfuscation (e.g., "sh1t", "f*ck")
    $sub_map = [
        'a' => '[a@4]',
        'i' => '[i1!|]',
        'o' => '[o0]',
        'e' => '[e3]',
        's' => '[s5$z]',
        'u' => '[uüv]',
        't' => '[t7+]',
    ];

    foreach ($bad_words as $word) {
        // Escape regex special characters based on delimiter '/'
        $escaped = preg_quote($word, '/');

        // Allow flexible spaces in multi-word phrases (e.g., "walang hiya")
        $escaped = str_replace('\ ', '\s+', $escaped);

        // Add fuzzy matching for character substitutions
        $pattern_chars = '';
        foreach (str_split($escaped) as $ch) {
            $lower = strtolower($ch);
            if (isset($sub_map[$lower])) {
                $pattern_chars .= $sub_map[$lower];
            } else {
                $pattern_chars .= $ch;
            }
        }

        // Build full regex pattern
        $pattern = '/(?<!\w)' . $pattern_chars . '(?!\w)/iu';

        // Replace matches with asterisks (same length as original word)
        $replacement = str_repeat('*', mb_strlen($word));
        $text = preg_replace($pattern, $replacement, $text);
    }

    return $text;
}

$product_id = intval($_GET['product_id'] ?? 0);
$order_id = intval($_GET['order_id'] ?? 0);
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if (!$product_id || !$order_id) {
    header('Location: ' . BASE_URL . '/user/products/index.php');
    exit();
}

// Verify user has completed order for this product (MP4 Requirement)
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
    
    // Check if review already exists
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
            $filtered_text = censorText($review_text, $bad_words);

            $insert_stmt = $conn->prepare("INSERT INTO product_reviews (product_id, user_id, order_id, rating, review_text) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("iiiis", $product_id, $user_id, $order_id, $rating, $filtered_text);

            if ($insert_stmt->execute()) {
                $success = 'Review submitted successfully!';
                header('Location: ' . BASE_URL . '/user/products/view.php?id=' . $product_id . '&success=1');
                exit();
            } else {
                $error = 'Failed to submit review.';
            }
            $insert_stmt->close();
        }
    }
    
    // Get product info
    $product_stmt = $conn->prepare("SELECT product_name FROM products WHERE product_id = ?");
    $product_stmt->bind_param("i", $product_id);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();
    $product = $product_result->fetch_assoc();
    $product_stmt->close();
}
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
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($product)): ?>
                        <p><strong>Product:</strong> <?php echo htmlspecialchars($product['product_name']); ?></p>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
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

