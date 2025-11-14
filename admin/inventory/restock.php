<?php
$page_title = 'Restock Products - Admin';
include '../../includes/header.php';
include '../../config/config.php';
requireAdminOrInventoryManager();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = intval($_POST['supplier_id'] ?? 0);
    $products = $_POST['products'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $costs = $_POST['costs'] ?? [];
    $manager_id = $_SESSION['user_id'];
    $notes = sanitize($_POST['notes'] ?? '');
    
    if ($supplier_id <= 0 || empty($products)) {
        $error = 'Please select a supplier and at least one product.';
    } else {
        $total_cost = 0;
        $restock_items = [];
        
        foreach ($products as $index => $product_id) {
            if (!empty($quantities[$index]) && !empty($costs[$index])) {
                $quantity = intval($quantities[$index]);
                $cost = floatval($costs[$index]);
                $subtotal = $quantity * $cost;
                $total_cost += $subtotal;
                $restock_items[] = [
                    'product_id' => intval($product_id),
                    'quantity' => $quantity,
                    'cost_per_unit' => $cost,
                    'subtotal' => $subtotal
                ];
            }
        }
        
        if (empty($restock_items)) {
            $error = 'Please add at least one product with quantity and cost.';
        } else {
            // Create restocking transaction
            $restock_stmt = $conn->prepare("INSERT INTO restocking_transactions (supplier_id, manager_id, total_cost, notes) VALUES (?, ?, ?, ?)");
            $restock_stmt->bind_param("iids", $supplier_id, $manager_id, $total_cost, $notes);
            $restock_stmt->execute();
            $restock_id = $conn->insert_id;
            $restock_stmt->close();
            
            // Add restocking items and update stock
            foreach ($restock_items as $item) {
                // Add restocking item
                $item_stmt = $conn->prepare("INSERT INTO restocking_items (restock_id, product_id, quantity, cost_per_unit, subtotal) VALUES (?, ?, ?, ?, ?)");
                $item_stmt->bind_param("iiidd", $restock_id, $item['product_id'], $item['quantity'], $item['cost_per_unit'], $item['subtotal']);
                $item_stmt->execute();
                $item_stmt->close();
                
                // Get current stock
                $stock_stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
                $stock_stmt->bind_param("i", $item['product_id']);
                $stock_stmt->execute();
                $stock_result = $stock_stmt->get_result();
                $current_stock = $stock_result->fetch_assoc()['stock_quantity'];
                $stock_stmt->close();
                
                // Update stock
                $new_stock = $current_stock + $item['quantity'];
                $update_stmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
                $update_stmt->bind_param("ii", $new_stock, $item['product_id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Record inventory history
                $inv_stmt = $conn->prepare("INSERT INTO inventory_history (product_id, transaction_type, quantity_change, previous_stock, new_stock, reference_id, reference_type, created_by) VALUES (?, 'restock', ?, ?, ?, ?, 'restock', ?)");
                $inv_stmt->bind_param("iiiiii", $item['product_id'], $item['quantity'], $current_stock, $new_stock, $restock_id, $manager_id);
                $inv_stmt->execute();
                $inv_stmt->close();
            }
            
            $success = 'Restocking completed successfully!';
            header('Location: index.php?success=1');
            exit();
        }
    }
}

$suppliers = $conn->query("SELECT * FROM suppliers WHERE is_active = 1 ORDER BY supplier_name");
$products_list = $conn->query("SELECT * FROM products WHERE is_active = 1 ORDER BY product_name");
$product_id = intval($_GET['product_id'] ?? 0);
?>

<?php
if (isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'Inventory Manager') {
	include '../../includes/inventory_navbar.php';
} else {
	include '../../includes/admin_navbar.php';
}
?>

<div class="container my-5">
    <h2 class="mb-4">Restock Products</h2>
    
    <div class="row">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Restocking Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="restockForm">
                        <div class="mb-3">
                            <label for="supplier_id" class="form-label">Supplier *</label>
                            <select class="form-select" id="supplier_id" name="supplier_id">
                                <option value="0">Select Supplier</option>
                                <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                                    <option value="<?php echo $supplier['supplier_id']; ?>"><?php echo htmlspecialchars($supplier['supplier_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div id="productsContainer">
                            <div class="product-row mb-3 p-3 border rounded">
                                <div class="row">
                                    <div class="col-md-5 mb-2">
                                        <label class="form-label">Product *</label>
                                        <select class="form-select product-select" name="products[]">
                                            <option value="">Select Product</option>
                                            <?php 
                                            $products_list->data_seek(0);
                                            while ($product = $products_list->fetch_assoc()): 
                                            ?>
                                                <option value="<?php echo $product['product_id']; ?>" <?php echo ($product_id == $product['product_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($product['product_name']); ?> (Stock: <?php echo $product['stock_quantity']; ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">Quantity *</label>
                                        <input type="text" class="form-control" name="quantities[]">
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label">Cost per Unit *</label>
                                        <input type="text" class="form-control" name="costs[]">
                                    </div>
                                    <div class="col-md-1 mb-2">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="button" class="btn btn-danger btn-sm w-100 remove-product">Remove</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-outline-primary mb-3" id="addProduct">Add Another Product</button>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Complete Restocking</button>
                        <a href="<?php echo BASE_URL; ?>/admin/inventory/index.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('addProduct').addEventListener('click', function() {
    const container = document.getElementById('productsContainer');
    const template = container.querySelector('.product-row');
    const newRow = template.cloneNode(true);
    newRow.querySelectorAll('input, select').forEach(input => {
        input.value = '';
    });
    container.appendChild(newRow);
});

// Use event delegation for remove buttons (works for dynamically added rows)
document.getElementById('productsContainer').addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-product') || e.target.closest('.remove-product')) {
        const button = e.target.classList.contains('remove-product') ? e.target : e.target.closest('.remove-product');
        const rows = document.querySelectorAll('.product-row');
        if (rows.length > 1) {
            button.closest('.product-row').remove();
        } else {
            alert('At least one product is required.');
        }
    }
});
</script>

<?php include '../../includes/foot.php'; ?>

