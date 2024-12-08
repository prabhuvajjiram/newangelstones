<?php
require_once 'includes/config.php';
require_once 'session_check.php';

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

// Get customers list
global $pdo;
$customers = [];
$stmt = $pdo->query("
    SELECT c.*, comp.name as company_name 
    FROM customers c 
    LEFT JOIN companies comp ON c.company_id = comp.id 
    ORDER BY c.name ASC
");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - Angel Stones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Customers</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customerModal">
                <i class="bi bi-person-plus"></i> Add Customer
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Job Title</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?= htmlspecialchars($customer['name']) ?></td>
                        <td><?= $customer['company_name'] ? htmlspecialchars($customer['company_name']) : '-' ?></td>
                        <td><?= $customer['job_title'] ? htmlspecialchars($customer['job_title']) : '-' ?></td>
                        <td><?= htmlspecialchars($customer['email']) ?></td>
                        <td><?= htmlspecialchars($customer['phone']) ?></td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary edit-customer" data-id="<?= $customer['id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="view_customer.php?id=<?= $customer['id'] ?>" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Customer Modal -->
    <div class="modal fade" id="customerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Customer Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="customerForm">
                        <input type="hidden" name="id" id="customerId">
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
                                <label for="jobTitle" class="form-label">Job Title</label>
                                <input type="text" class="form-control" id="jobTitle" name="jobTitle">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                            <div class="col-md-6">
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
                                <label for="postalCode" class="form-label">Postal Code</label>
                                <input type="text" class="form-control" id="postalCode" name="postalCode">
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
                    <button type="submit" form="customerForm" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Function to save customer data
            function saveCustomer(e) {
                e.preventDefault();
                
                const formData = {
                    action: $('#customerId').val() ? 'update' : 'add',
                    id: $('#customerId').val(),
                    name: $('#name').val(),
                    company_id: $('#company_id').val() || null,
                    job_title: $('#jobTitle').val(),
                    email: $('#email').val(),
                    phone: $('#phone').val(),
                    address: $('#address').val(),
                    city: $('#city').val(),
                    state: $('#state').val(),
                    postal_code: $('#postalCode').val(),
                    notes: $('#notes').val()
                };

                $.ajax({
                    url: 'ajax/save_customer.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(formData),
                    success: function(response) {
                        if (response.success) {
                            $('#customerModal').modal('hide');
                            location.reload(); // Refresh to show updated data
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error saving customer: ' + error);
                        console.error('Error details:', xhr.responseText);
                    }
                });
            }

            // Function to load customer data for editing
            function loadCustomerData(id) {
                $.ajax({
                    url: 'ajax/get_customer.php',
                    method: 'GET',
                    data: { id: id },
                    dataType: 'json',
                    success: function(data) {
                        if (data.error) {
                            alert('Error: ' + data.error);
                            return;
                        }
                        
                        $('#customerId').val(data.id);
                        $('#name').val(data.name);
                        $('#company_id').val(data.company_id);
                        $('#jobTitle').val(data.job_title);
                        $('#email').val(data.email);
                        $('#phone').val(data.phone);
                        $('#address').val(data.address);
                        $('#city').val(data.city);
                        $('#state').val(data.state);
                        $('#postalCode').val(data.postal_code);
                        $('#notes').val(data.notes);
                        
                        $('#customerModal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        alert('Error loading customer data: ' + error);
                    }
                });
            }

            // Handle form submission
            $('#customerForm').on('submit', saveCustomer);

            // Edit customer button click
            $('.edit-customer').click(function() {
                const id = $(this).data('id');
                loadCustomerData(id);
            });
        });
    </script>
</body>
</html>
