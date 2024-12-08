#!/usr/bin/php
<?php
// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/cron_email.log');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/EmailManager.php';

// Lock file to prevent multiple instances
$lockFile = __DIR__ . '/../logs/email_queue.lock';
if (file_exists($lockFile)) {
    $pid = trim(file_get_contents($lockFile));
    if (posix_kill($pid, 0)) {
        error_log("Process already running with PID: $pid");
        exit(1);
    }
}
file_put_contents($lockFile, getmypid());

try {
    // Set unlimited execution time for processing
    set_time_limit(0);
    
    $startTime = microtime(true);
    error_log("Starting email queue processing at " . date('Y-m-d H:i:s'));
    
    $emailManager = new EmailManager($pdo);
    $processed = $emailManager->processEmailQueue();
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    error_log("Completed processing $processed emails in $duration seconds at " . date('Y-m-d H:i:s'));
} catch (Exception $e) {
    error_log("Error processing email queue: " . $e->getMessage());
    exit(1);
} finally {
    // Always remove lock file
    @unlink($lockFile);
}
