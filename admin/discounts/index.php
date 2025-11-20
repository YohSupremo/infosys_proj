<?php
$page_title = 'Discounts - Admin';
include '../../config/config.php';
include '../../includes/header.php';
requireAdmin();

$discounts = $conn->query("SELECT dc.*, COUNT(du.usage_id) AS times_used 
                           FROM discount_codes dc
                           LEFT JOIN discount_usage du ON dc.discount_id = du.discount_id
                           GROUP BY dc.discount_id
                           ORDER BY dc.created_at DESC");
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Discount Codes</h2>
        <a href="create.php" class="btn btn-primary">Add New Discount</a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Applies To</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($discounts->num_rows > 0): ?>
                            <?php while ($discount = $discounts->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $discount['discount_id']; ?></td>
                                    <td><strong><?= htmlspecialchars($discount['code']); ?></strong></td>
                                    <td><?= htmlspecialchars($discount['discount_type']); ?></td>
                                    <td>
                                        <?= $discount['discount_type'] === 'percentage'
                                            ? $discount['discount_value'] . '%'
                                            : '₱' . number_format($discount['discount_value'], 2); ?>
                                    </td>
                                    <td><?= htmlspecialchars($discount['applies_to']); ?></td>

                                    <!-- Status -->
                                    <td>
                                        <?php
                                            $usage_limit = $discount['usage_limit'] ? intval($discount['usage_limit']) : null;
                                            $times_used = $discount['times_used'] ? intval($discount['times_used']) : 0;

                                            if (!is_null($usage_limit) && $times_used >= $usage_limit):
                                        ?>
                                            <span class="badge bg-secondary">Max Usage</span>
                                        <?php else: ?>
                                            <?php if ($discount['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Actions -->
                                    <td>
                                        <a href="edit.php?id=<?= $discount['discount_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">Edit</a>

                                        <?php if ($discount['is_active']): ?>
                                            <!-- Deactivate -->
                                            <form method="POST" action="delete.php" class="d-inline">
                                                <input type="hidden" name="discount_id" value="<?= $discount['discount_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                    onclick="return confirm('Deactivate this discount?')">
                                                    Deactivate
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <!-- Reactivate -->
                                            <form method="POST" action="activate.php" class="d-inline">
                                                <input type="hidden" name="discount_id" value="<?= $discount['discount_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                    Reactivate
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No discounts found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>
