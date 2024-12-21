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
    
    if (!$productId) {
        throw new Exception('Product ID is required');
    }
    
    // Check if product exists
    $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
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
                
                // Calculate new dimensions (max 1200px)
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
                
                error_log("New dimensions: {$newWidth}x{$newHeight}");
                
                // Create new image with new dimensions
                $newImage = imagecreatetruecolor($newWidth, $newHeight);
                
                // Handle transparency for PNG
                if ($mimeType === 'image/png') {
                    imagealphablending($newImage, false);
                    imagesavealpha($newImage, true);
                    $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                    imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
                }
                
                // Resize image
                imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                
                // Save optimized image
                $saveResult = false;
                switch ($mimeType) {
                    case 'image/jpeg':
                        $saveResult = imagejpeg($newImage, $fullPath, 85);
                        break;
                    case 'image/png':
                        $saveResult = imagepng($newImage, $fullPath, 8);
                        break;
                    case 'image/gif':
                        $saveResult = imagegif($newImage, $fullPath);
                        break;
                }
                
                error_log("Save result: " . ($saveResult ? "success" : "failed"));
                
                if (!$saveResult) {
                    throw new Exception('Failed to save optimized image');
                }
                
                // Free up memory
                imagedestroy($image);
                imagedestroy($newImage);
                
                // Save image record to database
                $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, created_at) VALUES (?, ?, NOW())");
                $imagePath = 'images/products/' . $filename;
                $result = $stmt->execute([$productId, $imagePath]);
                
                error_log("Database insert result: " . ($result ? "success" : "failed"));
                
                if (!$result) {
                    throw new Exception('Failed to save image record to database');
                }
                
                $uploadedImages[] = [
                    'id' => $pdo->lastInsertId(),
                    'path' => $imagePath,
                    'name' => $file['name']
                ];
                
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
