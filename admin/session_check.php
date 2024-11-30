<?php
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) && !isset($_SESSION['username']) && !isset($_SESSION['user_role'])) {
    header("Location: login.php");
    exit();
}
?>
