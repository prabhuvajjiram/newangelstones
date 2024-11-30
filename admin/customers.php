<?php
require_once 'includes/config.php';
requireLogin();

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
            
            $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, address, city, state, postal_code, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['address'],
                $data['city'],
                $data['state'],
                $data['postalCode'],
                $data['notes']
            ]);
            
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            
        } elseif ($data['action'] === 'update') {
            if (empty($data['id']) || empty($data['name'])) {
                throw new Exception('ID and Name are required for update');
            }
            
            $stmt = $pdo->prepare("UPDATE customers SET name=?, email=?, phone=?, address=?, city=?, state=?, postal_code=?, notes=? WHERE id=?");
            $stmt->execute([
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['address'],
                $data['city'],
                $data['state'],
                $data['postalCode'],
                $data['notes'],
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
$stmt = $pdo->query("SELECT * FROM customers ORDER BY name");
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
            <h2>Customer Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customerModal">
                <i class="bi bi-plus-lg"></i> Add Customer
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Location</th>
                                <th>Quotes</th>
                                <th>Last Follow-up</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($customer['email']); ?><br>
                                    <?php echo htmlspecialchars($customer['phone']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($customer['city']); ?>,
                                    <?php echo htmlspecialchars($customer['state']); ?>
                                </td>
                                <td>
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM quotes WHERE customer_id = " . $customer['id']);
                                    $quoteCount = $stmt->fetchColumn();
                                    echo $quoteCount;
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $stmt = $pdo->query("SELECT follow_up_date, status FROM follow_ups WHERE customer_id = " . $customer['id'] . " ORDER BY follow_up_date DESC LIMIT 1");
                                    $lastFollowup = $stmt->fetch(PDO::FETCH_ASSOC);
                                    if ($lastFollowup) {
                                        echo date('M d, Y', strtotime($lastFollowup['follow_up_date']));
                                        echo '<br><span class="badge bg-' . 
                                            ($lastFollowup['status'] === 'converted' ? 'success' : 
                                            ($lastFollowup['status'] === 'interested' ? 'primary' : 
                                            ($lastFollowup['status'] === 'not_interested' ? 'danger' : 'warning'))) . 
                                            '">' . ucfirst(str_replace('_', ' ', $lastFollowup['status'])) . '</span>';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" onclick="editCustomer(<?php echo $customer['id']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" onclick="addFollowUp(<?php echo $customer['id']; ?>)">
                                            <i class="bi bi-calendar-plus"></i>
                                        </button>
                                        <a href="quote.php?customer_id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-file-earmark-plus"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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
                        <input type="hidden" id="customerId">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" id="customerName" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="customerEmail">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="customerPhone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" id="customerAddress">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" id="customerCity">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">State</label>
                                <input type="text" class="form-control" id="customerState">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Postal Code</label>
                                <input type="text" class="form-control" id="customerPostalCode">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" id="customerNotes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="saveCustomer()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Follow-up Modal -->
    <div class="modal fade" id="followUpModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Follow-up</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="followUpForm">
                        <input type="hidden" id="followUpCustomerId">
                        <div class="mb-3">
                            <label class="form-label">Follow-up Date</label>
                            <input type="date" class="form-control" id="followUpDate" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="followUpStatus" required>
                                <option value="pending">Pending</option>
                                <option value="contacted">Contacted</option>
                                <option value="interested">Interested</option>
                                <option value="not_interested">Not Interested</option>
                                <option value="converted">Converted</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" id="followUpNotes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="saveFollowUp()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Reset form when modal is opened for new customer
        $('#customerModal').on('show.bs.modal', function (e) {
            if (!e.relatedTarget) return; // Don't reset if opened by edit button
            $('#customerForm')[0].reset();
            $('#customerId').val('');
        });

        function editCustomer(id) {
            // Fetch customer data and populate modal
            $.get('api/customer.php?id=' + id, function(data) {
                $('#customerId').val(data.id);
                $('#customerName').val(data.name);
                $('#customerEmail').val(data.email);
                $('#customerPhone').val(data.phone);
                $('#customerAddress').val(data.address);
                $('#customerCity').val(data.city);
                $('#customerState').val(data.state);
                $('#customerPostalCode').val(data.postal_code);
                $('#customerNotes').val(data.notes);
                $('#customerModal').modal('show');
            });
        }

        function saveCustomer() {
            // Get form data
            const formData = {
                action: $('#customerId').val() ? 'update' : 'add',
                id: $('#customerId').val(),
                name: $('#customerName').val().trim(),
                email: $('#customerEmail').val().trim(),
                phone: $('#customerPhone').val().trim(),
                address: $('#customerAddress').val().trim(),
                city: $('#customerCity').val().trim(),
                state: $('#customerState').val().trim(),
                postalCode: $('#customerPostalCode').val().trim(),
                notes: $('#customerNotes').val().trim()
            };

            // Validate required fields
            if (!formData.name) {
                alert('Name is required');
                $('#customerName').focus();
                return;
            }

            // Show loading state
            const saveBtn = $('#customerModal .btn-primary');
            const originalText = saveBtn.text();
            saveBtn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: window.location.href,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(response) {
                    if (response.success) {
                        $('#customerModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Error saving customer: ' + (response.error || 'Unknown error'));
                        saveBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    let errorMessage = 'Error saving customer';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMessage += ': ' + (response.error || error);
                    } catch(e) {
                        errorMessage += ': ' + error;
                    }
                    alert(errorMessage);
                    saveBtn.prop('disabled', false).text(originalText);
                }
            });
        }

        function addFollowUp(customerId) {
            $('#followUpCustomerId').val(customerId);
            $('#followUpDate').val(new Date().toISOString().split('T')[0]);
            $('#followUpModal').modal('show');
        }

        function saveFollowUp() {
            const data = {
                customerId: $('#followUpCustomerId').val(),
                date: $('#followUpDate').val(),
                status: $('#followUpStatus').val(),
                notes: $('#followUpNotes').val()
            };

            $.post('api/follow_up.php', data, function(response) {
                if (response.success) {
                    location.reload();
                }
            });
        }
    </script>
</body>
</html>
