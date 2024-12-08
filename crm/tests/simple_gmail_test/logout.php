<?php
session_start();

// Clear all session variables
session_unset();
session_destroy();

// Redirect back to the main page
header('Location: index.php');
exit;
