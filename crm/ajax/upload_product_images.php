<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../session_check.php';

header('Content-Type: application/json');

try {
    error_log("Starting image upload process");
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    $pdo = getDbConnection();
    $productId = $_POST['productId'] ?? null;
    
    error_log("Product ID: " . $productId);
    
    if (!$productId) {
        throw new Exception('Product ID is required');
    }
    
    // Check if product exists
    $stmt = $pdo->prepare("SELECT id, name FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Found product: " . print_r($product, true));
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    $uploadedImages = [];
    $errors = [];
    
    // Define upload directory path
    $baseUploadPath = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'products';
    error_log("Base upload path: " . $baseUploadPath);
    
    // Create directory if it doesn't exist
    if (!file_exists($baseUploadPath)) {
        error_log("Creating directory: " . $baseUploadPath);
        $oldumask = umask(0);
        $result = mkdir($baseUploadPath, 0777, true);
        umask($oldumask);
        if (!$result) {
            throw new Exception('Failed to create upload directory. Error: ' . error_get_last()['message']);
        }
    }
    
    // Check directory permissions
    if (!is_writable($baseUploadPath)) {
        error_log("Directory not writable: " . $baseUploadPath);
        throw new Exception('Upload directory is not writable');
    }
    
    // Handle multiple image uploads
    if (!empty($_FILES['images'])) {
        $files = reArrayFiles($_FILES['images']);
        error_log("Processed files array: " . print_r($files, true));
        
        foreach ($files as $file) {
            try {
                error_log("Processing file: " . print_r($file, true));
                
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('Upload failed with error code: ' . $file['error']);
                }
                
                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($fileInfo, $file['tmp_name']);
                finfo_close($fileInfo);
                
                error_log("File mime type: " . $mimeType);
                
                if (!in_array($mimeType, $allowedTypes)) {
                    throw new Exception('Invalid file type. Only JPG, PNG and GIF are allowed.');
                }
                
                // Clean product name for filename
                $cleanProductName = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', trim($product['name'])));
                
                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = $cleanProductName . '_' . $productId . '_' . uniqid() . '.' . $extension;
                $fullPath = $baseUploadPath . DIRECTORY_SEPARATOR . $filename;
                
                error_log("Full path for saving: " . $fullPath);
                
                // First try to move the uploaded file
                if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                    error_log("Failed to move uploaded file. PHP error: " . error_get_last()['message']);
                    throw new Exception('Failed to save uploaded file');
                }
                
                error_log("File moved successfully to: " . $fullPath);
                
                // Optimize image
                $image = null;
                switch ($mimeType) {
                    case 'image/jpeg':
                        $image = imagecreatefromjpeg($fullPath);
                        break;
                    case 'image/png':
                        $image = imagecreatefrompng($fullPath);
                        break;
                    case 'image/gif':
                        $image = imagecreatefromgif($fullPath);
                        break;
                }
                
                if (!$image) {
                    throw new Exception('Failed to process image');
                }
                
                // Get original dimensions
                $width = imagesx($image);
                $height = imagesy($image);
                
                error_log("Original dimensions: {$width}x{$height}");
                
                // Calculate dimensions for full size (max 1200px)
                $maxWidth = 1200;
                $maxHeight = 1200;
                $ratio = $width / $height;
                
                if ($width > $maxWidth || $height > $maxHeight) {
                    if ($width > $height) {
                        $newWidth = $maxWidth;
                        $newHeight = $maxWidth / $ratio;
                    } else {
                        $newHeight = $maxHeight;
                        $newWidth = $maxHeight * $ratio;
                    }
                } else {
                    $newWidth = $width;
                    $newHeight = $height;
                }
                
                error_log("New dimensions for full size: {$newWidth}x{$newHeight}");
                
                // Create full size image
                $fullSizeImage = imagecreatetruecolor($newWidth, $newHeight);
                
                // Handle transparency for PNG
                if ($mimeType === 'image/png') {
                    imagealphablending($fullSizeImage, false);
                    imagesavealpha($fullSizeImage, true);
                    $transparent = imagecolorallocatealpha($fullSizeImage, 255, 255, 255, 127);
                    imagefilledrectangle($fullSizeImage, 0, 0, $newWidth, $newHeight, $transparent);
                }
                
                // Resize full size image
                imagecopyresampled(
                    $fullSizeImage,
                    $image,
                    0, 0, 0, 0,
                    $newWidth,
                    $newHeight,
                    $width,
                    $height
                );
                
                // Calculate dimensions for thumbnail (300x200)
                $thumbWidth = 300;
                $thumbHeight = 200;
                
                // Create thumbnail image
                $thumbnailImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
                
                // Handle transparency for PNG thumbnails
                if ($mimeType === 'image/png') {
                    imagealphablending($thumbnailImage, false);
                    imagesavealpha($thumbnailImage, true);
                    $transparent = imagecolorallocatealpha($thumbnailImage, 255, 255, 255, 127);
                    imagefilledrectangle($thumbnailImage, 0, 0, $thumbWidth, $thumbHeight, $transparent);
                }
                
                // Calculate thumbnail dimensions to maintain aspect ratio
                $srcX = 0;
                $srcY = 0;
                $srcWidth = $width;
                $srcHeight = $height;
                
                // Crop to 3:2 aspect ratio if needed
                if ($width / $height > 1.5) { // Image is wider than 3:2
                    $srcWidth = $height * 1.5;
                    $srcX = ($width - $srcWidth) / 2;
                } elseif ($width / $height < 1.5) { // Image is taller than 3:2
                    $srcHeight = $width / 1.5;
                    $srcY = ($height - $srcHeight) / 2;
                }
                
                // Create thumbnail
                imagecopyresampled(
                    $thumbnailImage,
                    $image,
                    0, 0, $srcX, $srcY,
                    $thumbWidth,
                    $thumbHeight,
                    $srcWidth,
                    $srcHeight
                );
                
                // Save both versions
                $thumbFilename = 'thumb_' . $filename;
                $thumbPath = $baseUploadPath . DIRECTORY_SEPARATOR . $thumbFilename;
                
                error_log("Saving thumbnail to: " . $thumbPath);
                error_log("Saving full size to: " . $fullPath);
                
                // Save images based on type
                switch ($mimeType) {
                    case 'image/jpeg':
                        $saveResult = imagejpeg($fullSizeImage, $fullPath, 85) && 
                                    imagejpeg($thumbnailImage, $thumbPath, 85);
                        break;
                    case 'image/png':
                        $saveResult = imagepng($fullSizeImage, $fullPath, 8) &&
                                    imagepng($thumbnailImage, $thumbPath, 8);
                        break;
                    case 'image/gif':
                        $saveResult = imagegif($fullSizeImage, $fullPath) &&
                                    imagegif($thumbnailImage, $thumbPath);
                        break;
                }
                
                error_log("Save result: " . ($saveResult ? "success" : "failed"));
                
                if (!$saveResult) {
                    throw new Exception('Failed to save optimized images');
                }
                
                // Free up memory
                imagedestroy($image);
                imagedestroy($fullSizeImage);
                imagedestroy($thumbnailImage);
                
                // Save image record to database with correct paths
                $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, thumb_path, created_at) VALUES (?, ?, ?, NOW())");
                
                // Use correct paths for database storage
                $imagePath = 'images/products/' . $filename;
                $thumbPath = 'images/products/thumb_' . $filename;
                
                error_log("Attempting database insert - Product ID: " . $productId);
                error_log("Image path: " . $imagePath);
                error_log("Thumb path: " . $thumbPath);
                
                try {
                    $result = $stmt->execute([$productId, $imagePath, $thumbPath]);
                    error_log("Database insert result: " . ($result ? "success" : "failed"));
                    
                    if (!$result) {
                        $error = $stmt->errorInfo();
                        error_log("Database error details: " . print_r($error, true));
                        throw new Exception('Failed to save image record to database: ' . $error[2]);
                    }
                    
                    $newId = $pdo->lastInsertId();
                    error_log("New image record ID: " . $newId);
                    
                    // Verify the insert
                    $verifyStmt = $pdo->prepare("SELECT * FROM product_images WHERE id = ?");
                    $verifyStmt->execute([$newId]);
                    $verifyResult = $verifyStmt->fetch(PDO::FETCH_ASSOC);
                    error_log("Verify insert result: " . print_r($verifyResult, true));
                    
                    $uploadedImages[] = [
                        'id' => $newId,
                        'path' => $imagePath,
                        'thumb_path' => $thumbPath,
                        'name' => $file['name']
                    ];
                } catch (PDOException $e) {
                    error_log("PDO Exception: " . $e->getMessage());
                    error_log("Error code: " . $e->getCode());
                    throw new Exception('Database error: ' . $e->getMessage());
                }
                
                error_log("Successfully processed image: " . $file['name']);
                
            } catch (Exception $e) {
                error_log("Error processing file {$file['name']}: " . $e->getMessage());
                $errors[] = $file['name'] . ': ' . $e->getMessage();
            }
        }
    } else {
        error_log("No files were uploaded");
    }
    
    $response = [
        'success' => true,
        'images' => $uploadedImages,
        'errors' => $errors
    ];
    error_log("Final response: " . print_r($response, true));
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Error in upload_product_images.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Helper function to restructure $_FILES array
function reArrayFiles($files) {
    $fileArray = [];
    $fileCount = count($files['name']);
    $fileKeys = array_keys($files);
    
    for ($i = 0; $i < $fileCount; $i++) {
        foreach ($fileKeys as $key) {
            $fileArray[$i][$key] = $files[$key][$i];
        }
    }
    
    return $fileArray;
}
