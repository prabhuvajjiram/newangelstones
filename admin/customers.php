<?php
require_once 'includes/config.php';
requireLogin();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        if ($data['action'] === 'add') {
            $stmt = $conn->prepare("INSERT INTO customers (name, email, phone, address, city, state, postal_code, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", 
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['address'],
                $data['city'],
                $data['state'],
                $data['postalCode'],
                $data['notes']
            );
            $stmt->execute();
            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
        } elseif ($data['action'] === 'update') {
            $stmt = $conn->prepare("UPDATE customers SET name=?, email=?, phone=?, address=?, city=?, state=?, postal_code=?, notes=? WHERE id=?");
            $stmt->bind_param("ssssssssi", 
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['address'],
                $data['city'],
                $data['state'],
                $data['postalCode'],
                $data['notes'],
                $data['id']
            );
            $stmt->execute();
            echo json_encode(['success' => true]);
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Get customers list
$customers = [];
$result = $conn->query("SELECT * FROM customers ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}
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
                                    $quoteCount = $conn->query("SELECT COUNT(*) as count FROM quotes WHERE customer_id = " . $customer['id'])->fetch_assoc()['count'];
                                    echo $quoteCount;
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $lastFollowup = $conn->query("SELECT follow_up_date, status FROM follow_ups WHERE customer_id = " . $customer['id'] . " ORDER BY follow_up_date DESC LIMIT 1")->fetch_assoc();
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
            const data = {
                action: $('#customerId').val() ? 'update' : 'add',
                id: $('#customerId').val(),
                name: $('#customerName').val(),
                email: $('#customerEmail').val(),
                phone: $('#customerPhone').val(),
                address: $('#customerAddress').val(),
                city: $('#customerCity').val(),
                state: $('#customerState').val(),
                postalCode: $('#customerPostalCode').val(),
                notes: $('#customerNotes').val()
            };

            $.ajax({
                url: 'customers.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
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
