<?php
include '../../config/config.php';
requireAdmin();
// logic to update category details
// data is from edit.php form method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = intval($_POST['category_id'] ?? 0);
    $category_name = sanitize($_POST['category_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $parent_category_id = intval($_POST['parent_category_id'] ?? 0);
    $parent_category_id = ($parent_category_id > 0 && $parent_category_id != $category_id) ? $parent_category_id : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (!$category_id) {
        header('Location: index.php');
        exit();
    }
    
    if (empty($category_name)) {
        $_SESSION['error'] = 'Category name is required.';
        header('Location: edit.php?id=' . $category_id);
        exit();
    }
    
    $update_stmt = $conn->prepare("UPDATE categories SET category_name = ?, description = ?, parent_category_id = ?, is_active = ? WHERE category_id = ?");
    $update_stmt->bind_param("ssiii", $category_name, $description, $parent_category_id, $is_active, $category_id);
    
    if ($update_stmt->execute()) {
        $update_stmt->close();
        header('Location: index.php?success=1');
        exit();
    } else {
        $update_stmt->close();
        $_SESSION['error'] = 'Failed to update category.';
        header('Location: edit.php?id=' . $category_id);
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}
?>
