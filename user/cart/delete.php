<?php
include '../../config/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_item_id = intval($_POST['cart_item_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if ($cart_item_id > 0) {
        // Verify cart ownership
        $check_stmt = $conn->prepare("SELECT ci.cart_item_id FROM cart_items ci JOIN shopping_cart sc ON ci.cart_id = sc.cart_id WHERE ci.cart_item_id = ? AND sc.user_id = ?");
        $check_stmt->bind_param("ii", $cart_item_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $delete_stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_item_id = ?");
            $delete_stmt->bind_param("i", $cart_item_id);
            $delete_stmt->execute();
            $delete_stmt->close();
        }
        $check_stmt->close();
    }
}

header('Location: index.php');
exit();
?>

