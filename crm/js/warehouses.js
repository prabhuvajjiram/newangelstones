$(document).ready(function() {
    // Initialize Bootstrap tooltips and popovers
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize DataTable with improved styling
    let warehousesTable = $('#warehousesTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: 'ajax/get_warehouses.php',
            dataSrc: function(json) {
                console.log('DataTables data:', json);
                return json.success ? json.data : [];
            }
        },
        columns: [
            { 
                data: null,
                orderable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    return `
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="me-2">${row.id}</span>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-primary edit-warehouse me-1" data-id="${row.id}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-warehouse" data-id="${row.id}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    `;
                }
            },
            { 
                data: 'name',
                render: function(data, type, row) {
                    return `
                        <div class="d-flex align-items-center">
                            <div class="warehouse-icon me-2">
                                <i class="fas fa-warehouse text-primary"></i>
                            </div>
                            <div>
                                <div class="fw-semibold">${data}</div>
                                <div class="small text-muted">${row.address || 'No address'}</div>
                            </div>
                        </div>
                    `;
                }
            },
            { 
                data: 'contact_person',
                render: function(data, type, row) {
                    if (!data) return '-';
                    return `
                        <div>
                            <div>${data}</div>
                            <div class="small text-muted">${row.phone || ''}</div>
                        </div>
                    `;
                }
            },
            { 
                data: 'email',
                render: function(data) {
                    return data || '-';
                }
            },
            { 
                data: 'status',
                className: 'text-center',
                render: function(data) {
                    const statusClasses = {
                        'active': 'bg-success-subtle text-success',
                        'inactive': 'bg-danger-subtle text-danger'
                    };
                    const statusText = {
                        'active': 'Active',
                        'inactive': 'Inactive'
                    };
                    return `
                        <span class="badge rounded-pill ${statusClasses[data] || 'bg-secondary'}">
                            ${statusText[data] || data}
                        </span>
                    `;
                }
            }
        ],
        order: [[1, 'asc']], // Sort by name by default
        pageLength: 10,
        responsive: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "",
            searchPlaceholder: "Search warehouses...",
            lengthMenu: "_MENU_ per page",
            info: "Showing _START_ to _END_ of _TOTAL_ warehouses",
            infoEmpty: "No warehouses found",
            infoFiltered: "(filtered from _MAX_ total warehouses)"
        },
        drawCallback: function() {
            // Reinitialize dropdowns after table redraw
            var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
            dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
        }
    });

    // Add custom styling to the search input
    $('.dataTables_filter input').addClass('form-control');
    
    // Add custom styling to the length select
    $('.dataTables_length select').addClass('form-select form-select-sm');

    // Handle modal events
    let isEditMode = false;
    let editData = null;

    $('#warehouseModal').on('show.bs.modal', function(e) {
        if (!isEditMode) {
            console.log('Opening modal for new warehouse');
            $('#warehouseForm')[0].reset();
            $('#warehouseForm [name="id"]').val('');
            $('#warehouseModalLabel').text('Add New Warehouse');
        } else if (editData) {
            console.log('Populating form with edit data:', editData);
            $('#warehouseModalLabel').text('Edit Warehouse');
            $('#warehouseForm [name="id"]').val(editData.id);
            $('#warehouseForm [name="name"]').val(editData.name);
            $('#warehouseForm [name="address"]').val(editData.address || '');
            $('#warehouseForm [name="contact_person"]').val(editData.contact_person || '');
            $('#warehouseForm [name="phone"]').val(editData.phone || '');
            $('#warehouseForm [name="email"]').val(editData.email || '');
            $('#warehouseForm [name="notes"]').val(editData.notes || '');
            $('#warehouseForm [name="status"]').val(editData.status || 'active');
        }
    });

    $('#warehouseModal').on('hidden.bs.modal', function() {
        isEditMode = false;
        editData = null;
    });

    // Save warehouse
    $('#saveWarehouse').click(function(e) {
        e.preventDefault();
        const form = $('#warehouseForm');
        const saveBtn = $(this);
        const originalText = saveBtn.text();

        // Basic validation
        let isValid = true;
        form.find('input[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
                if (!$(this).next('.invalid-feedback').length) {
                    $(this).after('<div class="invalid-feedback">This field is required</div>');
                }
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            }
        });

        if (!isValid) return;

        // Show loading state
        saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');

        $.ajax({
            url: 'ajax/save_warehouse.php',
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    $('#warehouseModal').modal('hide');
                    warehousesTable.ajax.reload();
                    showAlert('success', 'Warehouse saved successfully');
                } else {
                    showAlert('danger', 'Error: ' + response.message);
                }
            },
            error: function() {
                showAlert('danger', 'Server error occurred while saving');
            },
            complete: function() {
                saveBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Edit warehouse - use event delegation
    $(document).on('click', '.edit-warehouse', function(e) {
        e.preventDefault();
        const button = $(this);
        const tr = button.closest('tr');
        
        // Get the data directly from the table row
        let rowData;
        if (tr.hasClass('child')) {
            // Handle responsive table child row
            rowData = warehousesTable.row(tr.prev()).data();
        } else {
            rowData = warehousesTable.row(tr).data();
        }
        
        console.log('Edit button clicked. Row data:', rowData);
        
        if (!rowData) {
            showAlert('danger', 'Error: Could not find warehouse data');
            return;
        }

        // Set edit mode and data before showing modal
        isEditMode = true;
        editData = rowData;
        
        // Show the modal
        $('#warehouseModal').modal('show');
    });

    // Delete warehouse - use event delegation
    $(document).on('click', '.delete-warehouse', function(e) {
        e.preventDefault();
        const button = $(this);
        const tr = button.closest('tr');
        
        // Get the data directly from the table row
        let rowData;
        if (tr.hasClass('child')) {
            // Handle responsive table child row
            rowData = warehousesTable.row(tr.prev()).data();
        } else {
            rowData = warehousesTable.row(tr).data();
        }
        
        if (!rowData) {
            showAlert('danger', 'Error: Could not find warehouse data');
            return;
        }

        if (confirm(`Are you sure you want to delete warehouse "${rowData.name}"?`)) {
            $.ajax({
                url: 'ajax/delete_warehouse.php',
                method: 'POST',
                data: { id: rowData.id },
                success: function(response) {
                    if (response.success) {
                        warehousesTable.ajax.reload();
                        showAlert('success', 'Warehouse deleted successfully');
                    } else {
                        showAlert('danger', 'Error: ' + response.message);
                    }
                },
                error: function() {
                    showAlert('danger', 'Server error occurred while deleting');
                }
            });
        }
    });
});

// Show alert message
function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Remove any existing alerts
    $('.alert').remove();
    
    // Add new alert at the top of the content
    $('.content-card').prepend(alertHtml);
    
    // Auto dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}
