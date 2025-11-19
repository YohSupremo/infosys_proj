<?php
include '../../config/config.php';
requireAdmin();
// delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_id = intval($_POST['team_id'] ?? 0);
    
    if ($team_id > 0) {
        $delete_stmt = $conn->prepare("DELETE FROM nba_teams WHERE team_id = ?");
        $delete_stmt->bind_param("i", $team_id);
        $delete_stmt->execute();
        $delete_stmt->close();
    }
}

header('Location: index.php');
exit();
?>

