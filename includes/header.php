<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'NBA Shop - Official NBA Apparel'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php 
    // Determine correct CSS path relative to the including script
    // Assets folder is always at root level
    $script_path = $_SERVER['PHP_SELF'];
    $depth = substr_count($script_path, '/') - 1; // Count directory depth
    if ($depth <= 1) {
        // Root or one level deep
        $css_path = 'assets/css/styles.css';
    } elseif (strpos($script_path, '/user/') !== false) {
        // User subdirectory - need to go up 2 levels
        $css_path = '../../assets/css/styles.css';
    } else {
        // Admin or other subdirectory - go up 1 level
        $css_path = '../assets/css/styles.css';
    }
    ?>
    <link rel="stylesheet" href="<?php echo $css_path; ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
<div class="main-content">

