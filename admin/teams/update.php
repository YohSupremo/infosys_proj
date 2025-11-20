<?php
include '../../config/config.php';
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_id = intval($_POST['team_id'] ?? 0);
    $team_name = sanitize($_POST['team_name'] ?? '');
    $team_code = sanitize($_POST['team_code'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $conference = sanitize($_POST['conference'] ?? '');
    $division = sanitize($_POST['division'] ?? '');
    
    if (!$team_id) {
        header('Location: index.php');
        exit();
    }
    
    if (empty($team_name) || empty($team_code)) {
        $_SESSION['error'] = 'Team name and code are required.';
        header('Location: edit.php?id=' . $team_id);
        exit();
    }
    
    $update_stmt = $conn->prepare("UPDATE nba_teams SET team_name = ?, team_code = ?, city = ?, conference = ?, division = ? WHERE team_id = ?");
    $update_stmt->bind_param("sssssi", $team_name, $team_code, $city, $conference, $division, $team_id);
    
    if ($update_stmt->execute()) {
        $update_stmt->close();
        header('Location: index.php?success=1');
        exit();
    } else {
        $update_stmt->close();
        $_SESSION['error'] = 'Failed to update team.';
        header('Location: edit.php?id=' . $team_id);
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}
?>
