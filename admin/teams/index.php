<?php
$page_title = 'NBA Teams - Admin';
include '../../config/config.php';
include '../../includes/header.php';
requireAdmin();
$teams = $conn->query("SELECT * FROM nba_teams ORDER BY team_name");
?>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>NBA Teams</h2>
        <a href="create.php" class="btn btn-primary">Add New Team</a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Team Name</th>
                            <th>Code</th>
                            <th>City</th>
                            <th>Conference</th>
                            <th>Division</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($teams->num_rows > 0): ?>
                            <?php while ($team = $teams->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $team['team_id']; ?></td>
                                    <td><?php echo htmlspecialchars($team['team_name']); ?></td>
                                    <td><?php echo htmlspecialchars($team['team_code']); ?></td>
                                    <td><?php echo htmlspecialchars($team['city'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($team['conference'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($team['division'] ?: 'N/A'); ?></td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $team['team_id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form method="POST" action="delete.php" class="d-inline">
                                            <input type="hidden" name="team_id" value="<?php echo $team['team_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this team?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No teams found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/foot.php'; ?>

