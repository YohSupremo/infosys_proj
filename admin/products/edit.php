<?php
$page_title = 'Edit Product - Admin';
include '../../includes/header.php';
include '../../config/config.php';
requireAdmin();

$product_id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

if (!$product_id) {
    header('Location: index.php');
    exit();
}

// Get product
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$product = $result->fetch_assoc();
$stmt->close();

// Get product categories
$product_categories = [];
$cat_stmt = $conn->prepare("SELECT category_id FROM product_categories WHERE product_id = ?");
$cat_stmt->bind_param("i", $product_id);
$cat_stmt->execute();
$cat_result = $cat_stmt->get_result();
while ($cat = $cat_result->fetch_assoc()) {
    $product_categories[] = $cat['category_id'];
}
$cat_stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = sanitize($_POST['product_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
    $team_id = intval($_POST['team_id'] ?? 0);
    $team_id = $team_id > 0 ? $team_id : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $categories = $_POST['categories'] ?? [];
    
    if (empty($product_name) || $price <= 0) {
        $error = 'Product name and price are required.';
    } else {
        $image_url = $product['image_url'];
        
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
        
        $update_stmt = $conn->prepare("UPDATE products SET team_id = ?, product_name = ?, description = ?, price = ?, stock_quantity = ?, image_url = ?, is_active = ? WHERE product_id = ?");
        $update_stmt->bind_param("issdissi", $team_id, $product_name, $description, $price, $stock_quantity, $image_url, $is_active, $product_id);
        
        if ($update_stmt->execute()) {
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
            
            $success = 'Product updated successfully!';
            header('Location: index.php?success=1');
            exit();
        } else {
            $error = 'Failed to update product.';
        }
        $update_stmt->close();
    }
}

// Get teams
$teams = $conn->query("SELECT * FROM nba_teams ORDER BY team_name");

// Get categories
$categories = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY category_name");
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Edit Product</h2>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Product Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="product_name" class="form-label">Product Name *</label>
                            <input type="text" class="form-control" id="product_name" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Price *</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="<?php echo $product['price']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="stock_quantity" class="form-label">Stock Quantity *</label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" min="0" value="<?php echo $product['stock_quantity']; ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="team_id" class="form-label">Team</label>
                            <select class="form-select" id="team_id" name="team_id">
                                <option value="0">No Team</option>
                                <?php 
                                $teams->data_seek(0);
                                while ($team = $teams->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $team['team_id']; ?>" <?php echo ($product['team_id'] == $team['team_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($team['team_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="categories" class="form-label">Categories</label>
                            <select class="form-select" id="categories" name="categories[]" multiple>
                                <?php 
                                $categories->data_seek(0);
                                while ($category = $categories->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $category['category_id']; ?>" <?php echo in_array($category['category_id'], $product_categories) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">Product Image</label>
                            <?php if ($product['image_url']): ?>
                                <div class="mb-2">
                                    <img src="../../<?php echo htmlspecialchars($product['image_url']); ?>" style="max-width: 200px;" class="img-thumbnail">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <div id="imagePreview" class="image-upload-preview mt-2" style="display: none;">
                                <img id="previewImg" src="" alt="Preview">
                            </div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Product</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php include '../../includes/foot.php'; ?>

