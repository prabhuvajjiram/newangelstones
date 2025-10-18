# Fax API Security - Deployment Checklist

## Files You Need to Upload to Production Server

Upload these 3 files to: `https://theangelstones.com/chat/`

### 1. fax_security.php
- **Path**: `chat/fax_security.php`
- **Purpose**: Security logic
- **Status**: ✅ Created locally

### 2. fax_security_config.php
- **Path**: `chat/fax_security_config.php`
- **Purpose**: Contains your API key
- **Status**: ✅ Created locally with your key
- **Your Key**: `af881b129399b598fde442e214161a205802dc8d400bc62be01f6d04be11970e`

### 3. fax_api.php (already uploaded, but needs to be updated)
- **Path**: `chat/fax_api.php`
- **Purpose**: Main fax endpoint (already has security integration)
- **Status**: ✅ Updated locally with security

---

## Deployment Steps:

### Step 1: Upload Security Files

Using FTP/SFTP or cPanel File Manager, upload:

```
Local → Production
----------------------------------------------------------
chat/fax_security.php         → /chat/fax_security.php
chat/fax_security_config.php  → /chat/fax_security_config.php
chat/fax_api.php              → /chat/fax_api.php (replace existing)
```

### Step 2: Create Required Directories

On the server, create these folders:
```
/chat/logs/
/chat/secure_storage/
```

Set permissions:
```bash
chmod 755 /chat/logs
chmod 755 /chat/secure_storage
```

### Step 3: Test Security

**Test WITHOUT key (should fail):**
```powershell
curl.exe -X POST https://theangelstones.com/chat/fax_api.php `
  -F "to=+17062627693" `
  -F "attachment=@test.pdf"
```

Expected response:
```json
{"success": false, "error": "API key required", "http_code": 403}
```

**Test WITH key (should work):**
```powershell
curl.exe -X POST https://theangelstones.com/chat/fax_api.php `
  -H "X-API-Key: af881b129399b598fde442e214161a205802dc8d400bc62be01f6d04be11970e" `
  -F "to=+17062627693" `
  -F "coverIndex=custom" `
  -F "attachment=@test.pdf"
```

Expected response:
```json
{"success": true, "message_id": 123456789, ...}
```

---

## Security Verification Checklist:

- [ ] Uploaded `fax_security.php` to production
- [ ] Uploaded `fax_security_config.php` to production
- [ ] Updated `fax_api.php` on production
- [ ] Created `/chat/logs/` directory
- [ ] Created `/chat/secure_storage/` directory
- [ ] Tested without API key (should fail)
- [ ] Tested with API key (should work)
- [ ] Saved API key securely (password manager)
- [ ] Updated applications with API key

---

## Your Permanent API Key:

```
af881b129399b598fde442e214161a205802dc8d400bc62be01f6d04be11970e
```

**Save this in a password manager!**

This key is permanent and can be used in:
- Website forms
- Mobile apps
- Automated scripts
- CRM integrations
- Any application that needs to send faxes

---

## Usage Examples with Your Key:

### PowerShell:
```powershell
curl.exe -X POST https://theangelstones.com/chat/fax_api.php `
  -H "X-API-Key: af881b129399b598fde442e214161a205802dc8d400bc62be01f6d04be11970e" `
  -F "to=+17062627693" `
  -F "coverIndex=custom" `
  -F "to_name=Customer Name" `
  -F "attachment=@quote.pdf"
```

### PHP:
```php
$apiKey = 'af881b129399b598fde442e214161a205802dc8d400bc62be01f6d04be11970e';

$ch = curl_init('https://theangelstones.com/chat/fax_api.php');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-API-Key: ' . $apiKey]);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'to' => '+17062627693',
    'coverIndex' => 'custom',
    'attachment' => new CURLFile($filePath)
]);
$response = curl_exec($ch);
```

### JavaScript:
```javascript
const apiKey = 'af881b129399b598fde442e214161a205802dc8d400bc62be01f6d04be11970e';

fetch('https://theangelstones.com/chat/fax_api.php', {
    method: 'POST',
    headers: { 'X-API-Key': apiKey },
    body: formData
});
```

---

## Key Management:

### To Generate Additional Keys:
```bash
php fax_security.php
# Enter name: "Mobile App"
# Copy new key and add to fax_security_config.php
```

### To Disable a Key:
```php
'old-key' => ['name' => 'Old App', 'enabled' => false],
```

### To Rotate Keys (Recommended every 90 days):
1. Generate new key
2. Add to config alongside old key
3. Update applications to use new key
4. After all apps updated, disable old key
5. Remove old key after 30-day grace period

---

## Why Security is Critical:

Without the API key requirement:
- ❌ Anyone can send faxes using your account
- ❌ Costs money (RingCentral charges per page)
- ❌ Could be used for spam
- ❌ Legal liability for content sent

With the API key:
- ✅ Only authorized applications can send
- ✅ Rate limiting prevents abuse
- ✅ Audit trail in security logs
- ✅ Can revoke access instantly

---

## Quick Reference:

**Your API Key (permanent):**
```
af881b129399b598fde442e214161a205802dc8d400bc62be01f6d04be11970e
```

**Use in curl:**
```bash
-H "X-API-Key: af881b129399b598fde442e214161a205802dc8d400bc62be01f6d04be11970e"
```

**Or as parameter:**
```bash
-F "api_key=af881b129399b598fde442e214161a205802dc8d400bc62be01f6d04be11970e"
```

**Security logs location:**
```
/chat/logs/fax_security.log
```

---

*Generated: October 18, 2025*
*Key expires: Never (unless manually changed)*
