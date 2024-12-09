# Angel Stones CRM Configuration Setup

## Configuration Files Setup

1. Copy the template files and rename them:
   ```bash
   cp includes/config.template.php includes/config.php
   ```

2. Edit includes/config.php and update the following values:
   - Database credentials (DB_HOST, DB_NAME, DB_USER, DB_PASS)
   - Gmail OAuth2 credentials (GMAIL_CLIENT_ID, GMAIL_CLIENT_SECRET)
   - Site URL and paths (SITE_URL, CRM_PATH)

3. Gmail OAuth2 Setup:
   a. Go to Google Cloud Console (https://console.cloud.google.com)
   b. Create a new project or select existing one
   c. Enable Gmail API
   d. Create OAuth2 credentials
   e. Set authorized redirect URI to: https://your-domain.com/crm/email_auth_callback.php
   f. Copy Client ID and Client Secret to config.php

## Security Notes

1. Never commit config.php to version control
2. Keep your OAuth2 credentials secure
3. Use HTTPS in production
4. Set secure file permissions:
   ```bash
   chmod 600 includes/config.php
   ```

## Email Configuration

The email system uses Gmail OAuth2 for secure email sending. Features include:
- OAuth2 authentication
- Email tracking
- Analytics
- Template support

## Required PHP Extensions

- PDO
- PDO_MySQL
- curl
- openssl
- json

## Generaing PDF and sending emails
send_quote.php and generatepdf.php to generate PDF and send emails.
