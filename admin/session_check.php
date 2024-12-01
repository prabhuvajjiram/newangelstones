<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/session_functions.php';

// Check session status if not explicitly skipped
if (!defined('SKIP_SESSION_CHECK')) {
    requireLogin();
}
?>
