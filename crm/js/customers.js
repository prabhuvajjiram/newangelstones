$(document).ready(function() {
    // Initialize DataTable
    const customersTable = $('#customersTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: 'ajax/get_customers.php',
            type: 'POST'
        },
        columns: [
            {
                data: null,
                className: 'text-center',
                render: function(data, type, row) {
                    return `
                        <div class="d-flex justify-content-center gap-2">
                            <span class="text-secondary small">#${row.id}</span>
                            <button type="button" class="btn btn-outline-primary btn-sm edit-customer" data-id="${row.id}" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm delete-customer" data-id="${row.id}" title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    `;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `
                        <div class="d-flex align-items-center gap-2">
                            <div class="warehouse-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <div class="fw-medium">${row.name}</div>
                                <div class="text-secondary small">${row.job_title || ''}</div>
                            </div>
                        </div>
                    `;
                }
            },
            {
                data: 'company_name',
                render: function(data, type, row) {
                    return data || '-';
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    const email = row.email ? `<div><i class="fas fa-envelope me-1"></i>${row.email}</div>` : '';
                    const phone = row.phone ? `<div><i class="fas fa-phone me-1"></i>${row.phone}</div>` : '';
                    return email + phone || '-';
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    const address = [];
                    if (row.address) address.push(row.address);
                    if (row.city) address.push(row.city);
                    if (row.state) address.push(row.state);
                    if (row.postal_code) address.push(row.postal_code);
                    
                    return address.length > 0 ? address.join(', ') : '-';
                }
            },
            {
                data: null,
                className: 'text-center',
                render: function(data, type, row) {
                    return `
                        <a href="view_customer.php?id=${row.id}" class="btn btn-outline-primary btn-sm" title="View Details">
                            <i class="fas fa-eye"></i>
                        </a>
                    `;
                }
            }
        ],
        order: [[1, 'asc']],
        pageLength: 10,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        language: {
            search: '',
            searchPlaceholder: 'Search customers...',
            lengthMenu: '_MENU_ per page',
            info: 'Showing _START_ to _END_ of _TOTAL_ customers',
            infoEmpty: 'No customers found',
            infoFiltered: '(filtered from _MAX_ total customers)',
            zeroRecords: 'No matching customers found'
        }
    });

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Clear form when modal is hidden
    $('#customerModal').on('hidden.bs.modal', function() {
        $('#customerForm')[0].reset();
        $('#id').val('');
        $('#customerModalLabel').text('Add New Customer');
    });

    // Handle edit button click
    $('#customersTable').on('click', '.edit-customer', function() {
        const id = $(this).data('id');
        
        $.ajax({
            url: 'ajax/get_customer.php',
            type: 'GET',
            data: { id: id },
            success: function(response) {
                if (response.error) {
                    showAlert('error', response.error);
                    return;
                }
                
                $('#id').val(response.id);
                $('#name').val(response.name);
                $('#company_id').val(response.company_id);
                $('#email').val(response.email);
                $('#phone').val(response.phone);
                $('#address').val(response.address);
                $('#city').val(response.city);
                $('#state').val(response.state);
                $('#postal_code').val(response.postal_code);
                $('#notes').val(response.notes);
                
                $('#customerModalLabel').text('Edit Customer');
                $('#customerModal').modal('show');
            },
            error: function(xhr, status, error) {
                showAlert('error', 'Error loading customer data: ' + error);
            }
        });
    });

    // Handle delete button click
    $('#customersTable').on('click', '.delete-customer', function() {
        const id = $(this).data('id');
        
        if (confirm('Are you sure you want to delete this customer?')) {
            $.ajax({
                url: 'ajax/delete_customer.php',
                type: 'POST',
                data: JSON.stringify({ id: id }),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        customersTable.ajax.reload();
                        showAlert('success', 'Customer deleted successfully');
                    } else {
                        showAlert('error', response.error || 'Error deleting customer');
                    }
                },
                error: function(xhr, status, error) {
                    showAlert('error', 'Error deleting customer: ' + error);
                }
            });
        }
    });

    // Handle save button click
    $('#saveCustomer').click(function() {
        const formData = {
            action: $('#id').val() ? 'update' : 'add',
            id: $('#id').val(),
            name: $('#name').val(),
            company_id: $('#company_id').val(),
            email: $('#email').val(),
            phone: $('#phone').val(),
            address: $('#address').val(),
            city: $('#city').val(),
            state: $('#state').val(),
            postal_code: $('#postal_code').val(),
            notes: $('#notes').val()
        };

        $.ajax({
            url: 'ajax/save_customer.php',
            type: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    $('#customerModal').modal('hide');
                    customersTable.ajax.reload();
                    showAlert('success', formData.action === 'add' ? 'Customer added successfully' : 'Customer updated successfully');
                } else {
                    showAlert('error', response.error || 'Error saving customer');
                }
            },
            error: function(xhr, status, error) {
                showAlert('error', 'Error saving customer: ' + error);
            }
        });
    });

    // Helper function to show alerts
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'}-fill me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        $('.alert').remove();
        $('.card-body').prepend(alertHtml);
    }
});
