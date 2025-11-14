<?php
$page_title = 'Forgot Password - NBA Shop';
include '../../config/config.php';
include '../../includes/header.php';

use PHPMailer\PHPMailer\PHPMailer;

require('../../vendor/autoload.php');

$mail = new PHPMailer(true);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        $stmt = $conn->prepare("SELECT user_id, first_name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $verification_code = rand(100000, 999999);
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_code'] = $verification_code;
            $_SESSION['reset_code_expires'] = time() + 600; // 10 minutes
            
            try{
                $mail->isSMTP();
                $mail->Host = 'sandbox.smtp.mailtrap.io';
                $mail->SMTPAuth = true;
                $mail->Username = 'a5ef4344d2fe1d';
                $mail->Password = 'bf071af51636d9';
                $mail->Port = 2525;
                $mail->SMTPSecure = 'tls';

                $mail->setFrom('noreply@nbashop.com', 'NBA Shop');
                $mail->addAddress($email, $user['first_name'] . ' ' . $user['last_name']);
                $mail->Subject = 'Password Reset Verification Code';
                $mail->Body = "Hello " . $user['first_name'] . ",\n\nYour verification code is: " . $verification_code . "\n\nEnter this code on the reset password page to set a new password. This code will expire in 10 minutes.\n\nIf you did not request this, please ignore this email.";
                $mail->AltBody = "Your verification code is: " . $verification_code;

                $mail->send();
                $_SESSION['reset_message'] = 'A verification code has been sent to your email. Please enter it to reset your password.';
                header('Location: ' . BASE_URL . '/user/auth/reset_password.php');
                exit();
            }catch (Exception $e){
                $error = 'Failed to send verification code. Please try again later.';
            }
        } else {
            // Don't reveal if email exists for security
            $success = 'If an account exists with this email, a verification code has been sent.';
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
                    <h4 class="mb-0">Forgot Password</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <p>Enter your email address and we'll send you a verification code to reset your password.</p>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Send Verification Code</button>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <a href="<?php echo BASE_URL; ?>/user/auth/login.php">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

