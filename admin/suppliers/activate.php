<?php
include '../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = intval($_POST['supplier_id'] ?? 0);

    if ($supplier_id > 0) {
        $stmt = $conn->prepare("UPDATE suppliers SET is_active = 1 WHERE supplier_id = ?");
        $stmt->bind_param("i", $supplier_id);
        $stmt->execute();
        $stmt->close();
    }
}

header('Location: index.php');
exit();
?>
