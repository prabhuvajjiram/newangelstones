$(document).ready(function() {
    // Load initial data
    loadMaterials();
    loadColors();
    loadWarehouses();

    // Event listeners for filters
    $('#colorFilter, #statusFilter').change(function() {
        loadMaterials();
    });

    $('#searchInput').on('keyup', function() {
        loadMaterials();
    });

    // Handle save button click
    $('#saveMaterialBtn').click(function() {
        saveMaterial($('#addMaterialForm'));
    });
});

function loadMaterials() {
    const colorFilter = $('#colorFilter').val();
    const statusFilter = $('#statusFilter').val();
    const searchQuery = $('#searchInput').val();

    // Show loading state
    $('#materialsTableBody').html('<tr><td colspan="9" class="text-center">Loading materials...</td></tr>');

    $.ajax({
        url: 'ajax/get_raw_materials.php',
        type: 'GET',
        data: {
            color: colorFilter,
            status: statusFilter,
            search: searchQuery
        },
        success: function(response) {
            console.log('Raw materials response:', response);
            if (response.success) {
                displayMaterials(response.materials);
            } else {
                $('#materialsTableBody').html('<tr><td colspan="9" class="text-center text-danger">Error: ' + response.message + '</td></tr>');
                console.error('Error loading materials:', response.message);
            }
        },
        error: function(xhr, status, error) {
            $('#materialsTableBody').html('<tr><td colspan="9" class="text-center text-danger">Error communicating with the server</td></tr>');
            console.error('AJAX error:', {xhr, status, error});
        }
    });
}

function loadColors() {
    $.ajax({
        url: 'ajax/get_colors.php',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                populateColorDropdowns(response.colors);
            } else {
                console.error('Failed to load colors:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading colors:', error);
        }
    });
}

function loadWarehouses() {
    $.ajax({
        url: 'ajax/get_warehouses.php',
        method: 'GET',
        success: function(response) {
            if (response.success && response.warehouses) {
                populateWarehouseDropdown(response.warehouses);
            } else {
                console.error('Failed to load warehouses:', response.message || 'Unknown error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading warehouses:', error);
        }
    });
}

function populateColorDropdowns(colors) {
    // Filter dropdown (with All Colors option)
    const filterSelect = $('#colorFilter');
    filterSelect.empty();
    filterSelect.append('<option value="">All Colors</option>');
    
    // Form dropdown (without All Colors option)
    const formSelect = $('select[name="color_id"]');
    formSelect.empty();
    formSelect.append('<option value="">Select Color</option>');
    
    // Add color options to both dropdowns
    colors.forEach(function(color) {
        // Add to filter dropdown
        filterSelect.append(
            $('<option></option>')
                .val(color.id)
                .text(color.name)
        );
        
        // Add to form dropdown
        formSelect.append(
            $('<option></option>')
                .val(color.id)
                .text(color.name)
        );
    });
}

function populateWarehouseDropdown(warehouses) {
    const select = $('select[name="warehouse_id"]');
    select.empty();
    select.append('<option value="">Select Warehouse</option>');
    
    warehouses.forEach(function(warehouse) {
        select.append(
            $('<option></option>')
                .val(warehouse.id)
                .text(warehouse.name)
        );
    });
}

function displayMaterials(materials) {
    const tbody = $('#materialsTableBody');
    tbody.empty();

    if (materials.length === 0) {
        tbody.html('<tr><td colspan="9" class="text-center">No materials found</td></tr>');
        return;
    }

    materials.forEach(function(material) {
        const dimensions = `${material.length} × ${material.width} × ${material.height}`;
        const row = `
            <tr>
                <td>${material.id}</td>
                <td>${material.color_name}</td>
                <td>${dimensions}</td>
                <td>${material.quantity}</td>
                <td>${material.warehouse_name}</td>
                <td>${material.location_details || '-'}</td>
                <td>${material.min_stock_level}</td>
                <td>${getStatusBadge(material.status)}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editMaterial(${material.id})">Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteMaterial(${material.id})">Delete</button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function getStatusBadge(status) {
    const badges = {
        'in_stock': '<span class="badge bg-success">In Stock</span>',
        'low_stock': '<span class="badge bg-warning">Low Stock</span>',
        'out_of_stock': '<span class="badge bg-danger">Out of Stock</span>'
    };
    return badges[status] || status;
}

function saveMaterial(form) {
    const formData = new FormData(form[0]);
    
    // Debug: Log form data
    console.log('Form data before send:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ': ' + value);
    }
    
    // Add warehouse name from selected option
    const warehouseSelect = form.find('select[name="warehouse_id"]');
    const selectedWarehouse = warehouseSelect.find('option:selected');
    formData.append('warehouse_name', selectedWarehouse.text());

    // Show loading state
    const saveBtn = $('#saveMaterialBtn');
    const originalText = saveBtn.text();
    saveBtn.prop('disabled', true).text('Saving...');

    $.ajax({
        url: 'ajax/save_raw_material.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log('Save response:', response);
            if (response.success) {
                // Close modal and reset form
                $('#addMaterialModal').modal('hide');
                form[0].reset();
                
                // Remove any existing material_id field
                form.find('input[name="material_id"]').remove();
                
                // Reset modal title
                $('#addMaterialModal .modal-title').text('Add New Material');
                
                // Show success message
                showAlert('success', 'Material saved successfully');
                
                // Reload materials table
                loadMaterials();
            } else {
                showAlert('error', response.message || 'Failed to save material');
            }
        },
        error: function(xhr, status, error) {
            showAlert('error', 'Error saving material');
            console.error('Save error:', {xhr, status, error});
        },
        complete: function() {
            // Reset button state
            saveBtn.prop('disabled', false).text(originalText);
        }
    });
}

function editMaterial(id) {
    $.ajax({
        url: 'ajax/get_raw_materials.php',
        type: 'GET',
        data: { id: id },
        success: function(response) {
            console.log('Edit response:', response);
            if (response.success) {
                populateEditForm(response.material);
                $('#addMaterialModal').modal('show');
            } else {
                showAlert('danger', 'Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Edit error:', {xhr, status, error});
            showAlert('danger', 'Error loading material details');
        }
    });
}

function populateEditForm(material) {
    console.log('Populating form with:', material);
    const form = $('#addMaterialForm');
    
    // Reset form first
    form[0].reset();
    
    // Set form title
    $('#addMaterialModal .modal-title').text('Edit Material');
    
    // Remove any existing material_id field
    form.find('input[name="material_id"]').remove();
    
    // Add material ID to form for update
    const materialIdField = $('<input>').attr({
        type: 'hidden',
        name: 'material_id',
        value: material.id
    });
    form.prepend(materialIdField);
    
    // Populate form fields
    form.find('select[name="color_id"]').val(material.color_id);
    form.find('input[name="length"]').val(material.length);
    form.find('input[name="width"]').val(material.width);
    form.find('input[name="height"]').val(material.height);
    form.find('input[name="quantity"]').val(material.quantity);
    form.find('select[name="warehouse_id"]').val(material.warehouse_id);
    form.find('input[name="location_details"]').val(material.location_details);
    form.find('input[name="min_stock_level"]').val(material.min_stock_level);
    
    // Debug: Log form data after population
    const formData = new FormData(form[0]);
    console.log('Form data after population:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ': ' + value);
    }
}

function deleteMaterial(id) {
    if (confirm('Are you sure you want to delete this material?')) {
        $.ajax({
            url: 'ajax/delete_raw_material.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Material deleted successfully');
                    loadMaterials();
                } else {
                    showAlert('danger', 'Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Delete error:', {xhr, status, error});
                showAlert('danger', 'Error deleting material');
            }
        });
    }
}

function showAlert(type, message) {
    const alertDiv = $('<div></div>')
        .addClass(`alert alert-${type} alert-dismissible fade show`)
        .attr('role', 'alert')
        .html(`
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `);
    
    $('#alertContainer').append(alertDiv);
    
    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        alertDiv.alert('close');
    }, 5000);
}
