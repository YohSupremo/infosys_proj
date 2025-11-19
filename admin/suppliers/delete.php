<?php
include '../../config/config.php';
requireAdmin();
// delete galing sa inline adding ng id
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = intval($_POST['supplier_id'] ?? 0);
    
    if ($supplier_id > 0) {
        $delete_stmt = $conn->prepare("DELETE FROM suppliers WHERE supplier_id = ?");
        $delete_stmt->bind_param("i", $supplier_id);
        $delete_stmt->execute();
        $delete_stmt->close();
    }
}

header('Location: index.php');
exit();
?>

