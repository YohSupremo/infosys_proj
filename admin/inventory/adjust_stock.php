<?php
$page_title = 'Adjust Stock - Admin';
include '../../includes/header.php';
include '../../config/config.php';
requireAdminOrInventoryManager();

$error = '';
$success = '';
$product_id = intval($_GET['product_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity_change = intval($_POST['quantity_change'] ?? 0);
    $adjustment_notes = sanitize($_POST['adjustment_notes'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    // Server-side validation
    if ($product_id <= 0) {
        $error = 'Please select a product.';
    } elseif ($quantity_change == 0) {
        $error = 'Quantity change cannot be zero.';
    } else {
        // Get current stock
        $stock_stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
        $stock_stmt->bind_param("i", $product_id);
        $stock_stmt->execute();
        $stock_result = $stock_stmt->get_result();
        
        if ($stock_result->num_rows === 0) {
            $error = 'Product not found.';
        } else {
            $current_stock = $stock_result->fetch_assoc()['stock_quantity'];
            $new_stock = $current_stock + $quantity_change;
            
            // Prevent negative stock
            if ($new_stock < 0) {
                $error = 'Cannot adjust stock below zero. Current stock: ' . $current_stock;
            } else {
                // Update stock
                $update_stmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
                $update_stmt->bind_param("ii", $new_stock, $product_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Record inventory history with adjustment type
                $inv_stmt = $conn->prepare("INSERT INTO inventory_history (product_id, transaction_type, quantity_change, previous_stock, new_stock, reference_id, reference_type, created_by, notes) VALUES (?, 'adjustment', ?, ?, ?, NULL, 'adjustment', ?, ?)");
                $inv_stmt->bind_param("iiiiis", $product_id, $quantity_change, $current_stock, $new_stock, $user_id, $adjustment_notes);
                $inv_stmt->execute();
                $inv_stmt->close();
                
                $success = 'Stock adjusted successfully!';
                header('Location: ' . BASE_URL . '/admin/inventory/index.php?success=1');
                exit();
            }
        }
        $stock_stmt->close();
    }
}

// Get product info if product_id is provided
$product = null;
if ($product_id > 0) {
    $product_stmt = $conn->prepare("SELECT product_id, product_name, stock_quantity FROM products WHERE product_id = ?");
    $product_stmt->bind_param("i", $product_id);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();
    if ($product_result->num_rows > 0) {
        $product = $product_result->fetch_assoc();
    }
    $product_stmt->close();
}

$products_list = $conn->query("SELECT * FROM products WHERE is_active = 1 ORDER BY product_name");
?>

<?php
if (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Inventory Manager') {
    include '../../includes/inventory_navbar.php';
} else {
    include '../../includes/admin_navbar.php';
}
?>

<div class="container my-5">
    <h2 class="mb-4">Adjust Stock</h2>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Stock Adjustment</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="product_id" class="form-label">Product *</label>
                            <select class="form-select" id="product_id" name="product_id" onchange="updateStockInfo()">
                                <option value="0">Select Product</option>
                                <?php 
                                $products_list->data_seek(0);
                                while ($prod = $products_list->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $prod['product_id']; ?>" 
                                            data-stock="<?php echo $prod['stock_quantity']; ?>"
                                            <?php echo ($product_id == $prod['product_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prod['product_name']); ?> (Current Stock: <?php echo $prod['stock_quantity']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Current Stock</label>
                            <div class="form-control" id="currentStockDisplay">
                                <?php echo $product ? $product['stock_quantity'] : 'Select a product'; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="quantity_change" class="form-label">Quantity Change *</label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary" onclick="setQuantityChange(-1)">-1</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setQuantityChange(-5)">-5</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setQuantityChange(-10)">-10</button>
                                <input type="number" class="form-control text-center" id="quantity_change" name="quantity_change" 
                                       value="<?php echo htmlspecialchars($_POST['quantity_change'] ?? ''); ?>" 
                                       placeholder="Enter quantity change (negative to reduce)">
                                <button type="button" class="btn btn-outline-secondary" onclick="setQuantityChange(10)">+10</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setQuantityChange(5)">+5</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setQuantityChange(1)">+1</button>
                            </div>
                            <small class="text-muted">Use negative numbers to reduce stock, positive to increase</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="adjustment_notes" class="form-label">Adjustment Notes</label>
                            <textarea class="form-control" id="adjustment_notes" name="adjustment_notes" rows="3" placeholder="Reason for adjustment (e.g., damaged items, found stock, etc.)"><?php echo htmlspecialchars($_POST['adjustment_notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">New Stock (Preview)</label>
                            <div class="form-control" id="newStockDisplay">
                                <?php 
                                if ($product && isset($_POST['quantity_change'])) {
                                    $preview = $product['stock_quantity'] + intval($_POST['quantity_change']);
                                    echo $preview >= 0 ? $preview : 'Invalid (would be negative)';
                                } else {
                                    echo 'Enter quantity change';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Apply Adjustment</button>
                        <a href="<?php echo BASE_URL; ?>/admin/inventory/index.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateStockInfo() {
    const select = document.getElementById('product_id');
    const selectedOption = select.options[select.selectedIndex];
    const currentStock = selectedOption.getAttribute('data-stock') || '0';
    document.getElementById('currentStockDisplay').textContent = currentStock;
    updateNewStockPreview();
}

function setQuantityChange(value) {
    const input = document.getElementById('quantity_change');
    const current = parseInt(input.value) || 0;
    input.value = current + value;
    updateNewStockPreview();
}

function updateNewStockPreview() {
    const select = document.getElementById('product_id');
    const quantityInput = document.getElementById('quantity_change');
    const selectedOption = select.options[select.selectedIndex];
    const currentStock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
    const quantityChange = parseInt(quantityInput.value) || 0;
    const newStock = currentStock + quantityChange;
    
    const preview = document.getElementById('newStockDisplay');
    if (newStock < 0) {
        preview.textContent = 'Invalid (would be negative)';
        preview.style.color = 'red';
    } else {
        preview.textContent = newStock;
        preview.style.color = '';
    }
}

// Update preview when quantity changes
document.getElementById('quantity_change').addEventListener('input', updateNewStockPreview);
document.getElementById('product_id').addEventListener('change', updateStockInfo);

// Initialize on page load
updateStockInfo();
</script>

<?php include '../../includes/foot.php'; ?>

