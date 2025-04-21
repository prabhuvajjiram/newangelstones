<?php
// Simple authentication check
$admin_password = "angelstones2025"; // Change this to your desired password
$authenticated = false;

if (isset($_POST['password'])) {
    if ($_POST['password'] === $admin_password) {
        $authenticated = true;
    } else {
        $error = "Invalid password";
    }
}

// Handle file upload
$message = '';
$error = '';

if ($authenticated && isset($_POST['upload_container']) && isset($_FILES['container_file'])) {
    $containersDir = __DIR__ . '/storage/containers';
    
    // Create directory if it doesn't exist
    if (!file_exists($containersDir)) {
        mkdir($containersDir, 0755, true);
    }
    
    $file = $_FILES['container_file'];
    
    // Check for errors
    if ($file['error'] === 0) {
        $fileName = $file['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Check file extension
        if ($fileExt === 'xlsx' || $fileExt === 'xls') {
            $targetPath = $containersDir . '/' . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $message = "Container file '{$fileName}' uploaded successfully!";
            } else {
                $error = "Failed to move uploaded file.";
            }
        } else {
            $error = "Only Excel files (.xlsx, .xls) are allowed for container data.";
        }
    } else {
        $error = getUploadErrorMessage($file['error']);
    }
}

// Helper function to get upload error message
function getUploadErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return "The uploaded file exceeds the upload_max_filesize directive in php.ini.";
        case UPLOAD_ERR_FORM_SIZE:
            return "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.";
        case UPLOAD_ERR_PARTIAL:
            return "The uploaded file was only partially uploaded.";
        case UPLOAD_ERR_NO_FILE:
            return "No file was uploaded.";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Missing a temporary folder.";
        case UPLOAD_ERR_CANT_WRITE:
            return "Failed to write file to disk.";
        case UPLOAD_ERR_EXTENSION:
            return "A PHP extension stopped the file upload.";
        default:
            return "Unknown upload error.";
    }
}

// Get list of container files
$containerFiles = [];
$containersDir = __DIR__ . '/storage/containers';
if (is_dir($containersDir)) {
    $containerFiles = array_diff(scandir($containersDir), ['.', '..']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Container Tracking Manager - Angel Stones</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/container-tracking.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Roboto', Arial, sans-serif;
        }
        .uploader-container {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 2rem;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .login-container {
            max-width: 500px;
            margin: 5rem auto;
            padding: 2rem;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .login-form {
            margin-top: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }
        .alert-danger {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }
        .files-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }
        .files-table th,
        .files-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .files-table th {
            background-color: #f1f1f1;
            font-weight: 600;
        }
        .files-table tr:hover {
            background-color: #f9f9f9;
        }
        .btn-delete {
            color: #c62828;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }
        .btn-delete:hover {
            background-color: #ffebee;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php if (!$authenticated): ?>
    <!-- Login Form -->
    <div class="login-container">
        <h1>Container Tracking Manager</h1>
        <p>Enter the admin password to access the container file uploader.</p>
        
        <?php if (isset($error) && !empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form class="login-form" method="post">
            <div class="form-group">
                <label for="password">Admin Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Log In</button>
        </form>
    </div>
    
    <?php else: ?>
    <!-- Container File Uploader -->
    <div class="uploader-container">
        <div class="header-actions">
            <h1>Container Tracking Manager</h1>
            <div>
                <a href="container-tracking.html" class="btn btn-primary" target="_blank">
                    <i class="bi bi-eye"></i> View Tracking Page
                </a>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="upload-form">
                <h2>Upload Container File</h2>
                <p>Upload an Excel file containing container data. The file should include the following columns:</p>
                <ul>
                    <li>NO</li>
                    <li>ITEM</li>
                    <li>MATERIAL</li>
                    <li>NAME</li>
                    <li>ITEM2</li>
                    <li>POLISHING</li>
                    <li>DETAILS</li>
                    <li>L, T, W (dimensions)</li>
                    <li>ORDER STATUS</li>
                    <li>M3</li>
                    <li>SFT</li>
                    <li>Qty</li>
                    <li>CRATE</li>
                </ul>
                
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="container_file">Select Excel File</label>
                        <input type="file" id="container_file" name="container_file" class="form-control" accept=".xlsx,.xls" required>
                    </div>
                    <button type="submit" name="upload_container" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Upload File
                    </button>
                </form>
            </div>
            
            <div class="files-list">
                <h2>Uploaded Container Files</h2>
                
                <?php if (empty($containerFiles)): ?>
                    <p>No container files uploaded yet.</p>
                <?php else: ?>
                    <table class="files-table">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Size</th>
                                <th>Uploaded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($containerFiles as $file): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($file); ?></td>
                                    <td><?php echo round(filesize($containersDir . '/' . $file) / 1024, 2); ?> KB</td>
                                    <td><?php echo date('Y-m-d H:i', filemtime($containersDir . '/' . $file)); ?></td>
                                    <td>
                                        <button class="btn-delete" onclick="deleteFile('<?php echo htmlspecialchars($file); ?>')">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function deleteFile(fileName) {
            if (confirm('Are you sure you want to delete ' + fileName + '?')) {
                window.location.href = '?delete=' + encodeURIComponent(fileName);
            }
        }
    </script>
    <?php endif; ?>
</body>
</html>
