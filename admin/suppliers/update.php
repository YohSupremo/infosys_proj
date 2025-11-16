<?php
include '../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = intval($_POST['supplier_id'] ?? 0);
    $supplier_name = sanitize($_POST['supplier_name'] ?? '');
    $contact_person = sanitize($_POST['contact_person'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (!$supplier_id) {
        header('Location: index.php');
        exit();
    }
    
    if (empty($supplier_name)) {
        $_SESSION['error'] = 'Supplier name is required.';
        header('Location: edit.php?id=' . $supplier_id);
        exit();
    }
    
    // Validate email format if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Please enter a valid email address (e.g. example@email.com)';
        header('Location: edit.php?id=' . $supplier_id);
        exit();
    }
    
    $update_stmt = $conn->prepare("UPDATE suppliers SET supplier_name = ?, contact_person = ?, email = ?, phone = ?, address = ?, is_active = ? WHERE supplier_id = ?");
    $update_stmt->bind_param("sssssii", $supplier_name, $contact_person, $email, $phone, $address, $is_active, $supplier_id);
    
    if ($update_stmt->execute()) {
        $update_stmt->close();
        header('Location: index.php?success=1');
        exit();
    } else {
        $update_stmt->close();
        $_SESSION['error'] = 'Failed to update supplier.';
        header('Location: edit.php?id=' . $supplier_id);
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}
?>
