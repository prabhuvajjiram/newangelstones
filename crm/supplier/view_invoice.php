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

// Validate invoice ID
$invoice_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$invoice_id) {
    $_SESSION['error'] = 'Invalid invoice ID';
    header('Location: supplier_invoice.php');
    exit;
}

// Get invoice details
$stmt = $pdo->prepare("
    SELECT si.*, s.name as supplier_name 
    FROM supplier_invoices si 
    LEFT JOIN suppliers s ON si.supplier_id = s.id 
    WHERE si.id = ?
");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice) {
    $_SESSION['error'] = 'Invoice not found';
    header('Location: supplier_invoice.php');
    exit;
}

$pageTitle = "View Invoice - " . htmlspecialchars($invoice['invoice_number']);
require_once '../header.php';
require_once '../navbar.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><?php echo $pageTitle; ?></h1>
                <a href="supplier_invoice.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Invoices
                </a>
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

            <!-- Invoice Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Invoice Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Supplier:</strong> <?php echo htmlspecialchars($invoice['supplier_name']); ?></p>
                            <p><strong>Invoice Number:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                            <p><strong>Invoice Date:</strong> <?php echo date('F j, Y', strtotime($invoice['invoice_date'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Currency:</strong> <?php echo htmlspecialchars($invoice['currency']); ?></p>
                            <p><strong>Exchange Rate:</strong> <?php echo number_format($invoice['exchange_rate'], 4); ?></p>
                            <p><strong>Total Amount:</strong> <?php echo number_format($invoice['total_amount'], 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice Items -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Invoice Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product Code</th>
                                    <th>Description</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <th>Unit Price</th>
                                    <th>Total Price</th>
                                    <th>FOB Price</th>
                                    <th>CBM</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("
                                    SELECT * FROM supplier_invoice_items 
                                    WHERE invoice_id = ? 
                                    ORDER BY id ASC
                                ");
                                $stmt->execute([$invoice_id]);
                                while ($item = $stmt->fetch()): 
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_code']); ?></td>
                                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                                    <td><?php echo number_format($item['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                    <td><?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td><?php echo number_format($item['total_price'], 2); ?></td>
                                    <td><?php echo $item['fob_price'] ? number_format($item['fob_price'], 2) : '-'; ?></td>
                                    <td><?php echo $item['cbm'] ? number_format($item['cbm'], 3) : '-'; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>
