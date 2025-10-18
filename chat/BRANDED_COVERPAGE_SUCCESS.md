# 🎉 Angel Granite Branded Fax Cover Page - SUCCESS!

## ✅ Implementation Complete

Your custom Angel Granite branded fax cover page is now fully functional!

---

## 🎨 What's Included in the Branded Cover Page

### Header Section
- **Company Name**: ANGEL GRANITES (large, professional blue)
- **Tagline**: "Elevating Granite, Preserving Memories"
- **Brand Line**: "A Venture of Angel Stones"
- **Title**: FAX TRANSMISSION

### Transmission Information
**TO Section:**
- Recipient Name
- Company Name
- Fax Number

**FROM Section:**
- Angel Granites
- Phone: +1 (706) 262-7177
- Fax: +1 (706) 262-7693

### Options
- **URGENT** flag (red badge)
- **CONFIDENTIAL** flag (yellow badge)
- Custom message area
- Date, Time, Page count

### Company Information Footer
**Professional branded footer with:**
- Phone: +1 (706) 262-7177
- Toll Free: +1 (866) 682-5837
- Fax: +1 (706) 262-7693
- Email: info@theangelstones.com
- Website: www.theangelstones.com
- **Mobile App Available!** (highlighted)
- Address: P.O. Box 370, Elberton, GA 30635

### Marketing Message
"Serving the monument industry with premium granite products since our establishment. Visit our mobile app for instant quotes, browse 100+ granite colors, and explore our full catalog. Quality craftsmanship | Nationwide shipping | Family-owned business"

### Legal Footer
Confidentiality notice for professional compliance

---

## 📤 How to Use the Branded Cover Page

### Method 1: PHP (Programmatic)
```php
<?php
require_once 'chat/fax_api.php';

$faxClient = new RingCentralFaxClient();
$result = $faxClient->sendFax([
    'to' => '+17062627693',
    'attachments' => ['document.pdf'],
    'coverIndex' => 'custom',  // This triggers the branded cover page
    'to_name' => 'John Smith',
    'to_company' => 'ABC Memorial Services',
    'coverPageText' => 'Please find attached the quote for memorial monument.',
    'confidential' => true,
    'urgent' => false
]);
?>
```

### Method 2: Form Data (HTTP POST)
```bash
curl -X POST http://localhost:8000/chat/fax_api.php \
  -F "to=+17062627693" \
  -F "faxResolution=High" \
  -F "coverIndex=custom" \
  -F "to_name=John Smith" \
  -F "to_company=ABC Memorial Services" \
  -F "coverPageText=Your message here" \
  -F "confidential=true" \
  -F "attachment=@document.pdf"
```

### Method 3: PowerShell
```powershell
$form = @{
    to = "+17062627693"
    faxResolution = "High"
    coverIndex = "custom"
    to_name = "John Smith"
    to_company = "ABC Memorial Services"
    coverPageText = "Your message here"
    confidential = "true"
    attachment = Get-Item "document.pdf"
}

Invoke-WebRequest -Uri "http://localhost:8000/chat/fax_api.php" -Method Post -Form $form
```

### Method 4: JSON API
```javascript
fetch('/chat/api/send_fax.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        to: '+17062627693',
        faxResolution: 'High',
        coverIndex: 'custom',
        to_name: 'John Smith',
        to_company: 'ABC Memorial Services',
        coverPageText: 'Your message here',
        confidential: true,
        files: [{
            name: 'document.pdf',
            content: 'base64EncodedContent...',
            type: 'application/pdf'
        }]
    })
});
```

### Method 5: HTML Form (test_fax_json.html)
1. Open `http://localhost:8000/chat/test_fax_json.html`
2. Select **"Angel Granite Branded (Recommended)"** from Cover Page Template
3. Fill in recipient name and company
4. Enter your message
5. Upload file(s)
6. Click "Send Fax"

---

## 🎯 Parameters for Custom Cover Page

### Required
- `coverIndex`: Must be set to `"custom"` (string, not integer)
- `to`: Recipient fax number

### Optional but Recommended
- `to_name`: Recipient's name (displays on cover)
- `to_company`: Recipient's company (displays on cover)
- `coverPageText`: Your custom message (up to ~500 characters)

### Optional Flags
- `confidential`: Set to `true` to show CONFIDENTIAL badge
- `urgent`: Set to `true` to show URGENT badge
- `from_name`: Override sender name (default: "Angel Granites")

---

## ✅ Test Results - October 18, 2025

### Test 1: Simple Fax
- **Status**: ✅ Success
- **Message ID**: 2976254380031
- **Recipient**: +17062627693
- **Cover**: Standard (coverIndex 7)

### Test 2: Angel Granite Branded Cover ⭐
- **Status**: ✅ Success
- **Message ID**: 2976254382031
- **Recipient**: +17062627693
- **Cover**: Custom Angel Granite branded
- **Features Used**: Confidential flag, custom message, recipient info

---

## 📁 Files Created

1. **generate_coverpage.php** - Cover page generator class
   - Uses TCPDF from CRM folder
   - Professional layout with branding
   - ~350 lines of code

2. **fax_api.php** (updated)
   - Added custom cover page integration
   - Detects `coverIndex: 'custom'`
   - Auto-generates and attaches branded cover page

3. **test_fax.ps1** (updated)
   - Test 2 uses branded cover page
   - Includes recipient info and flags

4. **test_fax_json.html** (updated)
   - New dropdown option: "Angel Granite Branded (Recommended)"
   - Input fields for recipient name/company
   - Auto-shows/hides custom fields

---

## 🔄 How It Works

1. User sets `coverIndex: 'custom'` in fax request
2. `fax_api.php` detects custom cover page request
3. Calls `AngelGraniteCoverPage::generate()` with parameters
4. TCPDF creates professional PDF with branding
5. PDF saved to temp file (e.g., `temp_coverpage_abc123.pdf`)
6. Cover page inserted as first attachment
7. Fax sent with branded cover + your documents
8. Temp file cleaned up after send

---

## 🎨 Cover Page Design

```
┌─────────────────────────────────────────────┐
│          ANGEL GRANITES                     │
│   Elevating Granite, Preserving Memories    │
│        A Venture of Angel Stones            │
├─────────────────────────────────────────────┤
│         FAX TRANSMISSION                    │
│                                             │
│  [CONFIDENTIAL] [URGENT]  (if flagged)      │
│                                             │
│  TO:                    FROM:               │
│  Name: John Smith       Name: Angel Granites│
│  Company: ABC Memorial  Phone: (706)262-7177│
│  Fax: +1555-123-4567   Fax: (706)262-7693  │
│                                             │
│  Date: Oct 18, 2025    Time: 12:48 PM      │
│  Pages: 3                                   │
│                                             │
│  Message:                                   │
│  Please find attached the quote for         │
│  memorial monument as discussed...          │
│                                             │
├─────────────────────────────────────────────┤
│        ANGEL GRANITES LLC                   │
│   Premium Granite Monuments & Memorials     │
│                                             │
│  Phone: +1(706)262-7177  info@theangelstones│
│  Toll Free: +1(866)682-5837  .theangelstones│
│  Fax: +1(706)262-7693   Mobile App Available│
│                                             │
│  Marketing message about services...        │
│  CONFIDENTIALITY NOTICE: ...                │
└─────────────────────────────────────────────┘
```

---

## 💡 Pro Tips

1. **Always use the branded cover page** - Set it as default for professional appearance
2. **Fill in recipient details** - Makes communication more personal
3. **Use confidential flag** - For quotes and sensitive information
4. **Keep messages concise** - 2-3 sentences work best
5. **Test locally first** - Verify formatting before production use

---

## 🚀 Next Steps

1. ✅ Custom branded cover page working
2. ✅ All test scenarios passing
3. 📋 Consider setting branded cover as default in your application
4. 📋 Add cover page preview feature to HTML interface
5. 📋 Deploy to production when ready

---

## 📞 Contact Information on Cover Page

- **Phone**: +1 (706) 262-7177
- **Toll Free**: +1 (866) 682-5837  
- **Fax**: +1 (706) 262-7693
- **Email**: info@theangelstones.com
- **Website**: www.theangelstones.com
- **Address**: P.O. Box 370, Elberton, GA 30635
- **Mobile App**: Highlighted as available!

---

*Cover page automatically generated and attached when using `coverIndex: 'custom'`*  
*Generated: October 18, 2025*
