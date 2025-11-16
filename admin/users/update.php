<?php
include '../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $contact_number = sanitize($_POST['contact_number'] ?? '');
    $role_id = intval($_POST['role_id'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password = $_POST['password'] ?? '';
    $uploaded_photo_path = null;
    
    if (!$user_id) {
        header('Location: index.php');
        exit();
    }
    
    // Handle profile photo upload (optional)
    if (isset($_FILES['profile_photo']) && isset($_FILES['profile_photo']['tmp_name']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../assets/images/profiles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        $allowed_exts = array('jpg', 'jpeg', 'png', 'gif');
        if (in_array($file_ext, $allowed_exts)) {
            $new_filename = uniqid() . '.' . $file_ext;
            $upload_path_fs = $upload_dir . $new_filename;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path_fs)) {
                $uploaded_photo_path = 'assets/images/profiles/' . $new_filename;
            }
        }
    }
    
    if (empty($first_name) || empty($last_name) || $role_id <= 0) {
        $_SESSION['error'] = 'Please fill in all required fields.';
        header('Location: edit.php?id=' . $user_id);
        exit();
    }
    
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $_SESSION['error'] = 'Password must be at least 6 characters long.';
            header('Location: edit.php?id=' . $user_id);
            exit();
        }
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        if ($uploaded_photo_path) {
            $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, contact_number = ?, role_id = ?, is_active = ?, password_hash = ?, profile_photo = ? WHERE user_id = ?");
            $update_stmt->bind_param("sssiissi", $first_name, $last_name, $contact_number, $role_id, $is_active, $password_hash, $uploaded_photo_path, $user_id);
        } else {
            $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, contact_number = ?, role_id = ?, is_active = ?, password_hash = ? WHERE user_id = ?");
            $update_stmt->bind_param("sssiisi", $first_name, $last_name, $contact_number, $role_id, $is_active, $password_hash, $user_id);
        }
    } else {
        if ($uploaded_photo_path) {
            $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, contact_number = ?, role_id = ?, is_active = ?, profile_photo = ? WHERE user_id = ?");
            $update_stmt->bind_param("sssiisi", $first_name, $last_name, $contact_number, $role_id, $is_active, $uploaded_photo_path, $user_id);
        } else {
            $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, contact_number = ?, role_id = ?, is_active = ? WHERE user_id = ?");
            $update_stmt->bind_param("sssiii", $first_name, $last_name, $contact_number, $role_id, $is_active, $user_id);
        }
    }
    
    if ($update_stmt->execute()) {
        $update_stmt->close();
        header('Location: index.php?success=1');
        exit();
    } else {
        $update_stmt->close();
        $_SESSION['error'] = 'Failed to update user.';
        header('Location: edit.php?id=' . $user_id);
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}
?>
