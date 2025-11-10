<?php
$page_title = 'Discount Report - Admin';
include '../../includes/header.php';
include '../../config/config.php';
requireAdmin();

$discounts = $conn->query("SELECT dc.*, 
    (SELECT COUNT(*) FROM discount_usage WHERE discount_id = dc.discount_id) as times_used,
    (SELECT SUM(discount_amount) FROM discount_usage WHERE discount_id = dc.discount_id) as total_discount_amount
    FROM discount_codes dc 
    ORDER BY dc.created_at DESC");
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Discount Report</h2>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Times Used</th>
                            <th>Total Discount Given</th>
                            <th>Usage Limit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($discounts->num_rows > 0): ?>
                            <?php while ($discount = $discounts->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($discount['code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($discount['discount_type']); ?></td>
                                    <td><?php echo $discount['discount_type'] === 'percentage' ? $discount['discount_value'] . '%' : '₱' . number_format($discount['discount_value'], 2); ?></td>
                                    <td><?php echo $discount['times_used'] ?: 0; ?> / <?php echo $discount['usage_limit'] ?: '∞'; ?></td>
                                    <td>₱<?php echo number_format($discount['total_discount_amount'] ?: 0, 2); ?></td>
                                    <td><?php echo $discount['usage_limit'] ?: 'Unlimited'; ?></td>
                                    <td>
										<?php if ($discount['is_active']): ?>
											<span class="badge bg-success">Active</span>
										<?php else: ?>
											<span class="badge bg-danger">Inactive</span>
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

