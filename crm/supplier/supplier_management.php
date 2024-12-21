<?php
$baseDir = dirname(dirname(__FILE__));
require_once $baseDir . '/includes/session.php';
require_once $baseDir . '/includes/config.php';
require_once $baseDir . '/includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . ADMIN_BASE_URL . 'login.php');
    exit;
}

$pageTitle = "Supplier Management";
include $baseDir . '/header.php';
include $baseDir . '/navbar.php';
?>

<div class="main-content">
    <div class="container-fluid px-4">
        <div class="row mt-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Supplier Management</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                        <i class="bi bi-plus-circle me-2"></i>Add New Supplier
                    </button>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="suppliersTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Company Name</th>
                                        <th>Contact Person</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addSupplierForm">
                    <div class="mb-3">
                        <label for="companyName" class="form-label">Company Name*</label>
                        <input type="text" class="form-control" id="companyName" name="companyName" required>
                    </div>
                    <div class="mb-3">
                        <label for="contactPerson" class="form-label">Contact Person*</label>
                        <input type="text" class="form-control" id="contactPerson" name="contactPerson" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email*</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone*</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveSupplier()">Save Supplier</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Supplier Modal -->
<div class="modal fade" id="editSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editSupplierForm">
                    <input type="hidden" id="editSupplierId" name="id">
                    <div class="mb-3">
                        <label for="editName" class="form-label">Company Name*</label>
                        <input type="text" class="form-control" id="editName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editContactPerson" class="form-label">Contact Person*</label>
                        <input type="text" class="form-control" id="editContactPerson" name="contact_person" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEmail" class="form-label">Email*</label>
                        <input type="email" class="form-control" id="editEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="editPhone" class="form-label">Phone*</label>
                        <input type="tel" class="form-control" id="editPhone" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="editAddress" class="form-label">Address</label>
                        <textarea class="form-control" id="editAddress" name="address" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="editNotes" name="notes" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateSupplier()">Update Supplier</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadSuppliers();
});

function showAlert(message, type) {
    var alertDiv = $('<div>')
        .addClass('alert alert-' + type + ' alert-dismissible fade show')
        .attr('role', 'alert');
    
    var messageSpan = $('<span>').text(message);
    var closeButton = $('<button>')
        .addClass('btn-close')
        .attr('type', 'button')
        .attr('data-bs-dismiss', 'alert')
        .attr('aria-label', 'Close');
    
    alertDiv.append(messageSpan).append(closeButton);
    $('.container-fluid').prepend(alertDiv);
    
    setTimeout(function() {
        alertDiv.alert('close');
    }, 5000);
}

function loadSuppliers() {
    $.ajax({
        url: 'ajax/get_suppliers.php',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const suppliers = response.data;
                const tbody = $('#suppliersTable tbody');
                tbody.empty();
                
                suppliers.forEach(function(supplier) {
                    var row = $('<tr>');
                    row.append($('<td>').text(supplier.id || ''));
                    row.append($('<td>').text(supplier.name || ''));
                    row.append($('<td>').text(supplier.contact_person || ''));
                    row.append($('<td>').text(supplier.email || ''));
                    row.append($('<td>').text(supplier.phone || ''));
                    row.append($('<td>').html('<span class="badge bg-success">Active</span>'));
                    
                    var actions = $('<td>').addClass('text-center');
                    var editBtn = $('<button>')
                        .addClass('btn btn-sm btn-primary me-2')
                        .html('<i class="bi bi-pencil"></i>')
                        .on('click', function() { editSupplier(supplier.id); });
                    
                    var deleteBtn = $('<button>')
                        .addClass('btn btn-sm btn-danger')
                        .html('<i class="bi bi-trash"></i>')
                        .on('click', function() { deleteSupplier(supplier.id); });
                    
                    actions.append(editBtn).append(deleteBtn);
                    row.append(actions);
                    
                    tbody.append(row);
                });
            } else {
                showAlert(response.message || 'Failed to load suppliers', 'danger');
            }
        },
        error: function() {
            showAlert('Failed to load suppliers', 'danger');
        }
    });
}

function saveSupplier() {
    var formData = new FormData(document.getElementById('addSupplierForm'));
    
    $.ajax({
        url: 'ajax/add_supplier.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                $('#addSupplierModal').modal('hide');
                document.getElementById('addSupplierForm').reset();
                showAlert('Supplier added successfully', 'success');
                loadSuppliers();
            } else {
                showAlert(response.message || 'Failed to add supplier', 'danger');
            }
        },
        error: function() {
            showAlert('An error occurred while adding the supplier', 'danger');
        }
    });
}

function editSupplier(id) {
    $.ajax({
        url: 'ajax/get_supplier.php',
        type: 'GET',
        data: { id: id },
        success: function(response) {
            if (response.success) {
                const supplier = response.data;
                $('#editSupplierId').val(supplier.id);
                $('#editName').val(supplier.name);
                $('#editContactPerson').val(supplier.contact_person);
                $('#editEmail').val(supplier.email);
                $('#editPhone').val(supplier.phone);
                $('#editAddress').val(supplier.address);
                $('#editNotes').val(supplier.notes);
                $('#editSupplierModal').modal('show');
            } else {
                showAlert('Error: ' + (response.message || 'Failed to load supplier details'), 'danger');
            }
        },
        error: function(xhr, status, error) {
            showAlert('Error loading supplier details: ' + error, 'danger');
            console.error('AJAX Error:', xhr.responseText);
        }
    });
}

function updateSupplier() {
    const formData = $('#editSupplierForm').serialize();
    
    $.ajax({
        url: 'ajax/update_supplier.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                $('#editSupplierModal').modal('hide');
                showAlert('Supplier updated successfully', 'success');
                loadSuppliers();
            } else {
                showAlert('Error: ' + (response.message || 'Failed to update supplier'), 'danger');
            }
        },
        error: function(xhr, status, error) {
            showAlert('Error updating supplier: ' + error, 'danger');
            console.error('AJAX Error:', xhr.responseText);
        }
    });
}

function deleteSupplier(id) {
    if (confirm('Are you sure you want to delete this supplier?')) {
        $.ajax({
            url: 'ajax/delete_supplier.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                if (response.success) {
                    showAlert('Supplier deleted successfully', 'success');
                    loadSuppliers();
                } else {
                    showAlert('Error: ' + (response.message || 'Failed to delete supplier'), 'danger');
                }
            },
            error: function(xhr, status, error) {
                showAlert('Error deleting supplier: ' + error, 'danger');
                console.error('AJAX Error:', xhr.responseText);
            }
        });
    }
}
</script>

<?php include $baseDir . '/footer.php'; ?>
