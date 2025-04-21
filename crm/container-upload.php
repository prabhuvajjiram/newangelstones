<?php
require_once 'includes/config.php';
require_once 'includes/session.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Create storage directories if they don't exist
$containersDir = __DIR__ . '/../storage/containers';
$invoicesDir = __DIR__ . '/../storage/invoices';

if (!file_exists($containersDir)) {
    mkdir($containersDir, 0755, true);
}

if (!file_exists($invoicesDir)) {
    mkdir($invoicesDir, 0755, true);
}

$message = '';
$error = '';

// Handle container file upload
if (isset($_POST['upload_container']) && isset($_FILES['container_file'])) {
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
                
                // Log the activity
                if (function_exists('logActivity')) {
                    logActivity('Container file uploaded: ' . $fileName);
                }
            } else {
                $error = "Failed to move uploaded file.";
            }
        } else {
            $error = "Only Excel files (.xlsx, .xls) are allowed for container data.";
        }
    } else {
        $error = "Error uploading file: " . getUploadErrorMessage($file['error']);
    }
}

// Handle invoice file upload
if (isset($_POST['upload_invoice']) && isset($_FILES['invoice_file'])) {
    $file = $_FILES['invoice_file'];
    
    // Check for errors
    if ($file['error'] === 0) {
        $fileName = $file['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Check file extension
        if ($fileExt === 'pdf') {
            $targetPath = $invoicesDir . '/' . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $message = "Invoice file '{$fileName}' uploaded successfully!";
                
                // Log the activity
                if (function_exists('logActivity')) {
                    logActivity('Invoice file uploaded: ' . $fileName);
                }
            } else {
                $error = "Failed to move uploaded file.";
            }
        } else {
            $error = "Only PDF files are allowed for invoices.";
        }
    } else {
        $error = "Error uploading file: " . getUploadErrorMessage($file['error']);
    }
}

// Get list of container files
$containerFiles = [];
if (is_dir($containersDir)) {
    $containerFiles = array_diff(scandir($containersDir), ['.', '..']);
}

// Get list of invoice files
$invoiceFiles = [];
if (is_dir($invoicesDir)) {
    $invoiceFiles = array_diff(scandir($invoicesDir), ['.', '..']);
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

// Include header
include_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Container Tracking Management</h1>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            Upload Container File
                        </div>
                        <div class="card-body">
                            <form action="" method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="container_file" class="form-label">Select Excel File (Container Data)</label>
                                    <input class="form-control" type="file" id="container_file" name="container_file" accept=".xlsx,.xls" required>
                                    <div class="form-text">Excel file should contain columns: NO, ITEM, MATERIAL, NAME, ITEM2, POLISHING, DETAILS, L, T, W, ORDER STATUS, M3, SFT, Qty, CRATE</div>
                                </div>
                                <button type="submit" name="upload_container" class="btn btn-primary">Upload Container File</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            Upload Invoice File
                        </div>
                        <div class="card-body">
                            <form action="" method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="invoice_file" class="form-label">Select PDF File (Shipping Invoice)</label>
                                    <input class="form-control" type="file" id="invoice_file" name="invoice_file" accept=".pdf" required>
                                    <div class="form-text">PDF should contain shipping details including vessel name and ETA</div>
                                </div>
                                <button type="submit" name="upload_invoice" class="btn btn-primary">Upload Invoice</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            Uploaded Container Files
                        </div>
                        <div class="card-body">
                            <?php if (empty($containerFiles)): ?>
                                <p class="text-muted">No container files uploaded yet.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
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
                                                        <a href="container-view.php?file=<?php echo urlencode($file); ?>" class="btn btn-sm btn-info">View</a>
                                                        <a href="#" class="btn btn-sm btn-danger" onclick="return confirmDelete('<?php echo htmlspecialchars($file); ?>', 'container')">Delete</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            Uploaded Invoice Files
                        </div>
                        <div class="card-body">
                            <?php if (empty($invoiceFiles)): ?>
                                <p class="text-muted">No invoice files uploaded yet.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>File Name</th>
                                                <th>Size</th>
                                                <th>Uploaded</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($invoiceFiles as $file): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($file); ?></td>
                                                    <td><?php echo round(filesize($invoicesDir . '/' . $file) / 1024, 2); ?> KB</td>
                                                    <td><?php echo date('Y-m-d H:i', filemtime($invoicesDir . '/' . $file)); ?></td>
                                                    <td>
                                                        <a href="container-view-invoice.php?file=<?php echo urlencode($file); ?>" class="btn btn-sm btn-info">View</a>
                                                        <a href="#" class="btn btn-sm btn-danger" onclick="return confirmDelete('<?php echo htmlspecialchars($file); ?>', 'invoice')">Delete</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <a href="../container-tracking.html" class="btn btn-success" target="_blank">View Container Tracking Page</a>
            </div>
        </main>
    </div>
</div>

<script>
function confirmDelete(fileName, fileType) {
    if (confirm('Are you sure you want to delete ' + fileName + '?')) {
        window.location.href = 'container-delete.php?file=' + encodeURIComponent(fileName) + '&type=' + fileType;
        return true;
    }
    return false;
}
</script>

<?php include_once 'includes/footer.php'; ?>
