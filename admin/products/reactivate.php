<?php
include '../../config/config.php';
requireAdminOrInventoryManager();

$redirect = 'index.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $redirect_input = $_POST['redirect'] ?? '';
    if (!empty($redirect_input) && stripos($redirect_input, 'http') !== 0) {
        $redirect = $redirect_input;
    }

    if ($product_id > 0) {
        $reactivate_stmt = $conn->prepare("UPDATE products SET is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE product_id = ?");
        $reactivate_stmt->bind_param("i", $product_id);
        $reactivate_stmt->execute();
        $reactivate_stmt->close();
    }
}

header('Location: ' . $redirect);
exit();

