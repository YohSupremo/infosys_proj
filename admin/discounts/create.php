<?php
$page_title = 'Add Discount - Admin';
include '../../config/config.php';
include '../../includes/header.php';
requireAdmin();
//flash msg lang 
//as always this is UI
// form method here is for store as always
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);

$products_list = $conn->query("SELECT * FROM products WHERE is_active = 1 ORDER BY product_name");
$categories_list = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY category_name");
?>

<?php include '../../includes/admin_navbar.php'; 
  ?>

<div class="container my-5">
    <div class="centered-form-wrapper">
        <h2 class="mb-4">Add New Discount</h2>
        <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Discount Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="store.php">
                        <div class="mb-3">
                            <label for="code" class="form-label">Discount Code *</label>
                            <input type="text" class="form-control" id="code" name="code" placeholder="Enter discount code">
                            <small class="text-muted">Required field</small>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="discount_type" class="form-label">Discount Type *</label>
                                <select class="form-select" id="discount_type" name="discount_type">
                                    <option value="percentage">Percentage</option>
                                    <option value="fixed_amount">Fixed Amount</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="discount_value" class="form-label">Discount Value *</label>
                                <input type="text" class="form-control" id="discount_value" name="discount_value" placeholder="e.g. 10 or 10.50">
                                <small class="text-muted">Enter a number (e.g. 10 for 10% or ₱10.50)</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="min_purchase_amount" class="form-label">Min Purchase Amount</label>
                                <input type="text" class="form-control" id="min_purchase_amount" name="min_purchase_amount" value="0" placeholder="e.g. 100.00">
                                <small class="text-muted">Enter a number (e.g. 100.00)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="max_discount_amount" class="form-label">Max Discount Amount</label>
                                <input type="text" class="form-control" id="max_discount_amount" name="max_discount_amount" placeholder="e.g. 50.00">
                                <small class="text-muted">Enter a number (e.g. 50.00)</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="usage_limit" class="form-label">Usage Limit (0 for unlimited)</label>
                            <input type="text" class="form-control" id="usage_limit" name="usage_limit" value="0" placeholder="e.g. 100">
                            <small class="text-muted">Enter a whole number (0 = unlimited)</small>
                        </div>
                        <div class="mb-3">
                            <label for="applies_to" class="form-label">Applies To *</label>
                            <select class="form-select" id="applies_to" name="applies_to">
                                <option value="all">All Products</option>
                                <option value="specific_products">Specific Products</option>
                                <option value="specific_categories">Specific Categories</option>
                            </select>
                        </div>
                        <div class="mb-3" id="products_section" style="display: none;">
                            <label for="products" class="form-label">Select Products</label>
                            <select class="form-select" id="products" name="products[]" multiple>
                                <?php while ($product = $products_list->fetch_assoc()): ?>
                                    <option value="<?php echo $product['product_id']; ?>"><?php echo htmlspecialchars($product['product_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3" id="categories_section" style="display: none;">
                            <label for="categories" class="form-label">Select Categories</label>
                            <select class="form-select" id="categories" name="categories[]" multiple>
                                <?php while ($category = $categories_list->fetch_assoc()): ?>
                                    <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['category_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Start Date *</label>
                                <input type="text" class="form-control" id="start_date" name="start_date" placeholder="YYYY-MM-DD HH:MM">
                                <small class="text-muted">Format: YYYY-MM-DD HH:MM (e.g. 2025-01-15 10:30)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="expiration_date" class="form-label">Expiration Date</label>
                                <input type="text" class="form-control" id="expiration_date" name="expiration_date" placeholder="YYYY-MM-DD HH:MM">
                                <small class="text-muted">Format: YYYY-MM-DD HH:MM (e.g. 2025-12-31 23:59)</small>
                            </div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Discount</button>
                        <a href="<?php echo BASE_URL; ?>/admin/discounts/index.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
        </div>
    </div>
</div>

<script>
document.getElementById('applies_to').addEventListener('change', function() {
    const appliesTo = this.value;
    document.getElementById('products_section').style.display = appliesTo === 'specific_products' ? 'block' : 'none';
    document.getElementById('categories_section').style.display = appliesTo === 'specific_categories' ? 'block' : 'none';
});
</script>

<?php include '../../includes/foot.php'; ?>

