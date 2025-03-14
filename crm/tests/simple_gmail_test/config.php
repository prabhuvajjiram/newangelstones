<?php
// Gmail OAuth2 credentials
define('GMAIL_CLIENT_ID', '');
define('GMAIL_CLIENT_SECRET', '');
define('GMAIL_REDIRECT_URI', 'https://www.theangelstones.com/crm/tests/simple_gmail_test/callback.php');

// OAuth2 endpoints
define('GMAIL_AUTH_URL', 'https://accounts.google.com/o/oauth2/v2/auth');
define('GMAIL_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GMAIL_USER_INFO_URL', 'https://www.googleapis.com/oauth2/v1/userinfo');

// Required scopes for Gmail access
define('GMAIL_SCOPES', [
    'https://mail.google.com/',
    'https://www.googleapis.com/auth/userinfo.email'
]);
