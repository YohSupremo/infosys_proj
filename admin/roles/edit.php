<?php
$page_title = 'Edit Role - Admin';
include '../../includes/header.php';
include '../../config/config.php';
requireAdmin();

$role_id = intval($_GET['id'] ?? 0);
$error = '';

if (!$role_id) {
    header('Location: index.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM roles WHERE role_id = ?");
$stmt->bind_param("i", $role_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$role = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_name = sanitize($_POST['role_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    
    if (empty($role_name)) {
        $error = 'Role name is required.';
    } else {
        $update_stmt = $conn->prepare("UPDATE roles SET role_name = ?, description = ? WHERE role_id = ?");
        $update_stmt->bind_param("ssi", $role_name, $description, $role_id);
        
        if ($update_stmt->execute()) {
            header('Location: index.php?success=1');
            exit();
        } else {
            $error = 'Failed to update role.';
        }
        $update_stmt->close();
    }
}
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Edit Role</h2>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Role Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="role_name" class="form-label">Role Name *</label>
                            <input type="text" class="form-control" id="role_name" name="role_name" value="<?php echo htmlspecialchars($role['role_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($role['description']); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Role</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

