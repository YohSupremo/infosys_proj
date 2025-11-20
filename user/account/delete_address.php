<?php
include '../../config/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address_id = intval($_POST['address_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if ($address_id > 0) {
        // Check if address belongs to user
        $check_stmt = $conn->prepare("SELECT address_id FROM user_addresses WHERE address_id = ? AND user_id = ?");
        $check_stmt->bind_param("ii", $address_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Check if address is used in any orders
            $order_check = $conn->prepare("SELECT COUNT(*) AS order_count FROM orders WHERE address_id = ?");
            $order_check->bind_param("i", $address_id);
            $order_check->execute();
            $order_result = $order_check->get_result();
            $order_row = $order_result->fetch_assoc();
            $order_count = intval($order_row['order_count']);
            $order_check->close();
            
            if ($order_count > 0) {
                // Address is used in orders, cannot delete
                $_SESSION['error'] = 'Cannot delete this address because it is associated with existing orders.';
            } else {
                // Safe to delete
                $delete_stmt = $conn->prepare("DELETE FROM user_addresses WHERE address_id = ?");
                $delete_stmt->bind_param("i", $address_id);
                $delete_stmt->execute();
                $delete_stmt->close();
                $_SESSION['success'] = 'Address deleted successfully.';
            }
        }
        $check_stmt->close();
    }
}

header('Location: addresses.php');
exit();
?>

