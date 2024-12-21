<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../session_check.php';

// Check for admin access
if (!isAdmin()) {
    $_SESSION['error'] = 'Access denied. Admin privileges required.';
    header('Location: ../index.php');
    exit;
}

$pageTitle = "Supplier Invoices";
require_once '../header.php';
require_once '../navbar.php';

// Get suppliers for dropdown
try {
    $pdo = getDbConnection();
    $suppliersQuery = "SELECT id, name FROM suppliers ORDER BY name";
    $suppliersStmt = $pdo->query($suppliersQuery);
    $suppliers = $suppliersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching suppliers: " . $e->getMessage());
    $suppliers = [];
}
?>

<div class="main-content">
    <div class="container-fluid px-4">
        <div class="row mt-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0 text-gray-800"><?php echo $pageTitle; ?></h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadInvoiceModal">
                        <i class="bi bi-upload me-2"></i>Upload Invoice
                    </button>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php 
                        echo htmlspecialchars($_SESSION['success']); 
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php 
                        echo htmlspecialchars($_SESSION['error']); 
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Supplier</th>
                                        <th>Date</th>
                                        <th>Currency</th>
                                        <th class="text-end">Total Amount</th>
                                        <th>Status</th>
                                        <th>File Type</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        $query = "SELECT si.*, s.name as supplier_name 
                                                FROM supplier_invoices si 
                                                LEFT JOIN suppliers s ON si.supplier_id = s.id 
                                                ORDER BY si.created_at DESC";
                                        $stmt = $pdo->query($query);
                                        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        if (empty($invoices)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No invoices found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($invoices as $invoice): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($invoice['supplier_name']); ?></td>
                                                    <td><?php echo date('Y-m-d', strtotime($invoice['invoice_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($invoice['currency']); ?></td>
                                                    <td class="text-end">
                                                        <?php echo number_format($invoice['total_amount'], 2); ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo match($invoice['status']) {
                                                                'processed' => 'success',
                                                                'error' => 'danger',
                                                                default => 'warning'
                                                            };
                                                        ?>">
                                                            <?php echo ucfirst(htmlspecialchars($invoice['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <i class="bi bi-file-<?php 
                                                            echo match($invoice['file_type']) {
                                                                'pdf' => 'pdf',
                                                                'jpg', 'jpeg', 'png' => 'image',
                                                                default => 'earmark'
                                                            };
                                                        ?>"></i>
                                                        <?php echo strtoupper($invoice['file_type']); ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <a href="../uploads/supplier_invoices/<?php echo urlencode($invoice['file_path']); ?>" 
                                                           class="btn btn-sm btn-info" target="_blank">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteInvoice(<?php echo $invoice['id']; ?>)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif;
                                    } catch (PDOException $e) {
                                        error_log("Error fetching invoices: " . $e->getMessage());
                                        echo '<tr><td colspan="8" class="text-center text-danger">Error loading invoices</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Invoice Modal -->
<div class="modal fade" id="uploadInvoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Supplier Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="uploadInvoiceForm" action="process_invoice_upload.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="supplier_id" class="form-label">Supplier*</label>
                        <select class="form-select" id="supplier_id" name="supplier_id" required>
                            <option value="">Select Supplier</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?php echo $supplier['id']; ?>">
                                    <?php echo htmlspecialchars($supplier['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="invoice_number" class="form-label">Invoice Number*</label>
                        <input type="text" class="form-control" id="invoice_number" name="invoice_number" required>
                    </div>
                    <div class="mb-3">
                        <label for="invoice_date" class="form-label">Invoice Date*</label>
                        <input type="date" class="form-control" id="invoice_date" name="invoice_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="currency" class="form-label">Currency*</label>
                        <select class="form-select" id="currency" name="currency" required>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="total_amount" class="form-label">Total Amount*</label>
                        <input type="number" class="form-control" id="total_amount" name="total_amount" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="invoice_file" class="form-label">Invoice File* (PDF, JPG, PNG)</label>
                        <input type="file" class="form-control" id="invoice_file" name="invoice_file" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="uploadInvoiceForm" class="btn btn-primary">Upload Invoice</button>
            </div>
        </div>
    </div>
</div>

<script>
function deleteInvoice(id) {
    if (confirm('Are you sure you want to delete this invoice?')) {
        fetch('delete_invoice.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error deleting invoice');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting invoice');
        });
    }
}
</script>

<?php include '../footer.php'; ?>
