<?php
$page_title = 'Add Product - Admin';
include '../../config/config.php';
include '../../includes/header.php';
requireAdmin();

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);

// Get teams
$teams = $conn->query("SELECT * FROM nba_teams ORDER BY team_name");

// Get categories
$categories = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY category_name");
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Add New Product</h2>
    
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
                    
                    <form method="POST" action="store.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="product_name" class="form-label">Product Name *</label>
                            <input type="text" class="form-control" id="product_name" name="product_name" value="<?php echo htmlspecialchars($_POST['product_name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Price *</label>
                                <input type="text" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" placeholder="e.g. 99.99">
                                <small class="text-muted">Enter a number (e.g. 99.99)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="stock_quantity" class="form-label">Stock Quantity *</label>
                                <input type="text" class="form-control" id="stock_quantity" name="stock_quantity" value="<?php echo htmlspecialchars($_POST['stock_quantity'] ?? ''); ?>" placeholder="e.g. 100">
                                <small class="text-muted">Enter a whole number (e.g. 100)</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="team_id" class="form-label">Team</label>
                            <select class="form-select" id="team_id" name="team_id">
                                <option value="0">No Team</option>
                                <?php while ($team = $teams->fetch_assoc()): ?>
                                    <option value="<?php echo $team['team_id']; ?>"><?php echo htmlspecialchars($team['team_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="categories" class="form-label">Categories</label>
                            <select class="form-select" id="categories" name="categories[]" multiple>
                                <?php while ($category = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['category_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">Primary Product Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <small class="text-muted">This will be stored in the products table for backward compatibility</small>
                            <div id="imagePreview" class="image-upload-preview mt-2" style="display: none;">
                                <img id="previewImg" src="" alt="Preview" style="max-width: 200px;">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="images" class="form-label">Additional Product Images (MP1 - Multiple Photos)</label>
                            <input type="file" class="form-control" id="images" name="images[]" accept="image/*" multiple>
                            <small class="text-muted">You can select multiple images. The first image will be set as primary.</small>
                            <div id="imagesPreview" class="mt-2"></div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                        <a href="<?php echo BASE_URL; ?>/admin/products/index.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Single image preview
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

// Multiple images preview
document.getElementById('images').addEventListener('change', function(e) {
    const preview = document.getElementById('imagesPreview');
    preview.innerHTML = '';
    
    if (e.target.files.length > 0) {
        Array.from(e.target.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'd-inline-block me-2 mb-2';
                div.innerHTML = '<img src="' + e.target.result + '" style="max-width: 100px; max-height: 100px; object-fit: cover;" class="img-thumbnail"><br><small>Image ' + (index + 1) + '</small>';
                preview.appendChild(div);
            }
            reader.readAsDataURL(file);
        });
    }
});
</script>

<?php include '../../includes/foot.php'; ?>

