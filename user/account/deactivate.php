<?php
$page_title = 'Deactivate Account - NBA Shop';
include '../../config/config.php';
include '../../includes/header.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = sanitize($_POST['confirm'] ?? '');
    
    if ($confirm !== 'DEACTIVATE') {
        $error = 'Please type "DEACTIVATE" to confirm.';
    } else {
        $deactivate_stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?");
        $deactivate_stmt->bind_param("i", $user_id);
        
        if ($deactivate_stmt->execute()) {
            $deactivate_stmt->close();
            session_destroy();
            header('Location: ' . BASE_URL . '/user/auth/login.php?deactivated=1');
            exit();
        } else {
            $error = 'Failed to deactivate account.';
        }
        $deactivate_stmt->close();
    }
}

$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<?php include '../../includes/navbar.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Deactivate Account</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <div class="alert alert-warning">
                        <strong>Warning:</strong> Deactivating your account will prevent you from logging in and accessing your account. 
                        You can contact an administrator to reactivate your account later.
                    </div>
                    
                    <p>Are you sure you want to deactivate your account, <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>?</p>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="confirm" class="form-label">Type "DEACTIVATE" to confirm *</label>
                            <input type="text" class="form-control" id="confirm" name="confirm" placeholder="DEACTIVATE">
                        </div>
                        <button type="submit" class="btn btn-danger">Deactivate My Account</button>
                        <a href="<?php echo BASE_URL; ?>/user/account/profile.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

