<?php
$page_title = 'Update Order Status - Admin';
include '../../includes/header.php';
include '../../config/config.php';
requireAdmin();

$order_id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

if (!$order_id) {
    header('Location: index.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
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
    
    if (empty($new_status)) {
        $error = 'Please select a status.';
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
                            <select class="form-select" id="order_status" name="order_status" required>
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
                        <a href="view.php?id=<?php echo $order_id; ?>" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

