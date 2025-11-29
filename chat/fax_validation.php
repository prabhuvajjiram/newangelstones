<?php
/**
 * Fax Input Validation Utilities
 * 
 * Shared validation functions for fax API endpoints
 */

class FaxValidator {
    
    /**
     * Validate and sanitize phone number
     * 
     * @param string $phoneNumber Phone number to validate
     * @return array ['valid' => bool, 'sanitized' => string, 'error' => string|null]
     */
    public static function validatePhoneNumber($phoneNumber) {
        if (empty($phoneNumber)) {
            return [
                'valid' => false,
                'sanitized' => '',
                'error' => 'Phone number is required'
            ];
        }
        
        // Sanitize: keep only digits, +, and hyphens
        $sanitized = preg_replace('/[^0-9+\-]/', '', $phoneNumber);
        
        // Validate format: 10-15 digits with optional + prefix
        // Accepts: +17062627693 or 17062627693 or 7062627693
        if (!preg_match('/^\+?\d{10,15}$/', $sanitized)) {
            return [
                'valid' => false,
                'sanitized' => $sanitized,
                'error' => 'Invalid phone number format. Use 10-15 digits with optional + prefix (e.g., +17062627693 or 7062627693)'
            ];
        }
        
        return [
            'valid' => true,
            'sanitized' => $sanitized,
            'error' => null
        ];
    }
    
    /**
     * Sanitize text input to prevent XSS
     * 
     * @param string $input Text to sanitize
     * @return string Sanitized text
     */
    public static function sanitizeText($input) {
        if (empty($input)) {
            return '';
        }
        
        return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize filename to prevent directory traversal
     * 
     * @param string $filename Filename to sanitize
     * @return string Sanitized filename
     */
    public static function sanitizeFilename($filename) {
        if (empty($filename)) {
            return '';
        }
        
        // Remove directory components
        $filename = basename($filename);
        
        // Keep only safe characters
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);
        
        return $filename;
    }
    
    /**
     * Validate file size
     * 
     * @param int $fileSize Size in bytes
     * @param int $maxSize Maximum allowed size in bytes (default 20MB)
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public static function validateFileSize($fileSize, $maxSize = 20971520) {
        if ($fileSize > $maxSize) {
            $maxMB = round($maxSize / 1048576, 1);
            return [
                'valid' => false,
                'error' => "File size exceeds {$maxMB}MB limit"
            ];
        }
        
        return [
            'valid' => true,
            'error' => null
        ];
    }
    
    /**
     * Validate and sanitize all fax parameters
     * 
     * @param array $params Input parameters
     * @return array ['valid' => bool, 'params' => array, 'errors' => array]
     */
    public static function validateFaxParams(array $params) {
        $errors = [];
        $sanitized = [];
        
        // Validate phone number
        if (isset($params['to'])) {
            $phoneResult = self::validatePhoneNumber($params['to']);
            if (!$phoneResult['valid']) {
                $errors[] = $phoneResult['error'];
            } else {
                $sanitized['to'] = $phoneResult['sanitized'];
            }
        } else {
            $errors[] = 'Recipient phone number (to) is required';
        }
        
        // Sanitize text fields
        $textFields = ['to_name', 'to_company', 'from_name', 'coverPageText'];
        foreach ($textFields as $field) {
            if (isset($params[$field])) {
                $sanitized[$field] = self::sanitizeText($params[$field]);
            }
        }
        
        // Copy other safe fields
        $safeFields = ['faxResolution', 'coverIndex', 'urgent', 'confidential'];
        foreach ($safeFields as $field) {
            if (isset($params[$field])) {
                $sanitized[$field] = $params[$field];
            }
        }
        
        // Convert boolean flags
        if (isset($sanitized['urgent'])) {
            $sanitized['urgent'] = filter_var($sanitized['urgent'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($sanitized['confidential'])) {
            $sanitized['confidential'] = filter_var($sanitized['confidential'], FILTER_VALIDATE_BOOLEAN);
        }
        
        return [
            'valid' => empty($errors),
            'params' => $sanitized,
            'errors' => $errors
        ];
    }
    
    /**
     * Process and validate uploaded files
     * 
     * @param array $files $_FILES array data
     * @param int $maxTotalSize Maximum total size in bytes (default 20MB)
     * @return array ['valid' => bool, 'files' => array, 'error' => string|null]
     */
    public static function processUploadedFiles(array $files, $maxTotalSize = 20971520) {
        $processed = [];
        $totalSize = 0;
        
        // Handle single file upload
        if (!is_array($files['name'])) {
            if ($files['error'] === UPLOAD_ERR_OK) {
                // Sanitize filename
                $fileName = self::sanitizeFilename($files['name']);
                
                // Check file size
                $fileSize = filesize($files['tmp_name']);
                $sizeCheck = self::validateFileSize($fileSize, $maxTotalSize);
                
                if (!$sizeCheck['valid']) {
                    return [
                        'valid' => false,
                        'files' => [],
                        'error' => $sizeCheck['error']
                    ];
                }
                
                $processed[] = [
                    'name' => $fileName,
                    'content' => file_get_contents($files['tmp_name']),
                    'type' => $files['type']
                ];
            }
        } else {
            // Handle multiple file uploads
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    // Sanitize filename
                    $fileName = self::sanitizeFilename($files['name'][$i]);
                    
                    // Check file size
                    $fileSize = filesize($files['tmp_name'][$i]);
                    $totalSize += $fileSize;
                    
                    if ($totalSize > $maxTotalSize) {
                        $maxMB = round($maxTotalSize / 1048576, 1);
                        return [
                            'valid' => false,
                            'files' => [],
                            'error' => "Total file size exceeds {$maxMB}MB limit"
                        ];
                    }
                    
                    $processed[] = [
                        'name' => $fileName,
                        'content' => file_get_contents($files['tmp_name'][$i]),
                        'type' => $files['type'][$i]
                    ];
                }
            }
        }
        
        if (empty($processed)) {
            return [
                'valid' => false,
                'files' => [],
                'error' => 'At least one valid file is required'
            ];
        }
        
        return [
            'valid' => true,
            'files' => $processed,
            'error' => null
        ];
    }
    
    /**
     * Process and validate base64-encoded files
     * 
     * @param array $files Array of file objects with name, content, type
     * @param int $maxTotalSize Maximum total size in bytes (default 20MB)
     * @return array ['valid' => bool, 'files' => array, 'error' => string|null]
     */
    public static function processBase64Files(array $files, $maxTotalSize = 20971520) {
        if (empty($files)) {
            return [
                'valid' => false,
                'files' => [],
                'error' => 'At least one file is required'
            ];
        }
        
        $processed = [];
        $totalSize = 0;
        
        foreach ($files as $file) {
            if (empty($file['name']) || empty($file['content'])) {
                return [
                    'valid' => false,
                    'files' => [],
                    'error' => 'Each file must have "name" and "content" fields'
                ];
            }
            
            // Sanitize filename
            $fileName = self::sanitizeFilename($file['name']);
            
            // Decode base64 content
            $content = base64_decode($file['content']);
            
            if ($content === false) {
                return [
                    'valid' => false,
                    'files' => [],
                    'error' => "Invalid base64 content in file: {$fileName}"
                ];
            }
            
            // Check file size
            $fileSize = strlen($content);
            $totalSize += $fileSize;
            
            if ($totalSize > $maxTotalSize) {
                $maxMB = round($maxTotalSize / 1048576, 1);
                return [
                    'valid' => false,
                    'files' => [],
                    'error' => "Total file size exceeds {$maxMB}MB limit"
                ];
            }
            
            $processed[] = [
                'name' => $fileName,
                'content' => $content,
                'type' => $file['type'] ?? 'application/pdf'
            ];
        }
        
        return [
            'valid' => true,
            'files' => $processed,
            'error' => null
        ];
    }
}
