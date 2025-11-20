<?php
include '../../config/config.php';
requireLogin();

$review_id = intval($_GET['id'] ?? 0);
$product_id = intval($_GET['product_id'] ?? 0);

if (!$review_id) {
    header('Location: ' . BASE_URL . '/user/products/index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

$review_stmt = $conn->prepare("SELECT product_id FROM product_reviews WHERE review_id = ? AND user_id = ?");
$review_stmt->bind_param("ii", $review_id, $user_id);
$review_stmt->execute();
$review_result = $review_stmt->get_result();

if ($review_result->num_rows === 0) {
    
    header('Location: ' . BASE_URL . '/user/products/index.php');
    exit();
}

$review = $review_result->fetch_assoc();
$product_id = $product_id ? $product_id : $review['product_id'];
$review_stmt->close();

$delete_stmt = $conn->prepare("DELETE FROM product_reviews WHERE review_id = ? AND user_id = ?");
$delete_stmt->bind_param("ii", $review_id, $user_id);
$delete_stmt->execute();
$delete_stmt->close();

header('Location: ' . BASE_URL . '/user/products/view.php?id=' . $product_id . '&deleted=1');
exit();
?>

