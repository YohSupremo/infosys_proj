<?php
include '../../config/config.php';
requireAdmin();

$image_id = intval($_GET['image_id'] ?? 0);
$product_id = intval($_GET['product_id'] ?? 0);

if (!$image_id || !$product_id) {
    header('Location: ' . BASE_URL . '/admin/products/index.php');
    exit();
}

// kunin image
$img_stmt = $conn->prepare("SELECT image_url FROM product_images WHERE image_id = ? AND product_id = ?");
$img_stmt->bind_param("ii", $image_id, $product_id);
$img_stmt->execute();
$img_result = $img_stmt->get_result();

if ($img_result->num_rows > 0) {
    $image = $img_result->fetch_assoc();
    
    //remove from db
    $delete_stmt = $conn->prepare("DELETE FROM product_images WHERE image_id = ? AND product_id = ?");
    $delete_stmt->bind_param("ii", $image_id, $product_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    // Delete file
    if ($image['image_url'] && file_exists('../../' . $image['image_url'])) {
        unlink('../../' . $image['image_url']);
    }
}

$img_stmt->close();

header('Location: ' . BASE_URL . '/admin/products/edit.php?id=' . $product_id);
exit();
?>

