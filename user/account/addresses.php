<?php
$page_title = 'My Addresses - NBA Shop';
include '../../config/config.php';
include '../../includes/header.php';
requireLogin();

$user_id = $_SESSION['user_id'];

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['error'], $_SESSION['success']);

$stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include '../../includes/navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">My Addresses</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <div class="mb-3">
        <a href="<?php echo BASE_URL; ?>/user/account/add_address.php" class="btn btn-primary">Add New Address</a>
    </div>
    
    <div class="row">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($address = $result->fetch_assoc()): ?>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <?php if ($address['is_default']): ?>
                                <span class="badge badge-success mb-2">Default Address</span>
                            <?php endif; ?>
                            <h5><?php echo htmlspecialchars($address['address_line1']); ?></h5>
                            <?php if ($address['address_line2']): ?>
                                <p class="mb-1"><?php echo htmlspecialchars($address['address_line2']); ?></p>
                            <?php endif; ?>
                            <p class="mb-1"><?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?> <?php echo htmlspecialchars($address['postal_code']); ?></p>
                            <p class="mb-1"><?php echo htmlspecialchars($address['country']); ?></p>
                            <div class="mt-3">
                                <a href="<?php echo BASE_URL; ?>/user/account/edit_address.php?id=<?php echo $address['address_id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="delete_address.php" class="d-inline">
                                    <input type="hidden" name="address_id" value="<?php echo $address['address_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this address?')">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">No addresses found. Add your first address to get started.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

