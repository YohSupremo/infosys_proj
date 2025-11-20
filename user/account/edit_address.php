<?php
$page_title = 'Edit Address - NBA Shop';
include '../../config/config.php';
include '../../includes/header.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$address_id = intval($_GET['id'] ?? 0); 
$error = '';
$success = '';

if (!$address_id) {
    header('Location: addresses.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM user_addresses WHERE address_id = ? AND user_id = ?");
$stmt->bind_param("ii", $address_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: addresses.php');
    exit();
}

$address = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address_line1 = sanitize($_POST['address_line1'] ?? '');
    $address_line2 = sanitize($_POST['address_line2'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $state = sanitize($_POST['state'] ?? '');
    $postal_code = sanitize($_POST['postal_code'] ?? '');
    $country = sanitize($_POST['country'] ?? 'Philippines');
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    if (empty($address_line1) || empty($city) || empty($state) || empty($postal_code)) {
        $error = 'Please fill in all required fields.';
    } else {
        if ($is_default) {
            $unset_default = $conn->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ? AND address_id != ?");
            $unset_default->bind_param("ii", $user_id, $address_id);
            $unset_default->execute();
            $unset_default->close();
        }
        
        $update_stmt = $conn->prepare("UPDATE user_addresses SET address_line1 = ?, address_line2 = ?, city = ?, state = ?, postal_code = ?, country = ?, is_default = ? WHERE address_id = ? AND user_id = ?");
        $update_stmt->bind_param("ssssssiii", $address_line1, $address_line2, $city, $state, $postal_code, $country, $is_default, $address_id, $user_id);
        
        if ($update_stmt->execute()) {
            $success = 'Address updated successfully!';
            header('Location: addresses.php?success=1');
            exit();
        } else {
            $error = 'Failed to update address.';
        }
        $update_stmt->close();
    }
}
?>

<?php include '../../includes/navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Edit Address</h2>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Address Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="address_line1" class="form-label">Address Line 1 *</label>
                            <input type="text" class="form-control" id="address_line1" name="address_line1" value="<?php echo htmlspecialchars($address['address_line1']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="address_line2" class="form-label">Address Line 2</label>
                            <input type="text" class="form-control" id="address_line2" name="address_line2" value="<?php echo htmlspecialchars($address['address_line2']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="city" class="form-label">City *</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($address['city']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="state" class="form-label">State/Province *</label>
                            <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($address['state']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="postal_code" class="form-label">Postal Code *</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($address['postal_code']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="country" class="form-label">Country</label>
                            <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlspecialchars($address['country']); ?>">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_default" name="is_default" value="1" <?php echo $address['is_default'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_default">Set as default address</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Address</button>
                        <a href="<?php echo BASE_URL; ?>/user/account/addresses.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

