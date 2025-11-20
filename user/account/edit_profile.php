<?php
$page_title = 'Edit Profile - NBA Shop';
include '../../config/config.php';
include '../../includes/header.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['error'], $_SESSION['success']);

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<?php include '../../includes/navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Edit Profile</h2>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Update Profile</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="update_profile.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="contact_number" class="form-label">Contact Number *</label>
                            <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="profile_photo" class="form-label">Profile Photo</label>
                            <?php if ($user['profile_photo']): ?>
                                <div class="mb-2">
                                    <img src="../../<?php echo htmlspecialchars($user['profile_photo']); ?>" style="max-width: 150px; max-height: 150px;" class="img-thumbnail">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                            <small class="text-muted">Upload a new profile photo (JPG, PNG, GIF)</small>
                        </div>
                        <hr>
                        <h5 class="mb-3">Change Password</h5>
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <small class="text-muted">Leave blank to keep current password. Must be at least 6 characters.</small>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                        <a href="<?php echo BASE_URL; ?>/user/account/profile.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

