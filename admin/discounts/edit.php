<?php
$page_title = 'Edit Discount - Admin';
include '../../config/config.php';
include '../../includes/header.php';
requireAdmin();

$discount_id = intval($_GET['id'] ?? 0);
$error = '';

if (!$discount_id) {
    header('Location: index.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM discount_codes WHERE discount_id = ?");
$stmt->bind_param("i", $discount_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$discount = $result->fetch_assoc();
$stmt->close();

// fetching ng products
$discount_products = [];
$prod_stmt = $conn->prepare("SELECT product_id FROM discount_products WHERE discount_id = ?");
$prod_stmt->bind_param("i", $discount_id);
$prod_stmt->execute();
$prod_result = $prod_stmt->get_result();
while ($p = $prod_result->fetch_assoc()) {
    $discount_products[] = $p['product_id'];
}
$prod_stmt->close();
// this is just for categories and their discounts codes
$discount_categories = [];
$cat_stmt = $conn->prepare("SELECT category_id FROM discount_categories WHERE discount_id = ?");
$cat_stmt->bind_param("i", $discount_id);
$cat_stmt->execute();
$cat_result = $cat_stmt->get_result();
while ($c = $cat_result->fetch_assoc()) {
    $discount_categories[] = $c['category_id'];
}
$cat_stmt->close();

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);

$products_list = $conn->query("SELECT * FROM products WHERE is_active = 1 ORDER BY product_name");
$categories_list = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY category_name");
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <div class="centered-form-wrapper">
        <h2 class="mb-4">Edit Discount</h2>
        <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Discount Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="update.php">
                        <input type="hidden" name="discount_id" value="<?php echo $discount_id; ?>">
                        <div class="mb-3">
                            <label for="code" class="form-label">Discount Code *</label>
                            <input type="text" class="form-control" id="code" name="code" value="<?php echo htmlspecialchars($discount['code']); ?>" placeholder="Enter discount code">
                            <small class="text-muted">Required field</small>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"><?php echo htmlspecialchars($discount['description']); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="discount_type" class="form-label">Discount Type *</label>
                                <select class="form-select" id="discount_type" name="discount_type">
                                    <option value="percentage" <?php echo $discount['discount_type'] === 'percentage' ? 'selected' : ''; ?>>Percentage</option>
                                    <option value="fixed_amount" <?php echo $discount['discount_type'] === 'fixed_amount' ? 'selected' : ''; ?>>Fixed Amount</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="discount_value" class="form-label">Discount Value *</label>
                                <input type="text" class="form-control" id="discount_value" name="discount_value" value="<?php echo $discount['discount_value']; ?>" placeholder="e.g. 10 or 10.50">
                                <small class="text-muted">Enter a number (e.g. 10 for 10% or ₱10.50)</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="min_purchase_amount" class="form-label">Min Purchase Amount</label>
                                <input type="text" class="form-control" id="min_purchase_amount" name="min_purchase_amount" value="<?php echo $discount['min_purchase_amount']; ?>" placeholder="e.g. 100.00">
                                <small class="text-muted">Enter a number (e.g. 100.00)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="max_discount_amount" class="form-label">Max Discount Amount</label>
                                <input type="text" class="form-control" id="max_discount_amount" name="max_discount_amount" value="<?php echo $discount['max_discount_amount'] ?: ''; ?>" placeholder="e.g. 50.00">
                                <small class="text-muted">Enter a number (e.g. 50.00)</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="usage_limit" class="form-label">Usage Limit (0 for unlimited)</label>
                            <input type="text" class="form-control" id="usage_limit" name="usage_limit" value="<?php echo $discount['usage_limit'] ?: 0; ?>" placeholder="e.g. 100">
                            <small class="text-muted">Enter a whole number (0 = unlimited)</small>
                        </div>
                        <div class="mb-3">
                            <label for="applies_to" class="form-label">Applies To *</label>
                            <select class="form-select" id="applies_to" name="applies_to">
                                <option value="all" <?php echo $discount['applies_to'] === 'all' ? 'selected' : ''; ?>>All Products</option>
                                <option value="specific_products" <?php echo $discount['applies_to'] === 'specific_products' ? 'selected' : ''; ?>>Specific Products</option>
                                <option value="specific_categories" <?php echo $discount['applies_to'] === 'specific_categories' ? 'selected' : ''; ?>>Specific Categories</option>
                            </select>
                        </div>
                        <div class="mb-3" id="products_section" style="display: <?php echo $discount['applies_to'] === 'specific_products' ? 'block' : 'none'; ?>;">
                            <label for="products" class="form-label">Select Products</label>
                            <select class="form-select" id="products" name="products[]" multiple>
                                <?php 
                                $products_list->data_seek(0);
                                while ($product = $products_list->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $product['product_id']; ?>" <?php echo in_array($product['product_id'], $discount_products) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($product['product_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3" id="categories_section" style="display: <?php echo $discount['applies_to'] === 'specific_categories' ? 'block' : 'none'; ?>;">
                            <label for="categories" class="form-label">Select Categories</label>
                            <select class="form-select" id="categories" name="categories[]" multiple>
                                <?php 
                                $categories_list->data_seek(0);
                                while ($category = $categories_list->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $category['category_id']; ?>" <?php echo in_array($category['category_id'], $discount_categories) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Start Date *</label>
                                <input type="text" class="form-control" id="start_date" name="start_date" value="<?php echo date('Y-m-d H:i', strtotime($discount['start_date'])); ?>" placeholder="YYYY-MM-DD HH:MM">
                                <small class="text-muted">Format: YYYY-MM-DD HH:MM (e.g. 2025-01-15 10:30)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="expiration_date" class="form-label">Expiration Date</label>
                                <input type="text" class="form-control" id="expiration_date" name="expiration_date" value="<?php echo $discount['expiration_date'] ? date('Y-m-d H:i', strtotime($discount['expiration_date'])) : ''; ?>" placeholder="YYYY-MM-DD HH:MM">
                                <small class="text-muted">Format: YYYY-MM-DD HH:MM (e.g. 2025-12-31 23:59)</small>
                            </div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?php echo $discount['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Discount</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
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

