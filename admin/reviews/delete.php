<?php
include '../../config/config.php';
requireAdmin();

$review_id = intval($_GET['id'] ?? 0);
$product_id = intval($_GET['product_id'] ?? 0);

if (!$review_id) {
    header('Location: ' . BASE_URL . '/admin/products/index.php');
    exit();
}

// Get review info to get product_id if not provided
if (!$product_id) {
    $review_stmt = $conn->prepare("SELECT product_id FROM product_reviews WHERE review_id = ?");
    $review_stmt->bind_param("i", $review_id);
    $review_stmt->execute();
    $review_result = $review_stmt->get_result();
    if ($review_result->num_rows > 0) {
        $review = $review_result->fetch_assoc();
        $product_id = $review['product_id'];
    }
    $review_stmt->close();
}

// Delete review
$delete_stmt = $conn->prepare("DELETE FROM product_reviews WHERE review_id = ?");
$delete_stmt->bind_param("i", $review_id);
$delete_stmt->execute();
$delete_stmt->close();

// Redirect back to product page or admin products list
if ($product_id) {
    header('Location: ' . BASE_URL . '/user/products/view.php?id=' . $product_id . '&deleted=1');
} else {
    header('Location: ' . BASE_URL . '/admin/products/index.php');
}
exit();
?>

