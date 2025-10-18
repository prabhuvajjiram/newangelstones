# RingCentral Fax API - cURL Command Reference

## Quick Test Commands for Angel Granite Fax API

### Production Endpoint
```
https://theangelstones.com/chat/fax_api.php
https://theangelstones.com/chat/api/send_fax.php
```

---

## 1. Simple Fax (Form Data - No Cover Page)

```bash
curl -X POST https://theangelstones.com/chat/fax_api.php \
  -F "to=+17062627693" \
  -F "faxResolution=High" \
  -F "attachment=@chat/test_document.pdf"
```

### Windows PowerShell:
```powershell
curl.exe -X POST https://theangelstones.com/chat/fax_api.php `
  -F "to=+17062627693" `
  -F "faxResolution=High" `
  -F "attachment=@chat/test_document.pdf"
```

---

## 2. Fax with Angel Granite Branded Cover Page ⭐ (RECOMMENDED)

```bash
curl -X POST https://theangelstones.com/chat/fax_api.php \
  -F "to=+17062627693" \
  -F "faxResolution=High" \
  -F "coverIndex=custom" \
  -F "to_name=John Smith" \
  -F "to_company=ABC Memorial Services" \
  -F "coverPageText=Please find attached the quote for your granite monument. Our premium quality monuments are crafted with care. Contact us for any questions." \
  -F "confidential=true" \
  -F "attachment=@chat/test_document.pdf"
```

### Windows PowerShell:
```powershell
curl.exe -X POST https://theangelstones.com/chat/fax_api.php `
  -F "to=+17062627693" `
  -F "faxResolution=High" `
  -F "coverIndex=custom" `
  -F "to_name=John Smith" `
  -F "to_company=ABC Memorial Services" `
  -F "coverPageText=Please find attached the quote for your granite monument. Our premium quality monuments are crafted with care. Contact us for any questions." `
  -F "confidential=true" `
  -F "attachment=@chat/test_document.pdf"
```

---

## 3. Urgent Fax with Branded Cover

```bash
curl -X POST https://theangelstones.com/chat/fax_api.php \
  -F "to=+17062627693" \
  -F "faxResolution=High" \
  -F "coverIndex=custom" \
  -F "to_name=Jane Doe" \
  -F "to_company=Memorial Chapel" \
  -F "coverPageText=URGENT: Time-sensitive quote attached. Please review and contact us immediately." \
  -F "urgent=true" \
  -F "confidential=true" \
  -F "attachment=@chat/test_document.pdf"
```

### Windows PowerShell:
```powershell
curl.exe -X POST https://theangelstones.com/chat/fax_api.php `
  -F "to=+17062627693" `
  -F "faxResolution=High" `
  -F "coverIndex=custom" `
  -F "to_name=Jane Doe" `
  -F "to_company=Memorial Chapel" `
  -F "coverPageText=URGENT: Time-sensitive quote attached. Please review and contact us immediately." `
  -F "urgent=true" `
  -F "confidential=true" `
  -F "attachment=@chat/test_document.pdf"
```

---

## 4. Multiple Files with Branded Cover

```bash
curl -X POST https://theangelstones.com/chat/fax_api.php \
  -F "to=+17062627693" \
  -F "faxResolution=High" \
  -F "coverIndex=custom" \
  -F "to_name=Customer Name" \
  -F "to_company=Funeral Home" \
  -F "coverPageText=Please find attached: Quote document, color samples, and terms. Total 3 pages." \
  -F "attachment=@chat/test_document.pdf" \
  -F "attachment=@chat/test_document.pdf" \
  -F "attachment=@chat/test_document.pdf"
```

### Windows PowerShell:
```powershell
curl.exe -X POST https://theangelstones.com/chat/fax_api.php `
  -F "to=+17062627693" `
  -F "faxResolution=High" `
  -F "coverIndex=custom" `
  -F "to_name=Customer Name" `
  -F "to_company=Funeral Home" `
  -F "coverPageText=Please find attached: Quote document, color samples, and terms. Total 3 pages." `
  -F "attachment=@chat/test_document.pdf" `
  -F "attachment=@chat/test_document.pdf" `
  -F "attachment=@chat/test_document.pdf"
```

---

## 5. JSON API with Base64 (Using jq for JSON)

First, encode your file to base64:

### Linux/Mac:
```bash
BASE64_CONTENT=$(base64 -w 0 chat/test_document.pdf)

curl -X POST https://theangelstones.com/chat/api/send_fax.php \
  -H "Content-Type: application/json" \
  -d "{
    \"to\": \"+17062627693\",
    \"faxResolution\": \"High\",
    \"coverIndex\": \"custom\",
    \"to_name\": \"Valued Customer\",
    \"to_company\": \"Memorial Services\",
    \"coverPageText\": \"Please find attached your granite monument quote. We appreciate your business.\",
    \"confidential\": true,
    \"files\": [
      {
        \"name\": \"quote.pdf\",
        \"content\": \"$BASE64_CONTENT\",
        \"type\": \"application/pdf\"
      }
    ]
  }"
```

### Windows PowerShell:
```powershell
$fileBytes = [System.IO.File]::ReadAllBytes("chat\test_document.pdf")
$base64Content = [System.Convert]::ToBase64String($fileBytes)

$jsonBody = @{
    to = "+17062627693"
    faxResolution = "High"
    coverIndex = "custom"
    to_name = "Valued Customer"
    to_company = "Memorial Services"
    coverPageText = "Please find attached your granite monument quote. We appreciate your business."
    confidential = $true
    files = @(
        @{
            name = "quote.pdf"
            content = $base64Content
            type = "application/pdf"
        }
    )
} | ConvertTo-Json -Depth 10

curl.exe -X POST https://theangelstones.com/chat/api/send_fax.php `
  -H "Content-Type: application/json" `
  -d $jsonBody
```

---

## 6. Standard RingCentral Cover Page (Template 1)

```bash
curl -X POST https://theangelstones.com/chat/fax_api.php \
  -F "to=+17062627693" \
  -F "faxResolution=High" \
  -F "coverIndex=1" \
  -F "coverPageText=Standard cover page message from Angel Granites." \
  -F "attachment=@chat/test_document.pdf"
```

### Windows PowerShell:
```powershell
curl.exe -X POST https://theangelstones.com/chat/fax_api.php `
  -F "to=+17062627693" `
  -F "faxResolution=High" `
  -F "coverIndex=1" `
  -F "coverPageText=Standard cover page message from Angel Granites." `
  -F "attachment=@chat/test_document.pdf"
```

---

## 7. Check Fax Status

After sending a fax, you'll get a message_id. Use it to check status:

```bash
# Replace MESSAGE_ID with actual ID from send response
curl -X GET "https://theangelstones.com/chat/fax_api.php?action=status&message_id=MESSAGE_ID"
```

### Windows PowerShell:
```powershell
curl.exe -X GET "https://theangelstones.com/chat/fax_api.php?action=status&message_id=MESSAGE_ID"
```

---

## Local Testing (Development)

For local testing, start PHP server and use localhost:

```bash
# Start server
cd c:\Users\prabh\newangelstones1123\newangelstones
php -S localhost:8000

# Local endpoint
curl -X POST http://localhost:8000/chat/fax_api.php \
  -F "to=+17062627693" \
  -F "faxResolution=High" \
  -F "coverIndex=custom" \
  -F "to_name=Customer Name" \
  -F "to_company=Company Name" \
  -F "coverPageText=Your message here" \
  -F "attachment=@chat/test_document.pdf"
```

---

## Common Parameters

### Required
- `to`: Recipient fax number (E.164 format: +17062627693)
- `attachment`: File to send (or `files` array for JSON API)

### Optional - Cover Page
- `coverIndex`: 
  - `"custom"` = Angel Granite branded cover (RECOMMENDED)
  - `"0"` to `"4"` = RingCentral templates
  - omit = no cover page
- `coverPageText`: Message on cover page (up to ~500 characters)
- `to_name`: Recipient name (for custom cover)
- `to_company`: Recipient company (for custom cover)
- `from_name`: Sender name (default: "Angel Granites")

### Optional - Flags
- `urgent`: Set to `"true"` for URGENT badge
- `confidential`: Set to `"true"` for CONFIDENTIAL badge
- `faxResolution`: `"High"` (default) or `"Low"`

---

## Testing Tips

1. **For local testing**, start PHP server first:
   ```bash
   cd c:\Users\prabh\newangelstones1123\newangelstones
   php -S localhost:8000
   ```

2. **Check if file exists**:
   ```bash
   ls chat/test_document.pdf
   ```

3. **View response with pretty print**:
   ```bash
   curl ... | jq .
   ```

4. **Save response to file**:
   ```bash
   curl ... > response.json
   ```

5. **Verbose output for debugging**:
   ```bash
   curl -v ...
   ```

---

## Example Success Response

```json
{
  "success": true,
  "message_id": 2976254382031,
  "uri": "https://platform.ringcentral.com/restapi/v1.0/account/63395585031/extension/63395585031/message-store/2976254382031",
  "data": {
    "id": 2976254382031,
    "to": [
      {
        "phoneNumber": "+17062627693",
        "location": "Augusta, GA",
        "messageStatus": "Queued"
      }
    ],
    "type": "Fax",
    "messageStatus": "Queued",
    "faxResolution": "High",
    "faxPageCount": 0,
    "coverIndex": 0
  }
}
```

---

## Example Error Response

```json
{
  "success": false,
  "error": "At least one attachment is required",
  "http_code": 400
}
```

---

## Quick One-Liner (Most Common Use Case)

Send a branded fax with one command:

### Linux/Mac:
```bash
curl -X POST https://theangelstones.com/chat/fax_api.php -F "to=+17062627693" -F "coverIndex=custom" -F "to_name=Customer" -F "coverPageText=Your quote attached." -F "attachment=@document.pdf"
```

### Windows PowerShell:
```powershell
curl.exe -X POST https://theangelstones.com/chat/fax_api.php -F "to=+17062627693" -F "coverIndex=custom" -F "to_name=Customer" -F "coverPageText=Your quote attached." -F "attachment=@document.pdf"
```

---

**Pro Tip**: Always use the Angel Granite branded cover page (`coverIndex=custom`) for professional appearance! 📠✨

*Generated: October 18, 2025*
