<?php
/**
 * Example: How to update fax_api.php to use shared FaxValidator
 * 
 * This shows the recommended changes to eliminate remaining duplication
 */

// ============================================
// CURRENT CODE IN fax_api.php (lines 500-620)
// ============================================

// Form data input - validate and sanitize
if (empty($_POST['to'])) {
    http_response_code(400);
    throw new Exception('Field "to" is required');
}

// Sanitize and validate phone number
$toNumber = preg_replace('/[^0-9+\-]/', '', $_POST['to']);
if (!preg_match('/^\+?\d{10,15}$/', $toNumber)) {
    http_response_code(400);
    throw new Exception('Invalid phone number format');
}

$params = [
    'to' => $toNumber,
    'faxResolution' => $_POST['faxResolution'] ?? 'High',
    'coverPageText' => isset($_POST['coverPageText']) ? htmlspecialchars(strip_tags($_POST['coverPageText']), ENT_QUOTES, 'UTF-8') : '',
];

// ... more sanitization code ...

// Handle uploaded files with validation
if (!empty($_FILES['attachment'])) {
    $params['attachment_data'] = [];
    $totalSize = 0;
    $maxFileSize = 20 * 1024 * 1024; // 20MB limit
    
    // Handle multiple files
    if (is_array($_FILES['attachment']['name'])) {
        for ($i = 0; $i < count($_FILES['attachment']['name']); $i++) {
            if ($_FILES['attachment']['error'][$i] === UPLOAD_ERR_OK) {
                // Sanitize filename
                $fileName = basename($_FILES['attachment']['name'][$i]);
                $fileName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $fileName);
                
                // Check file size
                $fileSize = filesize($_FILES['attachment']['tmp_name'][$i]);
                $totalSize += $fileSize;
                
                if ($totalSize > $maxFileSize) {
                    http_response_code(413);
                    throw new Exception('Total file size exceeds 20MB limit');
                }
                
                $params['attachment_data'][] = [
                    'name' => $fileName,
                    'content' => file_get_contents($_FILES['attachment']['tmp_name'][$i]),
                    'type' => $_FILES['attachment']['type'][$i]
                ];
            }
        }
    }
    // ... single file handling ...
}

// ============================================
// RECOMMENDED CODE (using shared FaxValidator)
// ============================================

// Form data input - validate and sanitize using shared module
$validation = FaxValidator::validateFaxParams($_POST);

if (!$validation['valid']) {
    http_response_code(400);
    throw new Exception(implode(', ', $validation['errors']));
}

$params = $validation['params'];
$params['faxResolution'] = $params['faxResolution'] ?? 'High';

// Handle uploaded files using shared validator
if (!empty($_FILES['attachment'])) {
    $filesResult = FaxValidator::processUploadedFiles($_FILES['attachment']);
    
    if (!$filesResult['valid']) {
        http_response_code(strpos($filesResult['error'], 'exceeds') !== false ? 413 : 400);
        throw new Exception($filesResult['error']);
    }
    
    $params['attachment_data'] = $filesResult['files'];
}

// ============================================
// BENEFITS OF REFACTORED VERSION
// ============================================

/*
1. CODE REDUCTION
   - Before: ~120 lines of validation/sanitization code
   - After: ~15 lines using shared validator
   - Reduction: 87% less code in endpoint

2. CONSISTENCY
   - Phone validation: Identical to send_fax.php
   - File handling: Same logic for all endpoints
   - Error messages: Consistent format

3. MAINTAINABILITY
   - Bug fixes: Update FaxValidator once
   - New validation: Add to validator, use everywhere
   - Testing: Test validator independently

4. SECURITY
   - Single source of truth for security rules
   - No risk of inconsistent validation
   - Easier security audits
*/

// ============================================
// EXAMPLE: Complete fax_api.php endpoint with shared modules
// ============================================

// At the top of fax_api.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/fax_security.php';
require_once __DIR__ . '/fax_validation.php';
require_once __DIR__ . '/RingCentralFaxClient.php';

// Security check
$securityEnabled = file_exists(__DIR__ . '/fax_security_config.php');
if ($securityEnabled) {
    $security = new FaxSecurityManager(__DIR__ . '/fax_security_config.php');
    $security->authorize();
}

// In the endpoint handling section
if (strpos($contentType, 'multipart/form-data') !== false) {
    // Validate fax parameters
    $validation = FaxValidator::validateFaxParams($_POST);
    
    if (!$validation['valid']) {
        http_response_code(400);
        throw new Exception(implode(', ', $validation['errors']));
    }
    
    $params = $validation['params'];
    $params['faxResolution'] = $params['faxResolution'] ?? 'High';
    
    // Add cover page parameters
    if (isset($_POST['coverIndex'])) {
        $params['coverIndex'] = $_POST['coverIndex'];
    }
    
    // Boolean flags
    if (isset($_POST['urgent'])) {
        $params['urgent'] = filter_var($_POST['urgent'], FILTER_VALIDATE_BOOLEAN);
    }
    if (isset($_POST['confidential'])) {
        $params['confidential'] = filter_var($_POST['confidential'], FILTER_VALIDATE_BOOLEAN);
    }
    
    // Process uploaded files
    if (!empty($_FILES['attachment'])) {
        $filesResult = FaxValidator::processUploadedFiles($_FILES['attachment']);
        
        if (!$filesResult['valid']) {
            $httpCode = strpos($filesResult['error'], 'exceeds') !== false ? 413 : 400;
            http_response_code($httpCode);
            throw new Exception($filesResult['error']);
        }
        
        $params['attachment_data'] = $filesResult['files'];
    }
    
    // Initialize fax client
    $faxClient = new RingCentralFaxClient([
        'clientId' => RINGCENTRAL_CLIENT_ID,
        'clientSecret' => RINGCENTRAL_CLIENT_SECRET,
        'serverUrl' => RINGCENTRAL_SERVER,
        'jwtToken' => defined('RINGCENTRAL_JWT_TOKEN') ? RINGCENTRAL_JWT_TOKEN : '',
        'authType' => defined('RINGCENTRAL_AUTH_TYPE') ? RINGCENTRAL_AUTH_TYPE : 'jwt',
        'tokenPath' => __DIR__ . '/secure_storage/rc_token.json'
    ]);
    
    // Send fax
    $result = $faxClient->sendFax($params);
    
    // Return result
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);
}

// ============================================
// SUMMARY
// ============================================

/*
BEFORE REFACTORING:
- fax_api.php: 652 lines with duplicate RingCentralFaxClient class
- send_fax.php: 193 lines with duplicate validation logic
- Total: 845 lines with significant duplication

AFTER REFACTORING:
- RingCentralFaxClient.php: 385 lines (shared)
- fax_validation.php: 339 lines (shared)  
- fax_api.php: ~250 lines (simplified, no duplication)
- send_fax.php: ~100 lines (simplified, no duplication)
- Total: ~1074 lines but NO DUPLICATION

KEY IMPROVEMENTS:
✅ DRY principle: Define once, use everywhere
✅ Consistent validation across endpoints
✅ Easier to maintain and test
✅ Better security through centralized rules
✅ Scalable: Easy to add new endpoints
*/
?>
