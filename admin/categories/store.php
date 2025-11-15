<?php
include '../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = sanitize($_POST['category_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $parent_category_id = intval($_POST['parent_category_id'] ?? 0);
    $parent_category_id = $parent_category_id > 0 ? $parent_category_id : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($category_name)) {
        $_SESSION['error'] = 'Category name is required.';
        header('Location: create.php');
        exit();
    }
    
    $stmt = $conn->prepare("INSERT INTO categories (category_name, description, parent_category_id, is_active) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $category_name, $description, $parent_category_id, $is_active);
    
    if ($stmt->execute()) {
        $stmt->close();
        header('Location: index.php?success=1');
        exit();
    } else {
        $stmt->close();
        $_SESSION['error'] = 'Failed to add category.';
        header('Location: create.php');
        exit();
    }
} else {
    header('Location: create.php');
    exit();
}
?>
