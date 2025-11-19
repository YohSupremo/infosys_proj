<?php
include '../../config/config.php';
requireAdmin();

$redirect = 'index.php';
// reactivate user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $redirect_input = $_POST['redirect'] ?? '';
    if (!empty($redirect_input) && stripos($redirect_input, 'http') !== 0) {
        $redirect = $redirect_input;
    }

    if ($user_id > 0) {
        $reactivate_stmt = $conn->prepare("UPDATE users SET is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
        $reactivate_stmt->bind_param("i", $user_id);
        $reactivate_stmt->execute();
        $reactivate_stmt->close();
    }
}

header('Location: ' . $redirect);
exit();

