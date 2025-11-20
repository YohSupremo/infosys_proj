<?php
include '../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $discount_id = intval($_POST['discount_id'] ?? 0);
    $code = sanitize($_POST['code'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $discount_type = sanitize($_POST['discount_type'] ?? '');
    $discount_value = floatval($_POST['discount_value'] ?? 0);
    $min_purchase_amount = floatval($_POST['min_purchase_amount'] ?? 0);
    $max_discount_amount = floatval($_POST['max_discount_amount'] ?? 0);
    $max_discount_amount = $max_discount_amount > 0 ? $max_discount_amount : null;
    $usage_limit = intval($_POST['usage_limit'] ?? 0);
    $usage_limit = $usage_limit > 0 ? $usage_limit : null;
    $applies_to = sanitize($_POST['applies_to'] ?? 'all');
    $start_date = sanitize($_POST['start_date'] ?? '');
    $expiration_date = sanitize($_POST['expiration_date'] ?? '');
    $expiration_date = !empty($expiration_date) ? $expiration_date : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $products = $_POST['products'] ?? [];
    $categories = $_POST['categories'] ?? [];
    
    if (!$discount_id) {
        header('Location: index.php');
        exit();
    }
    
    if (empty($code) || empty($discount_type)) {
        $_SESSION['error'] = 'Code and discount type are required.';
        header('Location: edit.php?id=' . $discount_id);
        exit();
    }
    
    if (empty($_POST['discount_value']) || !is_numeric($_POST['discount_value']) || floatval($_POST['discount_value']) < 0) {
        $_SESSION['error'] = 'Discount value must be a valid number greater than or equal to 0.';
        header('Location: edit.php?id=' . $discount_id);
        exit();
    }
    
    if (empty($start_date)) {
        $_SESSION['error'] = 'Start date is required.';
        header('Location: edit.php?id=' . $discount_id);
        exit();
    }
    
    $datePattern = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/';
    if (!preg_match($datePattern, $start_date)) {
        $_SESSION['error'] = 'Start date must be in format: YYYY-MM-DD HH:MM (e.g. 2025-01-15 10:30)';
        header('Location: edit.php?id=' . $discount_id);
        exit();
    }
    
    if (!empty($expiration_date) && !preg_match($datePattern, $expiration_date)) {
        $_SESSION['error'] = 'Expiration date must be in format: YYYY-MM-DD HH:MM (e.g. 2025-12-31 23:59)';
        header('Location: edit.php?id=' . $discount_id);
        exit();
    }
    
    if (!empty($_POST['min_purchase_amount']) && (!is_numeric($_POST['min_purchase_amount']) || floatval($_POST['min_purchase_amount']) < 0)) {
        $_SESSION['error'] = 'Min purchase amount must be a valid number greater than or equal to 0.';
        header('Location: edit.php?id=' . $discount_id);
        exit();
    }
    
    if (!empty($_POST['max_discount_amount']) && (!is_numeric($_POST['max_discount_amount']) || floatval($_POST['max_discount_amount']) < 0)) {
        $_SESSION['error'] = 'Max discount amount must be a valid number greater than or equal to 0.';
        header('Location: edit.php?id=' . $discount_id);
        exit();
    }
    
    if (!empty($_POST['usage_limit']) && (!is_numeric($_POST['usage_limit']) || intval($_POST['usage_limit']) < 0)) {
        $_SESSION['error'] = 'Usage limit must be a valid whole number greater than or equal to 0.';
        header('Location: edit.php?id=' . $discount_id);
        exit();
    }
    
    if ($discount_value <= 0) {
        $_SESSION['error'] = 'Discount value must be greater than 0.';
        header('Location: edit.php?id=' . $discount_id);
        exit();
    }
    
    $check_stmt = $conn->prepare("SELECT discount_id FROM discount_codes WHERE code = ? AND discount_id != ?");
    $check_stmt->bind_param("si", $code, $discount_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $check_stmt->close();
        $_SESSION['error'] = 'Discount code already exists.';
        header('Location: edit.php?id=' . $discount_id);
        exit();
    }
    $check_stmt->close();
    
    $update_stmt = $conn->prepare("UPDATE discount_codes SET code = ?, description = ?, discount_type = ?, discount_value = ?, min_purchase_amount = ?, max_discount_amount = ?, usage_limit = ?, applies_to = ?, start_date = ?, expiration_date = ?, is_active = ? WHERE discount_id = ?");
    $update_stmt->bind_param("sssdddisssii", $code, $description, $discount_type, $discount_value, $min_purchase_amount, $max_discount_amount, $usage_limit, $applies_to, $start_date, $expiration_date, $is_active, $discount_id);
    
    if ($update_stmt->execute()) {
        $update_stmt->close();
        
        $del_prod = $conn->prepare("DELETE FROM discount_products WHERE discount_id = ?");
        $del_prod->bind_param("i", $discount_id);
        $del_prod->execute();
        $del_prod->close();
        
        if ($applies_to === 'specific_products' && !empty($products)) {
            $prod_stmt = $conn->prepare("INSERT INTO discount_products (discount_id, product_id) VALUES (?, ?)");
            foreach ($products as $product_id) {
                $prod_stmt->bind_param("ii", $discount_id, $product_id);
                $prod_stmt->execute();
            }
            $prod_stmt->close();
        }
        
        $del_cat = $conn->prepare("DELETE FROM discount_categories WHERE discount_id = ?");
        $del_cat->bind_param("i", $discount_id);
        $del_cat->execute();
        $del_cat->close();
        
        if ($applies_to === 'specific_categories' && !empty($categories)) {
            $cat_stmt = $conn->prepare("INSERT INTO discount_categories (discount_id, category_id) VALUES (?, ?)");
            foreach ($categories as $category_id) {
                $cat_stmt->bind_param("ii", $discount_id, $category_id);
                $cat_stmt->execute();
            }
            $cat_stmt->close();
        }
        
        header('Location: index.php?success=1');
        exit();
    } else {
        $update_stmt->close();
        $_SESSION['error'] = 'Failed to update discount.';
        header('Location: edit.php?id=' . $discount_id);
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}
?>
