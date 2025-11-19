<?php
$page_title = 'Product Reviews - Admin';
include '../../config/config.php';
include '../../includes/header.php';
requireAdminOrInventoryManager();

$is_admin = hasRole('Admin');
$product_id = intval($_GET['id'] ?? 0);

if ($product_id <= 0) {
    header('Location: index.php');
    exit();
}

// Fetch product details
$product_stmt = $conn->prepare("
    SELECT p.*, t.team_name 
    FROM products p 
    LEFT JOIN nba_teams t ON p.team_id = t.team_id 
    WHERE p.product_id = ?
");
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();

if ($product_result->num_rows === 0) {
    $product_stmt->close();
    header('Location: index.php');
    exit();
}

$product = $product_result->fetch_assoc();
$product_stmt->close();

// Fetch review summary (count + average)
$summary_stmt = $conn->prepare("SELECT COUNT(*) AS total_reviews, AVG(rating) AS avg_rating FROM product_reviews WHERE product_id = ?");
$summary_stmt->bind_param("i", $product_id);
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc();
$summary_stmt->close();

$total_reviews = intval($summary['total_reviews'] ?? 0);
$avg_rating = $summary['avg_rating'] !== null ? round(floatval($summary['avg_rating']), 1) : null;

// Fetch individual reviews
$reviews_stmt = $conn->prepare("
    SELECT r.*, u.first_name, u.last_name 
    FROM product_reviews r 
    JOIN users u ON r.user_id = u.user_id 
    WHERE r.product_id = ?
    ORDER BY r.created_at DESC
");
$reviews_stmt->bind_param("i", $product_id);
$reviews_stmt->execute();
$reviews = $reviews_stmt->get_result();

if ($is_admin) {
    include '../../includes/admin_navbar.php';
} else {
    include '../../includes/inventory_navbar.php';
}
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Reviews for "<?php echo htmlspecialchars($product['product_name']); ?>"</h2>
            <p class="mb-0 text-muted">
                Product ID: <?php echo $product['product_id']; ?> |
                Status: <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?> |
                <?php if ($avg_rating !== null): ?>
                    Average Rating: <?php echo $avg_rating; ?>/5 (<?php echo $total_reviews; ?> reviews)
                <?php else: ?>
                    No reviews yet
                <?php endif; ?>
            </p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">← Back to Products</a>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if ($reviews->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Reviewer</th>
                                <th>Rating</th>
                                <th>Review</th>
                                <th>Date</th>
                                <?php if ($is_admin): ?>
                                    <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($review = $reviews->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></td>
                                    <td>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= intval($review['rating'])): ?>
                                                <span class="text-warning">★</span>
                                            <?php else: ?>
                                                <span class="text-muted">★</span>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        <span class="ms-1">(<?php echo intval($review['rating']); ?>/5)</span>
                                    </td>
                                    <td><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($review['created_at'])); ?></td>
                                    <?php if ($is_admin): ?>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/admin/reviews/delete.php?id=<?php echo $review['review_id']; ?>&product_id=<?php echo $product_id; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Delete this review? This cannot be undone.');">
                                                Delete
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-0">
                    No reviews found for this product yet.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
if (isset($reviews_stmt) && $reviews_stmt instanceof mysqli_stmt) {
    $reviews_stmt->close();
}
?>

<?php include '../../includes/foot.php'; ?>

