<?php
$page_title = 'Update Order Status - Admin';
include '../../config/config.php';
include '../../includes/header.php';
include '../../config/email_config.php';
require '../../vendor/autoload.php';
requireAdmin();

$order_id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

if (!$order_id) {
    header('Location: index.php');
    exit();
}

$stmt = $conn->prepare("SELECT o.*, u.email, u.first_name, u.last_name FROM orders o JOIN users u ON o.user_id = u.user_id WHERE o.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$order = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = sanitize($_POST['order_status'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    // Server-side validation
    if (empty($new_status)) {
        $error = 'Please select a status.';
    } elseif (!in_array($new_status, ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'])) {
        $error = 'Invalid status selected.';
    } else {
        $old_status = $order['order_status'];
        $update_stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $update_stmt->bind_param("si", $new_status, $order_id);
        
        if ($update_stmt->execute()) {
            // Record status history
            $history_stmt = $conn->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, notes) VALUES (?, ?, ?, ?, ?)");
            $history_stmt->bind_param("issis", $order_id, $old_status, $new_status, $user_id, $notes);
            $history_stmt->execute();
            $history_stmt->close();
            
            // Send email notification (Term Test Requirement)
            if ($old_status !== $new_status) {
                // Get order items for email
                $items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
                $items_stmt->bind_param("i", $order_id);
                $items_stmt->execute();
                $items_result = $items_stmt->get_result();
                
                $order_items = [];
                while ($item = $items_result->fetch_assoc()) {
                    $order_items[] = $item;
                }
                $items_stmt->close();
                
                // Send email with product list, subtotal, and grand total
                $to_email = $order['email'];
                $to_name = $order['first_name'] . ' ' . $order['last_name'];
                sendOrderStatusEmail(
                    $to_email,
                    $to_name,
                    $order_id,
                    $new_status,
                    $order_items,
                    $order['subtotal'],
                    $order['discount_amount'],
                    $order['total_amount']
                );
            }
            
            $success = 'Order status updated successfully!';
            header('Location: view.php?id=' . $order_id . '&success=1');
            exit();
        } else {
            $error = 'Failed to update order status.';
        }
        $update_stmt->close();
    }
}
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Update Order Status</h2>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Current Status: <?php echo htmlspecialchars($order['order_status']); ?></h5>
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
                            <label for="order_status" class="form-label">New Status *</label>
                            <select class="form-select" id="order_status" name="order_status">
                                <option value="">Select Status</option>
                                <option value="Pending" <?php echo $order['order_status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Processing" <?php echo $order['order_status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="Shipped" <?php echo $order['order_status'] === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="Delivered" <?php echo $order['order_status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="Cancelled" <?php echo $order['order_status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                        <a href="<?php echo BASE_URL; ?>/admin/orders/view.php?id=<?php echo $order_id; ?>" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

