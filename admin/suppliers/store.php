<?php
include '../../config/config.php';
requireAdmin();
// for create.php logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_name = sanitize($_POST['supplier_name'] ?? '');
    $contact_person = sanitize($_POST['contact_person'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($supplier_name)) {
        $_SESSION['error'] = 'Supplier name is required.';
        header('Location: create.php');
        exit();
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Please enter a valid email address (e.g. example@email.com)';
        header('Location: create.php');
        exit();
    }
    
    $stmt = $conn->prepare("INSERT INTO suppliers (supplier_name, contact_person, email, phone, address, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $supplier_name, $contact_person, $email, $phone, $address, $is_active);
    
    if ($stmt->execute()) {
        $stmt->close();
        header('Location: index.php?success=1');
        exit();
    } else {
        $stmt->close();
        $_SESSION['error'] = 'Failed to add supplier.';
        header('Location: create.php');
        exit();
    }
} else {
    header('Location: create.php');
    exit();
}
?>
