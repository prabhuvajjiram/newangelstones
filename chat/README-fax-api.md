# RingCentral Fax API - Standalone Implementation

A complete, standalone PHP implementation for sending faxes via the RingCentral API.

## 📋 Features

- ✅ **JWT Authentication** - Automatic authentication using JWT token
- ✅ **OAuth Support** - Fallback to OAuth authentication
- ✅ **Multiple Recipients** - Send faxes to multiple fax numbers
- ✅ **Multiple Attachments** - Attach multiple files (PDF, DOC, images)
- ✅ **Cover Pages** - Add custom cover page text and templates
- ✅ **High/Low Resolution** - Control fax quality
- ✅ **Status Tracking** - Check fax delivery status
- ✅ **File Upload Support** - Direct file upload via web interface
- ✅ **RESTful API** - Can be used as REST endpoint
- ✅ **Comprehensive Logging** - Detailed logs in `fax_api.log`

## 🚀 Quick Start

### 1. Prerequisites

- PHP 7.4 or higher
- cURL extension enabled
- RingCentral account with fax capability
- JWT token or OAuth credentials configured in `config.php`

### 2. Configuration

The fax API uses the same RingCentral credentials from your existing `config.php`:

```php
// Already configured in config.php
define('RINGCENTRAL_CLIENT_ID', 'your_client_id');
define('RINGCENTRAL_CLIENT_SECRET', 'your_client_secret');
define('RINGCENTRAL_JWT_TOKEN', 'your_jwt_token');
define('RINGCENTRAL_AUTH_TYPE', 'jwt'); // or 'oauth'
```

### 3. Basic Usage

#### Send a Simple Fax (PHP)

```php
require_once 'fax_api.php';

$faxClient = new RingCentralFaxClient([
    'clientId' => RINGCENTRAL_CLIENT_ID,
    'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
    'jwtToken' => RINGCENTRAL_JWT_TOKEN,
    'authType' => 'jwt'
]);

$result = $faxClient->sendFax([
    'to' => '+14155551234',
    'attachments' => ['/path/to/document.pdf'],
    'faxResolution' => 'High',
    'coverPageText' => 'Please find attached document.'
]);

if ($result['success']) {
    echo "Fax sent! Message ID: " . $result['message_id'];
} else {
    echo "Error: " . $result['error'];
}
```

#### Send Fax via REST API (cURL)

```bash
curl -X POST http://yourdomain.com/chat/fax_api.php \
  -F "to=+14155551234" \
  -F "faxResolution=High" \
  -F "coverPageText=Please find attached" \
  -F "attachment=@/path/to/document.pdf"
```

#### Send Fax via REST API (JavaScript)

```javascript
const formData = new FormData();
formData.append('to', '+14155551234');
formData.append('faxResolution', 'High');
formData.append('coverPageText', 'Please find attached');
formData.append('attachment', fileInput.files[0]);

const response = await fetch('fax_api.php', {
    method: 'POST',
    body: formData
});

const result = await response.json();
console.log(result);
```

## 📖 API Documentation

### Class: `RingCentralFaxClient`

#### Constructor

```php
$faxClient = new RingCentralFaxClient(array $config)
```

**Config Parameters:**
- `clientId` (string) - RingCentral client ID
- `clientSecret` (string) - RingCentral client secret
- `serverUrl` (string) - RingCentral server URL (default: https://platform.ringcentral.com)
- `jwtToken` (string) - JWT token for authentication
- `authType` (string) - Authentication type: 'jwt' or 'oauth'
- `tokenPath` (string) - Path to token storage file
- `logFile` (string) - Path to log file

#### Method: `sendFax()`

```php
$result = $faxClient->sendFax(array $params)
```

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `to` | string\|array | ✓ | Recipient fax number(s) with country code |
| `attachments` | array | * | Array of file paths to send |
| `attachment_data` | array | * | Array of file data (see below) |
| `faxResolution` | string | | 'High' or 'Low' (default: 'High') |
| `coverPageText` | string | | Text to display on cover page |
| `coverIndex` | int | | Cover page template (0-4) |

*Either `attachments` or `attachment_data` is required.

**Attachment Data Format:**
```php
[
    [
        'name' => 'document.pdf',
        'content' => $binaryContent,
        'type' => 'application/pdf'
    ]
]
```

**Return Value:**
```php
[
    'success' => true,
    'message_id' => '123456789',
    'uri' => '/restapi/v1.0/account/.../message-store/123456789',
    'data' => [...] // Full API response
]
```

#### Method: `getFaxStatus()`

```php
$status = $faxClient->getFaxStatus(string $messageId)
```

**Parameters:**
- `messageId` (string) - Message ID from sendFax response

**Return Value:**
```php
[
    'success' => true,
    'data' => [
        'messageStatus' => 'Sent',
        'direction' => 'Outbound',
        'creationTime' => '2025-10-18T10:30:00.000Z',
        'lastModifiedTime' => '2025-10-18T10:35:00.000Z'
    ]
]
```

## 📝 Usage Examples

### Example 1: Send Fax with Multiple Pages

```php
$result = $faxClient->sendFax([
    'to' => '+14155551234',
    'attachments' => [
        '/path/to/page1.pdf',
        '/path/to/page2.pdf',
        '/path/to/page3.pdf'
    ],
    'faxResolution' => 'High'
]);
```

### Example 2: Send to Multiple Recipients

```php
$result = $faxClient->sendFax([
    'to' => [
        ['phoneNumber' => '+14155551234'],
        ['phoneNumber' => '+14155555678'],
        ['phoneNumber' => '+14155559999']
    ],
    'attachments' => ['/path/to/document.pdf'],
    'coverPageText' => 'Broadcast message to all offices'
]);
```

### Example 3: Send Fax with Cover Page

```php
$result = $faxClient->sendFax([
    'to' => '+14155551234',
    'attachments' => ['/path/to/invoice.pdf'],
    'coverPageText' => 'Invoice #12345 - Due: 30 days',
    'coverIndex' => 1 // Use template #1
]);
```

### Example 4: Send Binary Content

```php
// Generate PDF content dynamically
$pdfContent = generatePDFContent(); // Your PDF generation function

$result = $faxClient->sendFax([
    'to' => '+14155551234',
    'attachment_data' => [
        [
            'name' => 'report.pdf',
            'content' => $pdfContent,
            'type' => 'application/pdf'
        ]
    ]
]);
```

### Example 5: Check Fax Status

```php
// Send fax
$result = $faxClient->sendFax([...]);

if ($result['success']) {
    // Wait a few seconds
    sleep(5);
    
    // Check status
    $status = $faxClient->getFaxStatus($result['message_id']);
    
    echo "Status: " . $status['data']['messageStatus'];
}
```

## 🌐 REST API Endpoint

When accessed directly via HTTP, `fax_api.php` acts as a REST endpoint.

### Send Fax (POST)

**Endpoint:** `POST /chat/fax_api.php`

**Content-Type:** `multipart/form-data` or `application/json`

#### Using multipart/form-data:

```bash
curl -X POST http://yourdomain.com/chat/fax_api.php \
  -F "to=+14155551234" \
  -F "faxResolution=High" \
  -F "coverPageText=Important Document" \
  -F "coverIndex=1" \
  -F "attachment=@document1.pdf" \
  -F "attachment=@document2.pdf"
```

#### Using application/json:

```bash
curl -X POST http://yourdomain.com/chat/fax_api.php \
  -H "Content-Type: application/json" \
  -d '{
    "to": "+14155551234",
    "faxResolution": "High",
    "coverPageText": "Important Document",
    "attachments": ["/server/path/to/document.pdf"]
  }'
```

**Response:**
```json
{
  "success": true,
  "message_id": "123456789",
  "uri": "/restapi/v1.0/account/~/extension/~/message-store/123456789",
  "data": { ... }
}
```

## 🧪 Testing

### Web Interface

Open `test_fax.html` in your browser:

```
http://yourdomain.com/chat/test_fax.html
```

Features:
- Upload multiple files
- Add cover page text
- Select resolution
- View results in real-time

### Command Line Examples

Run the example script:

```bash
php fax_example.php
```

## 📁 File Structure

```
chat/
├── fax_api.php          # Main fax API class and endpoint
├── fax_example.php      # PHP usage examples
├── test_fax.html        # Web-based test interface
├── fax_api.log          # Fax API logs (auto-created)
├── config.php           # RingCentral credentials
└── secure_storage/
    └── rc_token.json    # Access token storage
```

## 🔒 Security Features

- **Secure Token Storage** - Tokens stored in `/secure_storage/` directory
- **CORS Protection** - Only whitelisted domains can access API
- **Direct Access Prevention** - Config files protected from direct access
- **SSL/TLS** - All API calls use HTTPS
- **Automatic Token Refresh** - Expired tokens automatically renewed

## 📊 Supported File Types

- **Documents:** PDF, DOC, DOCX, TXT
- **Spreadsheets:** XLS, XLSX
- **Images:** JPG, JPEG, PNG, GIF, TIF, TIFF

Maximum file size depends on your RingCentral account limits.

## 🐛 Troubleshooting

### Authentication Failed

**Problem:** Getting authentication errors

**Solution:**
1. Verify JWT token in `config.php` is valid
2. Check token expiration date
3. Try regenerating JWT token in RingCentral Developer Console
4. Check `fax_api.log` for detailed error messages

### Fax Not Sending

**Problem:** Fax returns success but doesn't send

**Solution:**
1. Verify fax number format includes country code (e.g., +1234567890)
2. Check RingCentral account has fax capability enabled
3. Verify recipient's fax machine is operational
4. Use `getFaxStatus()` to check delivery status

### File Upload Issues

**Problem:** Files not uploading via web interface

**Solution:**
1. Check PHP `upload_max_filesize` and `post_max_size` settings
2. Verify file permissions on server
3. Check browser console for JavaScript errors
4. Ensure file types are supported

### CORS Errors

**Problem:** "Access-Control-Allow-Origin" errors

**Solution:**
1. Add your domain to `$allowedOrigins` array in `fax_api.php`
2. Verify you're using POST method, not GET
3. Check if OPTIONS preflight request is allowed

## 📝 Logging

All fax operations are logged to `fax_api.log`:

```
[2025-10-18 14:30:00] [INFO] Authenticating with JWT...
[2025-10-18 14:30:01] [INFO] JWT authentication successful
[2025-10-18 14:30:02] [INFO] Sending fax to: ["+14155551234"]
[2025-10-18 14:30:05] [INFO] Fax sent successfully. Message ID: 123456789
```

## 🔄 Token Refresh

Tokens are automatically refreshed when expired:

- **JWT tokens** - Long-lived, automatically exchanged for access tokens
- **OAuth tokens** - Refreshed using refresh token before expiration
- **Access tokens** - Stored in `/secure_storage/rc_token.json`

## 📚 Additional Resources

- [RingCentral API Reference](https://developers.ringcentral.com/api-reference)
- [RingCentral Fax API Documentation](https://developers.ringcentral.com/api-reference/Fax/createFaxMessage)
- [RingCentral Developer Portal](https://developers.ringcentral.com)

## 💡 Tips & Best Practices

1. **Use High Resolution** for important documents
2. **Test with Low Resolution** during development to save time
3. **Include Cover Pages** for professional appearance
4. **Always validate phone numbers** before sending
5. **Check status** after sending to confirm delivery
6. **Log all operations** for troubleshooting
7. **Use multipart uploads** for large files

## 🆘 Support

If you encounter issues:

1. Check `fax_api.log` for error details
2. Verify RingCentral credentials and permissions
3. Test with `test_fax.html` web interface
4. Review API response error messages

## 📄 License

This implementation is provided as-is for use with RingCentral services.
