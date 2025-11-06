<?php
// This file redirects to edit.php
header('Location: edit.php?id=' . intval($_GET['id'] ?? 0));
exit();
?>

