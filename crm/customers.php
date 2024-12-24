<?php
require_once 'session_check.php';
requireStaffOrAdmin(); // Allow both staff and admin users to access customers
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Debug session info
error_log("Debug: Session data in customers.php");
error_log("Session ID: " . session_id());
error_log("User ID: " . ($_SESSION['user_id'] ?? 'Not set'));
error_log("Role: " . ($_SESSION['role'] ?? 'Not set'));
error_log("Email: " . ($_SESSION['email'] ?? 'Not set'));

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Get raw POST data and decode
    $raw_data = file_get_contents('php://input');
    $data = json_decode($raw_data, true);
    
    // Debug logging
    error_log("Received POST data: " . $raw_data);
    
    if ($data === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON data received']);
        exit;
    }
    
    if (!isset($data['action'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No action specified']);
        exit;
    }
    
    try {
        global $pdo;
        
        if ($data['action'] === 'add') {
            // Validate required fields
            if (empty($data['name'])) {
                throw new Exception('Name is required');
            }
            
            $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, address, city, state, postal_code, notes, company_id, job_title) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['address'],
                $data['city'],
                $data['state'],
                $data['postal_code'],
                $data['notes'],
                $data['company_id'] ?: null,
                $data['job_title'] ?: null
            ]);
            
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            
        } elseif ($data['action'] === 'update') {
            if (empty($data['id']) || empty($data['name'])) {
                throw new Exception('ID and Name are required for update');
            }
            
            $stmt = $pdo->prepare("UPDATE customers SET name=?, email=?, phone=?, address=?, city=?, state=?, postal_code=?, notes=?, company_id=?, job_title=? WHERE id=?");
            $stmt->execute([
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['address'],
                $data['city'],
                $data['state'],
                $data['postal_code'],
                $data['notes'],
                $data['company_id'] ?: null,
                $data['job_title'] ?: null,
                $data['id']
            ]);
            
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Invalid action specified');
        }
    } catch (Exception $e) {
        error_log("Error in customers.php: " . $e->getMessage());
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

include 'header.php';
?>

<!-- Add DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="css/datatables-custom.css">

<!-- Add base URL for JavaScript -->
<script>
    const BASE_URL = '<?php echo ADMIN_BASE_URL; ?>';
</script>

<?php include 'navbar.php'; ?>

<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-12">
            <div class="content-card bg-white rounded-3 shadow-sm">
                <div class="card-header border-0 bg-transparent py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold">Customer Management</h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customerModal">
                            <i class="fas fa-plus me-2"></i>Add New Customer
                        </button>
                    </div>
                </div>
                <div class="card-body px-4">
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table id="customersTable" class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 120px">ID/Actions</th>
                                    <th>Customer</th>
                                    <th>Company</th>
                                    <th>Contact</th>
                                    <th>Location</th>
                                    <th class="text-center">View</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Customer Modal -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customerModalLabel">Add New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="customerForm">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="company_id" class="form-label">Company</label>
                            <select class="form-select" id="company_id" name="company_id">
                                <option value="">Select Company</option>
                                <?php
                                $companies = $pdo->query("SELECT id, name FROM companies ORDER BY name ASC");
                                while ($company = $companies->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . $company['id'] . '">' . htmlspecialchars($company['name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city">
                        </div>
                        <div class="col-md-4">
                            <label for="state" class="form-label">State</label>
                            <input type="text" class="form-control" id="state" name="state">
                        </div>
                        <div class="col-md-4">
                            <label for="postal_code" class="form-label">Postal Code</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveCustomer">Save</button>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<!-- Add DataTables JS -->
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="js/customers.js"></script>
