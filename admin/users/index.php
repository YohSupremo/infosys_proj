<?php
$page_title = 'Users - Admin';
include '../../config/config.php';
include '../../includes/header.php';
requireAdmin();

$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? 'active';
$allowed_statuses = ['active', 'inactive', 'all'];
if (!in_array($status_filter, $allowed_statuses, true)) {
    $status_filter = 'active';
}

$current_users_url = BASE_URL . '/admin/users/index.php';
$user_query_string = $_SERVER['QUERY_STRING'] ?? '';
if (!empty($user_query_string)) {
    $current_users_url .= '?' . $user_query_string;
}

$query = "SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE 1=1";
$types = '';
$params = [];

if ($status_filter === 'active') {
    $query .= " AND u.is_active = 1";
} elseif ($status_filter === 'inactive') {
    $query .= " AND u.is_active = 0";
}

if ($search !== '') {
    $query .= " AND (CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR u.email LIKE ?)";
    $like = '%' . $search . '%';
    $types .= 'ss';
    $params[] = $like;
    $params[] = $like;
}

$query .= " ORDER BY u.created_at DESC";

$stmt = $conn->prepare($query);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Users</h2>
        <a href="<?php echo BASE_URL; ?>/admin/users/create.php" class="btn btn-primary">Add New User</a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end mb-4">
                <div class="col-md-6">
                    <label for="search" class="form-label">Search Users</label>
                    <input type="text" id="search" name="search" class="form-control" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Show All</option>
                    </select>
                </div>
                <div class="col-md-3 text-md-end">
                    <button type="submit" class="btn btn-primary me-2">Apply</button>
                    <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users->num_rows > 0): ?>
                            <?php while ($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $user['user_id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                                    <td>
										<?php if ($user['is_active']): ?>
											<span class="badge bg-success">Active</span>
										<?php else: ?>
											<span class="badge bg-danger">Inactive</span>
										<?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/admin/users/edit.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <?php if ($user['is_active']): ?>
                                            <form method="POST" action="delete.php" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($current_users_url); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger ms-1" onclick="return confirm('Deactivate this user?')">Deactivate</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="reactivate.php" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($current_users_url); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success ms-1" onclick="return confirm('Reactivate this user?')">Reactivate</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    $stmt->close();
}
?>

<?php include '../../includes/foot.php'; ?>

