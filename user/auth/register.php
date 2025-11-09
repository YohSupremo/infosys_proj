<?php
$page_title = 'Register - NBA Shop';
include '../../includes/header.php';
include '../../config/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $contact_number = sanitize($_POST['contact_number'] ?? '');
    $address_line1 = sanitize($_POST['address_line1'] ?? '');
    $address_line2 = sanitize($_POST['address_line2'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $state = sanitize($_POST['state'] ?? '');
    $postal_code = sanitize($_POST['postal_code'] ?? '');
    $country = sanitize($_POST['country'] ?? 'Philippines');
    
    // Server-side validation
    if (empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (empty($address_line1) || empty($city) || empty($state) || empty($postal_code)) {
        $error = 'Please fill in all address fields.';
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already exists.';
        } else {
            // Get Customer role_id
            $role_stmt = $conn->prepare("SELECT role_id FROM roles WHERE role_name = 'Customer'");
            $role_stmt->execute();
            $role_result = $role_stmt->get_result();
            $role = $role_result->fetch_assoc();
            $role_id = $role['role_id'];
            
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Handle profile photo upload
            $profile_photo = null;
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
                        $profile_photo = 'assets/images/profiles/' . $new_filename;
                    }
                }
            }
            
            $insert_stmt = $conn->prepare("INSERT INTO users (role_id, email, password_hash, first_name, last_name, contact_number, profile_photo) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("issssss", $role_id, $email, $password_hash, $first_name, $last_name, $contact_number, $profile_photo);
            
            if ($insert_stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // Insert address
                $addr_stmt = $conn->prepare("INSERT INTO user_addresses (user_id, address_line1, address_line2, city, state, postal_code, country, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                $addr_stmt->bind_param("issssss", $user_id, $address_line1, $address_line2, $city, $state, $postal_code, $country);
                $addr_stmt->execute();
                $addr_stmt->close();
                
                $success = 'Registration successful! You can now login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
            $insert_stmt->close();
        }
        $stmt->close();
    }
}
?>

<?php include '../../includes/navbar.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Register</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="contact_number" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($_POST['contact_number'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="profile_photo" class="form-label">Profile Photo</label>
                            <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                            <small class="text-muted">Optional: Upload a profile photo (JPG, PNG, GIF)</small>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <small class="text-muted">Must be at least 6 characters</small>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                        <hr>
                        <h5 class="mb-3">Address Information *</h5>
                        <div class="mb-3">
                            <label for="address_line1" class="form-label">Address Line 1 *</label>
                            <input type="text" class="form-control" id="address_line1" name="address_line1" value="<?php echo htmlspecialchars($_POST['address_line1'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="address_line2" class="form-label">Address Line 2</label>
                            <input type="text" class="form-control" id="address_line2" name="address_line2" value="<?php echo htmlspecialchars($_POST['address_line2'] ?? ''); ?>">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City *</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="state" class="form-label">State/Province *</label>
                                <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($_POST['state'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="postal_code" class="form-label">Postal Code *</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($_POST['postal_code'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="country" class="form-label">Country *</label>
                                <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlspecialchars($_POST['country'] ?? 'Philippines'); ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <p>Already have an account? <a href="<?php echo BASE_URL; ?>/user/auth/login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>


