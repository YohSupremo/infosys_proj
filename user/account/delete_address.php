<?php
include '../../config/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address_id = intval($_POST['address_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    
    if ($address_id > 0) {
        // Verify address belongs to user
        $check_stmt = $conn->prepare("SELECT address_id FROM user_addresses WHERE address_id = ? AND user_id = ?");
        $check_stmt->bind_param("ii", $address_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $delete_stmt = $conn->prepare("DELETE FROM user_addresses WHERE address_id = ?");
            $delete_stmt->bind_param("i", $address_id);
            $delete_stmt->execute();
            $delete_stmt->close();
        }
        $check_stmt->close();
    }
}

header('Location: addresses.php');
exit();
?>

