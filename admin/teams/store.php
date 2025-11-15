<?php
include '../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_name = sanitize($_POST['team_name'] ?? '');
    $team_code = sanitize($_POST['team_code'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $conference = sanitize($_POST['conference'] ?? '');
    $division = sanitize($_POST['division'] ?? '');
    
    if (empty($team_name) || empty($team_code)) {
        $_SESSION['error'] = 'Team name and code are required.';
        header('Location: create.php');
        exit();
    }
    
    $stmt = $conn->prepare("INSERT INTO nba_teams (team_name, team_code, city, conference, division) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $team_name, $team_code, $city, $conference, $division);
    
    if ($stmt->execute()) {
        $stmt->close();
        header('Location: index.php?success=1');
        exit();
    } else {
        $stmt->close();
        $_SESSION['error'] = 'Failed to add team.';
        header('Location: create.php');
        exit();
    }
} else {
    header('Location: create.php');
    exit();
}
?>
