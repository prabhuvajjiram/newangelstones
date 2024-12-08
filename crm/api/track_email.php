<?php
require_once '../includes/config.php';
require_once '../includes/EmailManager.php';

// Set headers for the tracking pixel
header('Content-Type: image/gif');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output a 1x1 transparent GIF
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

// If we have a tracking ID, log the open
if (isset($_GET['id'])) {
    try {
        $emailManager = new EmailManager($pdo);
        $emailManager->logEmailOpen($_GET['id']);
    } catch (Exception $e) {
        // Log error but don't output anything to keep the image working
        error_log('Email tracking error: ' . $e->getMessage());
    }
}
