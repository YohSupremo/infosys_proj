<?php
$page_title = 'Suppliers - Admin';
include '../../config/config.php';
include '../../includes/header.php';
requireAdmin();

$suppliers = $conn->query("SELECT * FROM suppliers ORDER BY supplier_name");
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Suppliers</h2>
        <a href="create.php" class="btn btn-primary">Add New Supplier</a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Supplier Name</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($suppliers->num_rows > 0): ?>
                            <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $supplier['supplier_id']; ?></td>
                                    <td><?php echo htmlspecialchars($supplier['supplier_name']); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['contact_person'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['email'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['phone'] ?: 'N/A'); ?></td>
                                    <td>
                                        <?php if (intval($supplier['is_active']) == 1): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $supplier['supplier_id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form method="POST" action="delete.php" class="d-inline">
                                            <input type="hidden" name="supplier_id" value="<?php echo $supplier['supplier_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this supplier?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No suppliers found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

