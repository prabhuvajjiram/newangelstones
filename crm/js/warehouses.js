$(document).ready(function() {
    console.log('Initializing warehouse management...');

    // Initialize DataTable
    let warehousesTable = $('#warehousesTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: 'ajax/get_warehouses.php',
            dataSrc: 'data'
        },
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'address' },
            { data: 'contact_person' },
            { data: 'phone' },
            { data: 'email' },
            { 
                data: 'status',
                render: function(data) {
                    return `<span class="badge bg-${data === 'active' ? 'success' : 'danger'}">${data}</span>`;
                }
            },
            {
                data: null,
                render: function(data) {
                    return `
                        <button class="btn btn-sm btn-primary edit-warehouse" data-id="${data.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-warehouse" data-id="${data.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                }
            }
        ],
        order: [[1, 'asc']], // Sort by name by default
        pageLength: 10,
        language: {
            emptyTable: "No warehouses found"
        }
    });

    // Clear form when modal is opened for new warehouse
    $('#warehouseModal').on('show.bs.modal', function() {
        console.log('Modal opening...');
        $('#warehouseForm')[0].reset();
        $('#warehouse_id').val('');
    });

    // Edit warehouse
    $(document).on('click', '.edit-warehouse', function() {
        console.log('Edit button clicked');
        let id = $(this).data('id');
        $.ajax({
            url: 'ajax/get_warehouse.php',
            method: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                console.log('Edit response:', response);
                if (response.success) {
                    let warehouse = response.data;
                    $('#warehouse_id').val(warehouse.id);
                    $('#name').val(warehouse.name);
                    $('#address').val(warehouse.address);
                    $('#contact_person').val(warehouse.contact_person);
                    $('#phone').val(warehouse.phone);
                    $('#email').val(warehouse.email);
                    $('#notes').val(warehouse.notes);
                    $('#status').val(warehouse.status);
                    $('#warehouseModal').modal('show');
                } else {
                    alert(response.message || 'Error loading warehouse details');
                }
            },
            error: function(xhr, status, error) {
                console.error('Edit error:', error);
                alert('Error loading warehouse details');
            }
        });
    });

    // Save warehouse
    $('#saveWarehouse').on('click', function() {
        console.log('Save button clicked');
        if (!$('#warehouseForm')[0].checkValidity()) {
            $('#warehouseForm')[0].reportValidity();
            return;
        }

        let formData = new FormData($('#warehouseForm')[0]);
        
        $('#saveWarehouse').prop('disabled', true);
        
        $.ajax({
            url: 'ajax/save_warehouse.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                console.log('Save response:', response);
                if (response.success) {
                    $('#warehouseModal').modal('hide');
                    warehousesTable.ajax.reload();
                    alert(response.message);
                } else {
                    alert(response.message || 'Error saving warehouse');
                }
            },
            error: function(xhr, status, error) {
                console.error('Save error:', xhr.responseText);
                alert('Error saving warehouse details');
            },
            complete: function() {
                $('#saveWarehouse').prop('disabled', false);
            }
        });
    });

    // Delete warehouse
    $(document).on('click', '.delete-warehouse', function() {
        console.log('Delete button clicked');
        if (!confirm('Are you sure you want to delete this warehouse?')) {
            return;
        }

        let id = $(this).data('id');
        
        $.ajax({
            url: 'ajax/delete_warehouse.php',
            method: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                console.log('Delete response:', response);
                if (response.success) {
                    warehousesTable.ajax.reload();
                    alert(response.message);
                } else {
                    alert(response.message || 'Error deleting warehouse');
                }
            },
            error: function(xhr, status, error) {
                console.error('Delete error:', error);
                alert('Error deleting warehouse');
            }
        });
    });

    // Handle modal events
    $('#warehouseModal').on('hidden.bs.modal', function() {
        $('#warehouseForm')[0].reset();
        $('#warehouse_id').val('');
        $('#saveWarehouse').prop('disabled', false);
    });
});
