# Gmail Integration Test

This is a simple Gmail integration test using OAuth2 and PHPMailer.

## Server Requirements

- PHP >= 7.4
- Composer
- SSL enabled
- PHP extensions: curl, json, openssl

## Installation

1. Upload all files to your server
2. Navigate to the directory in terminal
3. Run:
   ```bash
   composer install
   ```

## Configuration

1. Make sure your Google Cloud Console project has:
   - Gmail API enabled
   - OAuth consent screen configured
   - Valid OAuth 2.0 credentials
   - Authorized redirect URIs set to match your callback URL

2. Update `config.php` with your credentials:
   - GMAIL_CLIENT_ID
   - GMAIL_CLIENT_SECRET
   - GMAIL_REDIRECT_URI (should match your production URL)

## Troubleshooting

1. If you get SSL errors, ensure your server has a valid SSL certificate
2. Check that all required PHP extensions are enabled
3. Verify that the vendor directory and autoload.php are properly created after composer install
4. Make sure file permissions are set correctly (755 for directories, 644 for files)
