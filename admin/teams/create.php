<?php
$page_title = 'Add Team - Admin';
include '../../config/config.php';
include '../../includes/header.php';
requireAdmin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_name = sanitize($_POST['team_name'] ?? '');
    $team_code = sanitize($_POST['team_code'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $conference = sanitize($_POST['conference'] ?? '');
    $division = sanitize($_POST['division'] ?? '');
    
    if (empty($team_name) || empty($team_code)) {
        $error = 'Team name and code are required.';
    } else {
        $stmt = $conn->prepare("INSERT INTO nba_teams (team_name, team_code, city, conference, division) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $team_name, $team_code, $city, $conference, $division);
        
        if ($stmt->execute()) {
            header('Location: index.php?success=1');
            exit();
        } else {
            $error = 'Failed to add team.';
        }
        $stmt->close();
    }
}
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <h2 class="mb-4">Add New Team</h2>
    
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
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="team_name" class="form-label">Team Name *</label>
                            <input type="text" class="form-control" id="team_name" name="team_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="team_code" class="form-label">Team Code *</label>
                            <input type="text" class="form-control" id="team_code" name="team_code" maxlength="10" required>
                        </div>
                        <div class="mb-3">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city">
                        </div>
                        <div class="mb-3">
                            <label for="conference" class="form-label">Conference</label>
                            <select class="form-select" id="conference" name="conference">
                                <option value="">Select Conference</option>
                                <option value="Eastern">Eastern</option>
                                <option value="Western">Western</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="division" class="form-label">Division</label>
                            <input type="text" class="form-control" id="division" name="division">
                        </div>
                        <button type="submit" class="btn btn-primary">Add Team</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

