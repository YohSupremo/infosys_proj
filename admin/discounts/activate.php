<?php
include '../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $discount_id = intval($_POST['discount_id'] ?? 0);

    if ($discount_id > 0) {
        $stmt = $conn->prepare("UPDATE discount_codes SET is_active = 1 WHERE discount_id = ?");
        $stmt->bind_param("i", $discount_id);
        $stmt->execute();
        $stmt->close();
    }
}

header('Location: index.php');
exit();
?>
