<?php
include '../../config/config.php';
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = sanitize($_POST['product_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $team_id = intval($_POST['team_id'] ?? 0);
    $team_id = $team_id > 0 ? $team_id : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $categories = $_POST['categories'] ?? [];
    
    if (empty($product_name)) {
        $_SESSION['error'] = 'Product name is required.';
        header('Location: create.php');
        exit();
    }

    if (empty($_POST['price']) || !is_numeric($_POST['price']) || floatval($_POST['price']) < 0) {
        $_SESSION['error'] = 'Price must be a valid number greater than or equal to 0.';
        header('Location: create.php');
        exit();
    }
    
    if ($price <= 0) {
        $_SESSION['error'] = 'Price must be greater than 0.';
        header('Location: create.php');
        exit();
    }
    
    $image_url = null;
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
                $image_url = 'assets/images/products/' . $new_filename;
            }
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO products (team_id, product_name, description, price, image_url, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdsi", $team_id, $product_name, $description, $price, $image_url, $is_active);
    
    if ($stmt->execute()) {
        $product_id = $conn->insert_id;
        $stmt->close();
        
        if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
            $upload_dir = '../../assets/images/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
            $display_order = 0;
            
            foreach ($_FILES['images']['name'] as $key => $filename) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if (in_array($file_ext, $allowed_exts)) {
                        $new_filename = uniqid() . '.' . $file_ext;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $upload_path)) {
                            $image_path = 'assets/images/products/' . $new_filename;
                            $is_primary = ($display_order === 0) ? 1 : 0;
                            
                            $img_stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, is_primary, display_order) VALUES (?, ?, ?, ?)");
                            $img_stmt->bind_param("isii", $product_id, $image_path, $is_primary, $display_order);
                            $img_stmt->execute();
                            $img_stmt->close();
                            
                            $display_order++;
                        }
                    }
                }
            }
        }
        
        if (!empty($categories)) {
            $cat_stmt = $conn->prepare("INSERT INTO product_categories (product_id, category_id, is_primary) VALUES (?, ?, ?)");
            foreach ($categories as $index => $category_id) {
                $is_primary = $index === 0 ? 1 : 0;
                $cat_stmt->bind_param("iii", $product_id, $category_id, $is_primary);
                $cat_stmt->execute();
            }
            $cat_stmt->close();
        }
        
        header('Location: ' . BASE_URL . '/admin/products/index.php?success=1');
        exit();
    } else {
        $stmt->close();
        $_SESSION['error'] = 'Failed to add product.';
        header('Location: create.php');
        exit();
    }
} else {
    header('Location: create.php');
    exit();
}
?>
