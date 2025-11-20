<?php
include '../../config/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $contact_number = sanitize($_POST['contact_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($first_name) || empty($last_name)) {
        $_SESSION['error'] = 'First name and last name are required.';
    } elseif (!empty($password) && $password !== $confirm_password) {
        $_SESSION['error'] = 'Passwords do not match.';
    } elseif (!empty($password) && strlen($password) < 6) {
        $_SESSION['error'] = 'Password must be at least 6 characters long.';
     } elseif (empty($contact_number)) {
        $_SESSION['error'] = 'Contact number is required.';
    } elseif (!empty($contact_number) && !preg_match('/^\d{11}$/', $contact_number)) {
        $_SESSION['error'] = 'Contact number must be exactly 11 digits.';
    } else {
        $stmt = $conn->prepare("SELECT profile_photo FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current = $result->fetch_assoc();
        $stmt->close();

        $profile_photo = $current ? $current['profile_photo'] : null;

        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../assets/images/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true); 
            }

            $file_ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_ext, $allowed_exts)) {
                $new_filename = uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                    if ($profile_photo && file_exists('../../' . $profile_photo)) {
                        unlink('../../' . $profile_photo);
                    }
                    $profile_photo = 'assets/images/profiles/' . $new_filename;
                }
            }
        }

        if (!isset($_SESSION['error']) || $_SESSION['error'] === '') {
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, contact_number = ?, profile_photo = ?, password_hash = ? WHERE user_id = ?");
                $update_stmt->bind_param("sssssi", $first_name, $last_name, $contact_number, $profile_photo, $password_hash, $user_id);
            } else {
                $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, contact_number = ?, profile_photo = ? WHERE user_id = ?");
                $update_stmt->bind_param("ssssi", $first_name, $last_name, $contact_number, $profile_photo, $user_id);
            }

            if ($update_stmt->execute()) {
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['success'] = 'Profile updated successfully!';
            } else {
                $_SESSION['error'] = 'Failed to update profile.';
            }
            $update_stmt->close();
        }
    }
}

header('Location: edit_profile.php');
exit();
?>
