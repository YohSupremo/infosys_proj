<?php
$page_title = 'Edit Product - Admin';
include '../../config/config.php';
include '../../includes/header.php';
requireAdmin();
// same as the other edits
$product_id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

if (!$product_id) {
    header('Location: index.php');
    exit();
}

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

// categories
$product_categories = [];
$cat_stmt = $conn->prepare("SELECT category_id FROM product_categories WHERE product_id = ?");
$cat_stmt->bind_param("i", $product_id);
$cat_stmt->execute();
$cat_result = $cat_stmt->get_result();
while ($cat = $cat_result->fetch_assoc()) {
    $product_categories[] = $cat['category_id'];
}
$cat_stmt->close();

// images
$images_stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY display_order ASC, is_primary DESC");
$images_stmt->bind_param("i", $product_id);
$images_stmt->execute();
$images_result = $images_stmt->get_result();
$product_images = [];
while ($img = $images_result->fetch_assoc()) {
    $product_images[] = $img;
}
$images_stmt->close();

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);

// teams
$teams = $conn->query("SELECT * FROM nba_teams ORDER BY team_name");

// categories
$categories = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY category_name");
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <div class="centered-form-wrapper">
        <h2 class="mb-4">Edit Product</h2>
        <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Product Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="update.php" enctype="multipart/form-data">
                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                        <div class="mb-3">
                            <label for="product_name" class="form-label">Product Name *</label>
                            <input type="text" class="form-control" id="product_name" name="product_name" value="<?php echo ($product['product_name']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo ($product['description']); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Price *</label>
                                <input type="text" class="form-control" id="price" name="price" value="<?php echo $product['price']; ?>" placeholder="e.g. 99.99">
                                <small class="text-muted">Enter a number (e.g. 99.99)</small>
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
                            <label for="image" class="form-label">Primary Product Image</label>
                            <?php if ($product['image_url']): ?>
                                <div class="mb-2">
                                    <img src="../../<?php echo htmlspecialchars($product['image_url']); ?>" style="max-width: 200px;" class="img-thumbnail">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <small class="text-muted">This updates the main product image</small>
                            <div id="imagePreview" class="image-upload-preview mt-2" style="display: none;">
                                <img id="previewImg" src="" alt="Preview" style="max-width: 200px;">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Existing Product Images (MP1 - Multiple Photos)</label>
                            <?php if (count($product_images) > 0): ?>
                                <div class="row mb-3">
                                    <?php foreach ($product_images as $img): ?>
                                        <div class="col-md-3 mb-2">
                                            <div class="position-relative">
                                                <img src="../../<?php echo htmlspecialchars($img['image_url']); ?>" class="img-thumbnail" style="width: 100%; height: 150px; object-fit: cover;">
                                                <?php if ($img['is_primary']): ?>
                                                    <span class="badge bg-primary position-absolute top-0 start-0 m-1">Primary</span>
                                                <?php endif; ?>
                                                <a href="<?php echo BASE_URL; ?>/admin/products/delete_image.php?image_id=<?php echo $img['image_id']; ?>&product_id=<?php echo $product_id; ?>" 
                                                   class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" 
                                                   onclick="return confirm('Delete this image?')">×</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No additional images. Upload images below.</p>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="images" class="form-label">Add More Product Images</label>
                            <input type="file" class="form-control" id="images" name="images[]" accept="image/*" multiple>
                            <small class="text-muted">You can select multiple images to add</small>
                            <div id="imagesPreview" class="mt-2"></div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Product</button>
                        <a href="<?php echo BASE_URL; ?>/admin/products/index.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
        </div>
    </div>
</div>

<script>
// preview img-thumbnail
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

// Mpreview multiple images
document.getElementById('images').addEventListener('change', function(e) {
    const preview = document.getElementById('imagesPreview');
    preview.innerHTML = '';
    
    if (e.target.files.length > 0) {
        Array.from(e.target.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'd-inline-block me-2 mb-2';
                div.innerHTML = '<img src="' + e.target.result + '" style="max-width: 100px; max-height: 100px; object-fit: cover;" class="img-thumbnail"><br><small>New Image ' + (index + 1) + '</small>';
                preview.appendChild(div);
            }
            reader.readAsDataURL(file);
        });
    }
});
</script>

<?php include '../../includes/foot.php'; ?>

