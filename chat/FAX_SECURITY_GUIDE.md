# RingCentral Fax API - Security Implementation Guide

## 🔒 Security Overview

**CRITICAL**: Without security, anyone with the URL can send faxes using your RingCentral account, which could lead to:
- ⚠️ Unauthorized fax spam
- ⚠️ Unexpected costs (RingCentral charges per fax)
- ⚠️ Legal liability
- ⚠️ Rate limit exhaustion

---

## Quick Setup (Recommended for Production)

### Step 1: Generate API Key

Run this command from the chat directory:

```bash
cd C:\Users\prabh\newangelstones1123\newangelstones\chat
php fax_security.php
```

This will generate a secure 64-character API key. **Save this key securely!**

### Step 2: Configure Security

Edit `fax_security_config.php` and add your generated key:

```php
<?php
return [
    'api_keys' => [
        'your-generated-64-char-key-here' => [
            'name' => 'Main App',
            'enabled' => true
        ],
    ],
    'security_mode' => 'api_key',
    'rate_limit' => [
        'enabled' => true,
        'max_requests_per_hour' => 50,
        'max_requests_per_day' => 200,
    ],
];
?>
```

### Step 3: Use API Key in Requests

**PowerShell with API Key:**
```powershell
curl.exe -X POST https://theangelstones.com/chat/fax_api.php `
  -H "X-API-Key: your-generated-64-char-key-here" `
  -F "to=+17062627693" `
  -F "coverIndex=custom" `
  -F "to_name=Customer" `
  -F "attachment=@document.pdf"
```

**Or pass as parameter:**
```powershell
curl.exe -X POST https://theangelstones.com/chat/fax_api.php `
  -F "api_key=your-generated-64-char-key-here" `
  -F "to=+17062627693" `
  -F "coverIndex=custom" `
  -F "attachment=@document.pdf"
```

---

## Security Modes

### Mode 1: API Key Authentication (Recommended)

**Best for**: External integrations, mobile apps, third-party services

```php
'security_mode' => 'api_key',
'api_keys' => [
    'key1...' => ['name' => 'Website', 'enabled' => true],
    'key2...' => ['name' => 'Mobile App', 'enabled' => true],
    'key3...' => ['name' => 'CRM System', 'enabled' => true],
],
```

**Usage:**
```bash
# Header method (preferred)
curl -H "X-API-Key: YOUR_KEY" ...

# Parameter method
curl -F "api_key=YOUR_KEY" ...
```

---

### Mode 2: Session Authentication

**Best for**: Logged-in users on your website

```php
'security_mode' => 'session',
'require_session' => true,
'session_user_key' => 'user_id',
```

Only users who are logged into your website (with `$_SESSION['user_id']` set) can send faxes.

---

### Mode 3: IP Whitelist

**Best for**: Internal systems with static IPs

```php
'ip_whitelist' => [
    '203.0.113.0',      // Your office
    '198.51.100.0',     // Your server
],
```

Only requests from whitelisted IPs are allowed.

---

### Mode 4: Combined Security (Strictest)

```php
'security_mode' => 'strict',
'require_session' => true,
'api_keys' => [...],
'ip_whitelist' => [...],
'rate_limit' => ['enabled' => true],
```

Requires ALL security checks to pass.

---

## Rate Limiting

Protects against abuse and cost overruns:

```php
'rate_limit' => [
    'enabled' => true,
    'max_requests_per_hour' => 50,    // Per client
    'max_requests_per_day' => 200,    // Per client
],
```

Rate limits are tracked per:
- API key (if provided)
- User ID (if logged in)
- IP address (fallback)

---

## Managing API Keys

### Generate New Key

```bash
php fax_security.php
```

### Disable a Key

Set `enabled` to `false`:

```php
'api_keys' => [
    'old-key' => ['name' => 'Old App', 'enabled' => false],  // Disabled
    'new-key' => ['name' => 'New App', 'enabled' => true],   // Active
],
```

### Rotate Keys

1. Generate new key
2. Update applications to use new key
3. Disable old key after migration
4. Remove old key from config after grace period

---

## Security Levels

### 🔴 Level 0: Open (DANGEROUS - Testing Only)

**Security**: None
**Setup**: Delete or rename `fax_security_config.php`

```bash
# Anyone can send
curl -X POST https://theangelstones.com/chat/fax_api.php -F "to=+1..." -F "attachment=@file.pdf"
```

⚠️ **Use only for local testing!**

---

### 🟡 Level 1: Rate Limiting Only

**Security**: Prevents spam, but anyone can still send

```php
'security_mode' => 'open',
'rate_limit' => ['enabled' => true],
```

---

### 🟢 Level 2: API Key (Recommended)

**Security**: Only authorized clients with valid API keys

```php
'security_mode' => 'api_key',
```

✅ **Recommended for most use cases**

---

### 🔵 Level 3: API Key + IP Whitelist

**Security**: API key + Must be from allowed IP

```php
'security_mode' => 'api_key',
'ip_whitelist' => ['203.0.113.0'],
```

---

### 🟣 Level 4: Maximum Security

**Security**: API key + Session + IP whitelist + Rate limit

```php
'security_mode' => 'strict',
'require_session' => true,
'ip_whitelist' => [...],
'rate_limit' => ['enabled' => true],
```

---

## Testing Security

### Test 1: Without API Key (Should Fail)

```powershell
curl.exe -X POST https://theangelstones.com/chat/fax_api.php `
  -F "to=+17062627693" `
  -F "attachment=@test.pdf"
```

**Expected Response:**
```json
{
  "success": false,
  "error": "API key required",
  "http_code": 403
}
```

### Test 2: With Valid API Key (Should Succeed)

```powershell
curl.exe -X POST https://theangelstones.com/chat/fax_api.php `
  -H "X-API-Key: your-key-here" `
  -F "to=+17062627693" `
  -F "attachment=@test.pdf"
```

**Expected Response:**
```json
{
  "success": true,
  "message_id": 1234567890,
  ...
}
```

### Test 3: Rate Limit (Should Fail After Limit)

Send requests until you hit the hourly limit.

**Expected Response:**
```json
{
  "success": false,
  "error": "Rate limit exceeded: Maximum 50 requests per hour",
  "http_code": 403
}
```

---

## Security Logs

All security events are logged to `logs/fax_security.log`:

```
[2025-10-18 10:30:00] [INFO] [203.0.113.0] Authorization successful for api_abc123...
[2025-10-18 10:31:00] [WARNING] [198.51.100.5] Authorization failed: Invalid API key
[2025-10-18 10:32:00] [WARNING] [203.0.113.0] Authorization failed: Rate limit exceeded
```

**Monitor this file regularly for suspicious activity!**

---

## Production Deployment Checklist

- [ ] Generate strong API key(s)
- [ ] Configure `fax_security_config.php` with your keys
- [ ] Set `security_mode` to `'api_key'` or higher
- [ ] Enable rate limiting
- [ ] Add IP whitelist (optional but recommended)
- [ ] Test with valid API key
- [ ] Test without API key (should fail)
- [ ] Test rate limiting
- [ ] Update all applications with API keys
- [ ] Monitor `logs/fax_security.log`
- [ ] Set up log rotation
- [ ] Document API keys securely (password manager)

---

## Integration Examples

### PHP Application

```php
$apiKey = 'your-secure-key-here';
$faxUrl = 'https://theangelstones.com/chat/fax_api.php';

$ch = curl_init($faxUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: ' . $apiKey
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'to' => '+17062627693',
    'coverIndex' => 'custom',
    'attachment' => new CURLFile('/path/to/file.pdf')
]);
$response = curl_exec($ch);
```

### JavaScript (Fetch API)

```javascript
const apiKey = 'your-secure-key-here';
const formData = new FormData();
formData.append('to', '+17062627693');
formData.append('coverIndex', 'custom');
formData.append('attachment', fileBlob, 'document.pdf');

fetch('https://theangelstones.com/chat/fax_api.php', {
    method: 'POST',
    headers: {
        'X-API-Key': apiKey
    },
    body: formData
})
.then(response => response.json())
.then(data => console.log(data));
```

### Python

```python
import requests

api_key = 'your-secure-key-here'
url = 'https://theangelstones.com/chat/fax_api.php'

headers = {'X-API-Key': api_key}
files = {'attachment': open('document.pdf', 'rb')}
data = {
    'to': '+17062627693',
    'coverIndex': 'custom',
    'to_name': 'Customer'
}

response = requests.post(url, headers=headers, files=files, data=data)
print(response.json())
```

---

## Best Practices

### 1. API Key Storage

❌ **Never** hardcode keys in public repositories
❌ **Never** commit keys to version control
✅ Store in environment variables
✅ Use secrets management (AWS Secrets Manager, Azure Key Vault)
✅ Use password managers for documentation

### 2. Key Rotation

- Generate new keys every 90 days
- Use multiple keys for different applications
- Maintain key changelog

### 3. Monitoring

- Review security logs weekly
- Set up alerts for:
  - Failed authentication attempts
  - Rate limit violations
  - Unusual activity patterns

### 4. Incident Response

If a key is compromised:
1. Immediately disable the key in config
2. Generate new key
3. Update all applications
4. Review logs for unauthorized usage
5. Contact RingCentral if fraudulent faxes were sent

---

## Troubleshooting

### "API key required" but I'm passing it

**Check:**
- Header name is `X-API-Key` (case-sensitive)
- Or parameter name is `api_key`
- Key is not truncated or has extra spaces

### Rate limit too restrictive

Increase limits in config:
```php
'max_requests_per_hour' => 100,  // Increase
'max_requests_per_day' => 500,   // Increase
```

### Need to bypass security temporarily

Rename config file:
```bash
mv fax_security_config.php fax_security_config.php.disabled
```

Security will be disabled if config file doesn't exist.

---

## Cost Protection

Average costs (approximate):
- **Outbound fax**: $0.04 - $0.07 per page
- **50 faxes/hour × 2 pages** = 100 pages = **$4-7/hour**
- **Without limits**: Potential unlimited costs!

**Rate limiting is your financial safety net!**

---

## Support

For security issues or questions:
- Review logs: `logs/fax_security.log`
- Check config: `fax_security_config.php`
- Test locally with localhost first
- Use verbose curl: `curl -v ...` for debugging

---

**Remember: Security is not optional for production!** 🔒

*Last updated: October 18, 2025*
