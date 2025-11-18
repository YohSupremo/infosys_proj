<?php
$page_title = 'Add User - Admin';
include '../../config/config.php';
include '../../includes/header.php';
requireAdmin();

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);

$roles = $conn->query("SELECT * FROM roles ORDER BY role_name");
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Add New User</h2>
    
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">User Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="store.php" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="text" class="form-control" id="email" name="email" placeholder="example@email.com">
                            <small class="text-muted">Enter a valid email address (e.g. example@email.com)</small>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        <div class="mb-3">
                            <label for="contact_number" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="contact_number" name="contact_number">
                        </div>
                        <div class="mb-3">
                            <label class="form-label d-block">Profile Photo</label>
                            <input type="file" class="form-control" name="profile_photo" accept="image/*">
                            <small class="text-muted">Upload a JPG/PNG/GIF image. Max ~2MB.</small>
                        </div>
                        <div class="mb-3">
                            <label for="role_id" class="form-label">Role *</label>
                            <select class="form-select" id="role_id" name="role_id">
                                <option value="0">Select Role</option>
                                <?php while ($role = $roles->fetch_assoc()): ?>
                                    <option value="<?php echo $role['role_id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Add User</button>
                        <a href="<?php echo BASE_URL; ?>/admin/users/index.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

