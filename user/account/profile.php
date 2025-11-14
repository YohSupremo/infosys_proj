<?php
$page_title = 'My Profile - NBA Shop';
include '../../config/config.php';
include '../../includes/header.php';
requireLogin();

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<?php include '../../includes/navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">My Profile</h2>
    
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <?php if (!empty($user['profile_photo'])): ?>
                        <img src="../../<?php echo htmlspecialchars($user['profile_photo']); ?>" 
                             class="img-fluid rounded-circle mb-3" 
                             alt="Profile Photo" 
                             style="width: 200px; height: 200px; object-fit: cover; border: 4px solid #007bff;">
                    <?php else: ?>
                        <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center mb-3" 
                             style="width: 200px; height: 200px; margin: 0 auto;">
                            <i class="bi bi-person-circle" style="font-size: 150px; color: white;"></i>
                        </div>
                    <?php endif; ?>
                    <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                    <p class="text-muted mb-0">
                        <span class="badge bg-primary"><?php echo htmlspecialchars($user['role_name']); ?></span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th width="200">Name:</th>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                        </tr>
                        <tr>
                            <th>Contact Number:</th>
                            <td><?php echo htmlspecialchars($user['contact_number'] ?: 'Not provided'); ?></td>
                        </tr>
                        <tr>
                            <th>Role:</th>
                            <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Account Status:</th>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Member Since:</th>
                            <td><?php echo date('F d, Y', strtotime($user['created_at'])); ?></td>
                        </tr>
                    </table>
                    
                    <div class="d-flex gap-2">
                        <a href="<?php echo BASE_URL; ?>/user/account/edit_profile.php" class="btn btn-primary">Edit Profile</a>
                        <a href="<?php echo BASE_URL; ?>/user/account/deactivate.php" class="btn btn-outline-danger">Deactivate Account</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

