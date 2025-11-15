<?php
$page_title = 'Edit Team - Admin';
include '../../config/config.php';
include '../../includes/header.php';
requireAdmin();

$team_id = intval($_GET['id'] ?? 0);
$error = '';

if (!$team_id) {
    header('Location: index.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM nba_teams WHERE team_id = ?");
$stmt->bind_param("i", $team_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$team = $result->fetch_assoc();
$stmt->close();

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Edit Team</h2>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Team Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="update.php">
                        <input type="hidden" name="team_id" value="<?php echo $team_id; ?>">
                        <div class="mb-3">
                            <label for="team_name" class="form-label">Team Name *</label>
                            <input type="text" class="form-control" id="team_name" name="team_name" value="<?php echo htmlspecialchars($team['team_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="team_code" class="form-label">Team Code *</label>
                            <input type="text" class="form-control" id="team_code" name="team_code" value="<?php echo htmlspecialchars($team['team_code']); ?>" maxlength="10" required>
                        </div>
                        <div class="mb-3">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($team['city']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="conference" class="form-label">Conference</label>
                            <select class="form-select" id="conference" name="conference">
                                <option value="">Select Conference</option>
                                <option value="Eastern" <?php echo $team['conference'] === 'Eastern' ? 'selected' : ''; ?>>Eastern</option>
                                <option value="Western" <?php echo $team['conference'] === 'Western' ? 'selected' : ''; ?>>Western</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="division" class="form-label">Division</label>
                            <input type="text" class="form-control" id="division" name="division" value="<?php echo htmlspecialchars($team['division']); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Update Team</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

