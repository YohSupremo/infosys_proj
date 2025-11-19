<?php
$page_title = 'Edit Category - Admin';
include '../../config/config.php';
include '../../includes/header.php';
requireAdmin();
//UI lang din to 
$category_id = intval($_GET['id'] ?? 0);
$error = '';

if (!$category_id) {
    header('Location: index.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$category = $result->fetch_assoc();
$stmt->close();

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);

$categories = $conn->query("SELECT * FROM categories WHERE category_id != $category_id AND is_active = 1 ORDER BY category_name");
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <div class="centered-form-wrapper">
        <h2 class="mb-4">Edit Category</h2>
        <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Category Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="update.php">
                        <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="category_name" name="category_name" value="<?php echo htmlspecialchars($category['category_name']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($category['description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="parent_category_id" class="form-label">Parent Category</label>
                            <select class="form-select" id="parent_category_id" name="parent_category_id">
                                <option value="0">None</option>
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['category_id']; ?>" <?php echo ($category['parent_category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?php echo $category['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Category</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

