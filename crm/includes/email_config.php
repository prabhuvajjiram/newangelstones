<?php
// Google Workspace Email Configuration
define('GOOGLE_WORKSPACE_CLIENT_ID', ''); // Your OAuth 2.0 Client ID
define('GOOGLE_WORKSPACE_CLIENT_SECRET', ''); // Your OAuth 2.0 Client Secret
define('GOOGLE_WORKSPACE_REDIRECT_URI', ''); // Your OAuth 2.0 Redirect URI
define('GOOGLE_WORKSPACE_SCOPES', [
    'https://www.googleapis.com/auth/gmail.send',
    'https://www.googleapis.com/auth/gmail.compose',
    'https://www.googleapis.com/auth/gmail.modify'
]);

// Email Templates Directory
define('EMAIL_TEMPLATES_DIR', __DIR__ . '/../email_templates/');

// Attachments Upload Directory
define('EMAIL_ATTACHMENTS_DIR', __DIR__ . '/../uploads/email_attachments/');

// Maximum attachment size (5MB)
define('MAX_ATTACHMENT_SIZE', 5 * 1024 * 1024);

// Email Queue Settings
define('EMAIL_QUEUE_BATCH_SIZE', 50);
define('EMAIL_QUEUE_MAX_RETRIES', 3);
define('EMAIL_QUEUE_RETRY_DELAY', 300); // 5 minutes in seconds
