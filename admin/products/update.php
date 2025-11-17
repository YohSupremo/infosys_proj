<?php
include '../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $product_name = sanitize($_POST['product_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $team_id = intval($_POST['team_id'] ?? 0);
    $team_id = $team_id > 0 ? $team_id : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $categories = $_POST['categories'] ?? [];
    
    if (!$product_id) {
        header('Location: index.php');
        exit();
    }
    
    // Get current product to preserve image_url if not updating
    $get_product = $conn->prepare("SELECT image_url FROM products WHERE product_id = ?");
    $get_product->bind_param("i", $product_id);
    $get_product->execute();
    $product_result = $get_product->get_result();
    if ($product_result->num_rows === 0) {
        $get_product->close();
        header('Location: index.php');
        exit();
    }
    $current_product = $product_result->fetch_assoc();
    $get_product->close();
    
    // Validation
    if (empty($product_name)) {
        $_SESSION['error'] = 'Product name is required.';
        header('Location: edit.php?id=' . $product_id);
        exit();
    }
    
    // Validate price format
    if (empty($_POST['price']) || !is_numeric($_POST['price']) || floatval($_POST['price']) < 0) {
        $_SESSION['error'] = 'Price must be a valid number greater than or equal to 0.';
        header('Location: edit.php?id=' . $product_id);
        exit();
    }
    
    if ($price <= 0) {
        $_SESSION['error'] = 'Price must be greater than 0.';
        header('Location: edit.php?id=' . $product_id);
        exit();
    }
    
    $image_url = $current_product['image_url'];
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../assets/images/products/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed_exts)) {
            $new_filename = uniqid() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Delete old image if exists
                if ($image_url && file_exists('../../' . $image_url)) {
                    unlink('../../' . $image_url);
                }
                $image_url = 'assets/images/products/' . $new_filename;
            }
        }
    }
    
    $update_stmt = $conn->prepare("UPDATE products SET team_id = ?, product_name = ?, description = ?, price = ?, image_url = ?, is_active = ? WHERE product_id = ?");
    $update_stmt->bind_param("issdssi", $team_id, $product_name, $description, $price, $image_url, $is_active, $product_id);
    
    if ($update_stmt->execute()) {
        $update_stmt->close();
        
        // Update categories
        $del_cat = $conn->prepare("DELETE FROM product_categories WHERE product_id = ?");
        $del_cat->bind_param("i", $product_id);
        $del_cat->execute();
        $del_cat->close();
        
        if (!empty($categories)) {
            $cat_stmt = $conn->prepare("INSERT INTO product_categories (product_id, category_id, is_primary) VALUES (?, ?, ?)");
            foreach ($categories as $index => $category_id) {
                $is_primary = $index === 0 ? 1 : 0;
                $cat_stmt->bind_param("iii", $product_id, $category_id, $is_primary);
                $cat_stmt->execute();
            }
            $cat_stmt->close();
        }
        
        // Handle multiple image uploads (MP1 Requirement)
        if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
            $upload_dir = '../../assets/images/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
            
            // Get current max display_order
            $max_order_stmt = $conn->prepare("SELECT MAX(display_order) as max_order FROM product_images WHERE product_id = ?");
            $max_order_stmt->bind_param("i", $product_id);
            $max_order_stmt->execute();
            $max_order_result = $max_order_stmt->get_result();
            $max_order = $max_order_result->fetch_assoc()['max_order'] ?? 0;
            $max_order_stmt->close();
            
            foreach ($_FILES['images']['name'] as $key => $filename) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if (in_array($file_ext, $allowed_exts)) {
                        $new_filename = uniqid() . '.' . $file_ext;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $upload_path)) {
                            $image_path = 'assets/images/products/' . $new_filename;
                            $max_order++;
                            $is_primary = 0; // Don't set new images as primary automatically
                            
                            $img_stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, is_primary, display_order) VALUES (?, ?, ?, ?)");
                            $img_stmt->bind_param("isii", $product_id, $image_path, $is_primary, $max_order);
                            $img_stmt->execute();
                            $img_stmt->close();
                        }
                    }
                }
            }
        }
        
        header('Location: ' . BASE_URL . '/admin/products/index.php?success=1');
        exit();
    } else {
        $update_stmt->close();
        $_SESSION['error'] = 'Failed to update product.';
        header('Location: edit.php?id=' . $product_id);
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}
?>
