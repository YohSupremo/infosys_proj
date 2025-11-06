<?php
$page_title = 'Roles - Admin';
include '../../includes/header.php';
include '../../config/config.php';
requireAdmin();

$roles = $conn->query("SELECT * FROM roles ORDER BY role_name");
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Roles</h2>
        <a href="create.php" class="btn btn-primary">Add New Role</a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Role Name</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($roles->num_rows > 0): ?>
                            <?php while ($role = $roles->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $role['role_id']; ?></td>
                                    <td><?php echo htmlspecialchars($role['role_name']); ?></td>
                                    <td><?php echo htmlspecialchars($role['description'] ?: 'N/A'); ?></td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $role['role_id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form method="POST" action="delete.php" class="d-inline">
                                            <input type="hidden" name="role_id" value="<?php echo $role['role_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this role?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No roles found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

