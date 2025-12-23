<?php
// Set server name for environment detection
$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';

require_once '../crm/includes/config.php';
require_once '../crm/includes/functions.php';

// Image upload configuration
define('PROMOTIONS_UPLOAD_DIR', __DIR__ . '/../images/promotions/');
define('PROMOTIONS_UPLOAD_URL', '/images/promotions/');
define('MAX_FILE_SIZE', 5242880); // 5MB (allow larger uploads, we'll compress)
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);
define('IMAGE_MAX_WIDTH', 1200);
define('IMAGE_MAX_HEIGHT', 600);
define('IMAGE_QUALITY', 85);
define('OUTPUT_FORMAT', 'webp'); // Always output as WebP for best compression

// Log upload attempt
error_log('Upload attempt - FILES: ' . print_r($_FILES, true));

// Check if file was uploaded
if (!isset($_FILES['file'])) {
    sendResponse(['success' => false, 'error' => 'No file field in request'], 400);
}

if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE in HTML form',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
    ];
    $errorMsg = $errorMessages[$_FILES['file']['error']] ?? 'Unknown upload error';
    error_log('Upload error: ' . $errorMsg);
    sendResponse(['success' => false, 'error' => $errorMsg], 400);
}

$file = $_FILES['file'];
$promotionId = $_POST['promotion_id'] ?? null;

// Validate file size
if ($file['size'] > MAX_FILE_SIZE) {
    sendResponse(['success' => false, 'error' => 'File too large. Maximum size is 5MB'], 400);
}

// Validate file extension
$fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
    sendResponse(['success' => false, 'error' => 'Invalid file type. Only JPG, JPEG, PNG, and WebP allowed'], 400);
}

// Generate unique filename - always use .webp extension
$timestamp = time();
$uniqueId = uniqid();
$filename = $timestamp . '_' . $uniqueId . '.webp';
$uploadPath = PROMOTIONS_UPLOAD_DIR . $filename;

// Create upload directory if it doesn't exist
if (!file_exists(PROMOTIONS_UPLOAD_DIR)) {
    mkdir(PROMOTIONS_UPLOAD_DIR, 0755, true);
}

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    sendResponse(['success' => false, 'error' => 'Failed to save uploaded file'], 500);
}

// Resize and optimize image
try {
    optimizeImage($uploadPath, $fileExt);
} catch (Exception $e) {
    // Continue even if optimization fails
    error_log('Image optimization failed: ' . $e->getMessage());
}

// Generate public URL
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$imageUrl = $baseUrl . PROMOTIONS_UPLOAD_URL . $filename;

// If promotion ID provided, update the promotion
if ($promotionId) {
    try {
        $db = getDbConnection();
        $stmt = $db->prepare("UPDATE promotions SET image_url = ? WHERE id = ?");
        $stmt->execute([$imageUrl, $promotionId]);
    } catch (PDOException $e) {
        // Continue even if update fails
        error_log('Failed to update promotion image: ' . $e->getMessage());
    }
}

sendResponse([
    'success' => true,
    'message' => 'Image uploaded successfully',
    'imageUrl' => $imageUrl,
    'filename' => $filename
], 201);

// Enhanced image optimization function
function optimizeImage($filepath, $ext) {
    $maxWidth = IMAGE_MAX_WIDTH;
    $maxHeight = IMAGE_MAX_HEIGHT;
    $quality = IMAGE_QUALITY;
    
    // Load image based on type
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $image = imagecreatefromjpeg($filepath);
            break;
        case 'png':
            $image = imagecreatefrompng($filepath);
            break;
        case 'webp':
            $image = imagecreatefromwebp($filepath);
            break;
        default:
            return;
    }
    
    if (!$image) {
        return;
    }
    
    // Get current dimensions
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Calculate resize dimensions to fit within max bounds (contain, not crop)
    $targetRatio = $maxWidth / $maxHeight;
    $currentRatio = $width / $height;
    
    if ($currentRatio > $targetRatio) {
        // Image is wider - fit to width
        $newWidth = $maxWidth;
        $newHeight = (int)($maxWidth / $currentRatio);
    } else {
        // Image is taller - fit to height
        $newHeight = $maxHeight;
        $newWidth = (int)($maxHeight * $currentRatio);
    }
    
    // Create new image with calculated dimensions (not forced to exact size)
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG
    if ($ext === 'png') {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
        imagefill($newImage, 0, 0, $transparent);
    }
    
    // Resize to fit (preserve full image, no cropping)
    imagecopyresampled(
        $newImage, $image,
        0, 0, 0, 0,
        $newWidth, $newHeight, $width, $height
    );
    
    // Always save as WebP for best compression (regardless of input format)
    // WebP automatically strips EXIF data and provides superior compression
    imagewebp($newImage, $filepath, $quality);
    
    // imagedestroy() is deprecated in PHP 8.0+ and has no effect
    // Memory is automatically freed
}

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
