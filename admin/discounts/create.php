<?php
$page_title = 'Add Discount - Admin';
include '../../includes/header.php';
include '../../config/config.php';
requireAdmin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    
    if (empty($code) || empty($discount_type) || $discount_value <= 0) {
        $error = 'Code, discount type, and value are required.';
    } else {
        $check_stmt = $conn->prepare("SELECT discount_id FROM discount_codes WHERE code = ?");
        $check_stmt->bind_param("s", $code);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = 'Discount code already exists.';
        } else {
            $stmt = $conn->prepare("INSERT INTO discount_codes (code, description, discount_type, discount_value, min_purchase_amount, max_discount_amount, usage_limit, applies_to, start_date, expiration_date, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdddissis", $code, $description, $discount_type, $discount_value, $min_purchase_amount, $max_discount_amount, $usage_limit, $applies_to, $start_date, $expiration_date, $is_active);
            
            if ($stmt->execute()) {
                $discount_id = $conn->insert_id;
                
                // Add products if applies to specific products
                if ($applies_to === 'specific_products' && !empty($products)) {
                    $prod_stmt = $conn->prepare("INSERT INTO discount_products (discount_id, product_id) VALUES (?, ?)");
                    foreach ($products as $product_id) {
                        $prod_stmt->bind_param("ii", $discount_id, $product_id);
                        $prod_stmt->execute();
                    }
                    $prod_stmt->close();
                }
                
                // Add categories if applies to specific categories
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
                $error = 'Failed to add discount.';
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}

$products_list = $conn->query("SELECT * FROM products WHERE is_active = 1 ORDER BY product_name");
$categories_list = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY category_name");
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Add New Discount</h2>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Discount Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="code" class="form-label">Discount Code *</label>
                            <input type="text" class="form-control" id="code" name="code" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="discount_type" class="form-label">Discount Type *</label>
                                <select class="form-select" id="discount_type" name="discount_type" required>
                                    <option value="percentage">Percentage</option>
                                    <option value="fixed_amount">Fixed Amount</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="discount_value" class="form-label">Discount Value *</label>
                                <input type="number" class="form-control" id="discount_value" name="discount_value" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="min_purchase_amount" class="form-label">Min Purchase Amount</label>
                                <input type="number" class="form-control" id="min_purchase_amount" name="min_purchase_amount" step="0.01" min="0" value="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="max_discount_amount" class="form-label">Max Discount Amount</label>
                                <input type="number" class="form-control" id="max_discount_amount" name="max_discount_amount" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="usage_limit" class="form-label">Usage Limit (0 for unlimited)</label>
                            <input type="number" class="form-control" id="usage_limit" name="usage_limit" min="0" value="0">
                        </div>
                        <div class="mb-3">
                            <label for="applies_to" class="form-label">Applies To *</label>
                            <select class="form-select" id="applies_to" name="applies_to" required>
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
                                <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="expiration_date" class="form-label">Expiration Date</label>
                                <input type="datetime-local" class="form-control" id="expiration_date" name="expiration_date">
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
</div>

<script>
document.getElementById('applies_to').addEventListener('change', function() {
    const appliesTo = this.value;
    document.getElementById('products_section').style.display = appliesTo === 'specific_products' ? 'block' : 'none';
    document.getElementById('categories_section').style.display = appliesTo === 'specific_categories' ? 'block' : 'none';
});
</script>

<?php include '../../includes/foot.php'; ?>

