<?php
$page_title = 'Login - NBA Shop';
include '../../config/config.php';
include '../../includes/header.php';

$error = '';
$success = '';
$redirect_message = '';

// check for redirect message
if (isset($_SESSION['redirect_message'])) {
    $redirect_message = $_SESSION['redirect_message'];
    unset($_SESSION['redirect_message']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // server-side validation - check kung may email at pass, pati kung valid email
        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address (e.g. example@email.com)';
        } else {
            $stmt = $conn->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                //if valid email, verify kung tama yung in-input na password ni user
                if (password_verify($password, $user['password_hash'])) {
                    if ($user['is_active'] == 1) {
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['last_name'] = $user['last_name'];
                        $_SESSION['role_name'] = $user['role_name'];
                        $_SESSION['role_id'] = $user['role_id'];
                        
                        // check if there's a redirect after login (e.g., from add to cart)
                        if (isset($_SESSION['redirect_after_login'])) {
                            $redirect_url = $_SESSION['redirect_after_login'];
                            unset($_SESSION['redirect_after_login']);
                            header('Location: ' . $redirect_url);
                        } elseif ($user['role_id'] === 1) {
                            header('Location: ' . BASE_URL . '/admin/dashboard.php');
                        } else if ($user['role_id'] == 2) {
                            header('Location: ' . BASE_URL . '/admin/inventory_dashboard.php');
                        } else {
                             header('Location: ' . BASE_URL . '/index.php');
                        }
                        exit();
                    } else {
                        $error = 'Your account is deactivated. Please contact the administrator to reactivate your account.';
                    }
                } else {
                    $error = 'Invalid email or password.';
                }
            } else {
                $error = 'Invalid email or password.';
            }
            $stmt->close();
        }
    }
}
?>
<?php include '../../includes/navbar.php'; ?>

<div class="container my-5">
    <?php if ($redirect_message): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($redirect_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Login</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="text" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="example@email.com">
                            <small class="text-muted">Enter your email address</small>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                          <div class="mt-3 text-center">
                        <a href="<?php echo BASE_URL; ?>/user/auth/forgot_password.php">Forgot Password?</a>
                    </div>
                    <div class="mt-2 text-center">
                        <p>Don't have an account? <a href="<?php echo BASE_URL; ?>/user/auth/register.php">Register here</a></p>
                    </div>
                    </form>
                    
                  
                    
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

