<?php
include '../../config/config.php';
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_id = intval($_POST['team_id'] ?? 0);
    
    if ($team_id > 0) {
        $product_check = $conn->prepare("SELECT COUNT(*) AS product_count FROM products WHERE team_id = ?");
        $product_check->bind_param("i", $team_id);
        $product_check->execute();
        $product_result = $product_check->get_result();
        $product_row = $product_result->fetch_assoc();
        $product_count = intval($product_row['product_count']);
        $product_check->close();
        
        if ($product_count > 0) {
            $_SESSION['error'] = 'Cannot delete this team because it is associated with existing products.';
        } else {
            $delete_stmt = $conn->prepare("DELETE FROM nba_teams WHERE team_id = ?");
            $delete_stmt->bind_param("i", $team_id);
            $delete_stmt->execute();
            $delete_stmt->close();
            $_SESSION['success'] = 'Team deleted successfully.';
        }
    }
}

header('Location: index.php');
exit();
?>

