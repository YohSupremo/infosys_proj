<?php
$page_title = 'Edit User - Admin';
include '../../config/config.php';
include '../../includes/header.php';
requireAdmin();

$user_id = intval($_GET['id'] ?? 0);
$error = '';

if (!$user_id) {
    header('Location: index.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);

$roles = $conn->query("SELECT * FROM roles ORDER BY role_name");
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Edit User</h2>
    
    <div class="row">
		<div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">User Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
					<form method="POST" action="update.php" enctype="multipart/form-data">
						<input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        </div>
						<div class="mb-3">
							<label class="form-label d-block">Profile Photo</label>
							<div class="d-flex align-items-center">
								<?php
								$current_photo = !empty($user['profile_photo']) ? $user['profile_photo'] : 'assets/images/placeholder.jpg';
								?>
								<img src="<?php echo BASE_URL . '/' . $current_photo; ?>" alt="Profile" style="width:60px;height:60px;object-fit:cover" class="rounded me-3">
								<input type="file" class="form-control" name="profile_photo" accept="image/*" style="max-width:320px;">
							</div>
							<small class="text-muted">Upload a JPG/PNG/GIF image. Max ~2MB.</small>
						</div>
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        <div class="mb-3">
                            <label for="contact_number" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($user['contact_number']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="role_id" class="form-label">Role *</label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <?php while ($role = $roles->fetch_assoc()): ?>
                                    <option value="<?php echo $role['role_id']; ?>" <?php echo ($user['role_id'] == $role['role_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Update User</button>
                        <a href="<?php echo BASE_URL; ?>/admin/users/index.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

