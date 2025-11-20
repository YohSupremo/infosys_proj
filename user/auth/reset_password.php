<?php
$page_title = 'Reset Password - NBA Shop';
include '../../config/config.php';
include '../../includes/header.php';

$error = '';
$success = '';
$notice = '';

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_code'])) {
    header('Location: ' . BASE_URL . '/user/auth/forgot_password.php');
    exit();
}

$reset_email = $_SESSION['reset_email'];

if (isset($_SESSION['reset_message'])) {
    $notice = $_SESSION['reset_message'];
    unset($_SESSION['reset_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $verification_code = sanitize($_POST['verification_code'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($verification_code) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif (!isset($_SESSION['reset_code']) || !isset($_SESSION['reset_code_expires'])) {
        $error = 'Verification code has expired. Please request a new one.';
    } elseif (time() > $_SESSION['reset_code_expires']) {
        $error = 'Verification code has expired. Please request a new one.';
    } elseif (strlen($verification_code) != 6 || !is_numeric($verification_code)) {
        $error = 'Verification code must be a 6-digit number.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($verification_code !== strval($_SESSION['reset_code'])) {
        $error = 'Invalid verification code.';
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $reset_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $update_stmt->bind_param("ss", $password_hash, $reset_email);
            
            if ($update_stmt->execute()) {
                $success = 'Password reset successful! You can now login.';
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_code']);
                unset($_SESSION['reset_code_expires']);
            } else {
                $error = 'Password reset failed. Please try again.';
            }
            $update_stmt->close();
        } else {
            $error = 'Unable to verify the account. Please try again.';
        }
        $stmt->close();
    }
}
?>

<?php include '../../includes/navbar.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Reset Password</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if ($notice): ?>
                        <div class="alert alert-info"><?php echo $notice; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($reset_email); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="verification_code" class="form-label">Verification Code</label>
                            <input type="text" class="form-control" id="verification_code" name="verification_code">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <a href="<?php echo BASE_URL; ?>/user/auth/forgot_password.php">Need a new code?</a><br>
                        <a href="<?php echo BASE_URL; ?>/user/auth/login.php">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

