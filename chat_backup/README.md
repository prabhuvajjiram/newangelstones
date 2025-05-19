# Angel Stones RingCentral Chat Integration

This system integrates RingCentral Team Messaging with the Angel Stones CRM to provide real-time chat functionality for customer support and sales.

## System Architecture

The chat system consists of the following components:

- **JWT Authentication**: Secure JWT authentication with RingCentral (OAuth also supported as fallback)
- **Database Storage**: MySQL tables for storing chat sessions and messages
- **API Endpoints**: RESTful API for sending/receiving messages
- **Webhook Handling**: Process incoming messages from RingCentral
- **Token Management**: Token handling for both JWT and OAuth authentication methods

## Setup Instructions

1. **Database Setup**: Run `init_database.php` to create necessary tables
2. **Authentication**: JWT authentication is configured automatically without agent intervention
3. **Testing**: Use `test_chat.html` to test the chat functionality

## Directory Structure

- `/api`: API endpoints for chat functionality
- `/assets`: CSS, JS, and image assets
- `/vendor`: RingCentral SDK and dependencies
- `/secure_storage`: Secure storage for OAuth tokens

## API Endpoints

- `api/send_message.php`: Send messages to RingCentral
- `api/get_messages.php`: Retrieve chat messages
- `api/poll_messages.php`: Poll for new messages
- `api/webhook.php`: Handle incoming messages from RingCentral
- `api/create_dedicated_chat.php`: Create dedicated chat rooms

## Authentication Methods

### JWT Authentication (Primary Method)

1. System uses the JWT token configured in `config.php`
2. No user intervention required - authentication happens automatically
3. Token is validated and used to obtain an access token
4. Access token is stored in `/secure_storage/rc_token.json`

### OAuth Authentication (Fallback)

1. User visits `authorize.php`
2. RingCentral OAuth authorization page loads
3. User approves the authorization
4. RingCentral redirects to `callback.php`
5. Token is stored in `/secure_storage/rc_token.json`

## Database Schema

The system uses four main tables:

- `chat_sessions`: Stores chat session information
- `chat_messages`: Stores individual chat messages
- `chat_teams`: Stores RingCentral team information
- `chat_settings`: Stores system settings

## Maintenance

- **JWT Configuration**: Update the JWT token in `config.php` when needed
- **Token Refresh**: JWT tokens are long-lived, but OAuth tokens need periodic refresh
- **Authentication Status**: Check `auth_status.php` to verify authentication status

## Troubleshooting

If you encounter issues with the chat system:

1. Check `ringcentral_chat.log` for error messages
2. Verify database connectivity
3. Ensure RingCentral credentials are correct
4. Verify token status in `auth_status.php`
5. Check if JWT token is properly configured in `config.php`
6. If using OAuth, re-authenticate if token is expired or invalid

## Configuration

Main configuration settings are in `config.php`:

- Database connection details
- RingCentral API credentials
- JWT authentication token and settings
- Authentication type (JWT or OAuth)
- Chat widget appearance settings
- CORS configuration

### JWT Authentication Configuration

JWT authentication requires the following settings in `config.php`:

```php
// JWT Authentication settings
define('RINGCENTRAL_JWT_TOKEN', 'your-jwt-token-here');
define('RINGCENTRAL_AUTH_TYPE', 'jwt'); // Use 'jwt' instead of 'oauth'
```

Generate a JWT token in the RingCentral Developer Console and add it to the configuration.
