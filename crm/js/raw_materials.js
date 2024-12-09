$(document).ready(function() {
    // Load initial data
    loadMaterials();
    loadColors();

    // Event listeners for filters
    $('#colorFilter, #statusFilter').change(function() {
        loadMaterials();
    });

    $('#searchInput').on('keyup', function() {
        loadMaterials();
    });

    // Save material button click handler
    $('#saveMaterialBtn').click(function(e) {
        e.preventDefault();
        saveMaterial();
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
    // Show loading state in dropdowns
    const loadingOption = '<option value="">Loading colors...</option>';
    $('#colorFilter').html(loadingOption);
    $('select[name="color_id"]').html(loadingOption);

    $.ajax({
        url: 'ajax/get_colors.php',
        type: 'GET',
        success: function(response) {
            console.log('Colors response:', response);
            if (response.success) {
                populateColorDropdowns(response.colors);
            } else {
                const errorOption = '<option value="">Error loading colors</option>';
                $('#colorFilter').html(errorOption);
                $('select[name="color_id"]').html(errorOption);
                console.error('Error loading colors:', response.message);
            }
        },
        error: function(xhr, status, error) {
            const errorOption = '<option value="">Error loading colors</option>';
            $('#colorFilter').html(errorOption);
            $('select[name="color_id"]').html(errorOption);
            console.error('AJAX error:', {xhr, status, error});
        }
    });
}

function populateColorDropdowns(colors) {
    const filterOptions = ['<option value="">All Colors</option>'];
    const modalOptions = ['<option value="">Select Color</option>'];

    colors.forEach(color => {
        const option = `<option value="${color.id}">${color.color_name}</option>`;
        filterOptions.push(option);
        modalOptions.push(option);
    });

    $('#colorFilter').html(filterOptions.join(''));
    $('select[name="color_id"]').html(modalOptions.join(''));
}

function displayMaterials(materials) {
    const tbody = $('#materialsTableBody');
    tbody.empty();

    materials.forEach(material => {
        const status = getStatusBadge(material.status);
        const row = `
            <tr>
                <td>${material.id}</td>
                <td>${material.color_name}</td>
                <td>${material.length}" × ${material.width}" × ${material.height}"</td>
                <td>${material.quantity}</td>
                <td>${material.location}</td>
                <td>${material.min_stock_level}</td>
                <td>${status}</td>
                <td>${material.last_updated}</td>
                <td>
                    <button class="btn btn-sm btn-primary me-1" onclick="editMaterial(${material.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteMaterial(${material.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function getStatusBadge(status) {
    const badges = {
        in_stock: '<span class="badge bg-success">In Stock</span>',
        low_stock: '<span class="badge bg-warning">Low Stock</span>',
        out_of_stock: '<span class="badge bg-danger">Out of Stock</span>'
    };
    return badges[status] || status;
}

function saveMaterial() {
    // Get form data
    const form = $('#addMaterialForm');
    const formData = new FormData(form[0]);

    // Show loading state
    const saveBtn = $('#saveMaterialBtn');
    const originalText = saveBtn.text();
    saveBtn.prop('disabled', true).text('Saving...');

    // Log form data for debugging
    console.log('Form data being sent:', Object.fromEntries(formData));

    $.ajax({
        url: 'ajax/save_raw_material.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            console.log('Save response:', response);
            if (response && response.success) {
                // Reset form and close modal
                form[0].reset();
                $('#addMaterialModal').modal('hide');
                
                // Remove any existing material_id input
                form.find('input[name="material_id"]').remove();
                
                // Reload materials table
                loadMaterials();
                
                // Show success message
                alert('Material saved successfully');
            } else {
                // Show error message
                alert('Error saving material: ' + (response && response.message ? response.message : 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Save error:', {xhr, status, error});
            let errorMessage = 'Error saving material';
            
            try {
                // Try to parse the response as JSON
                if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMessage += ': ' + response.message;
                        }
                    } catch (e) {
                        // If response is not JSON, try to extract error from HTML
                        const htmlMatch = xhr.responseText.match(/<b>([^<]+)<\/b>/);
                        if (htmlMatch) {
                            errorMessage += ': ' + htmlMatch[1];
                        } else {
                            errorMessage += ': ' + error;
                        }
                    }
                } else {
                    errorMessage += ': ' + error;
                }
            } catch (e) {
                errorMessage += ': ' + error;
            }
            
            alert(errorMessage);
        },
        complete: function() {
            // Reset button state
            saveBtn.prop('disabled', false).text(originalText);
            
            // Always reload materials after save attempt
            // This ensures the table is updated even if there was an error
            // but the save actually succeeded
            loadMaterials();
        }
    });
}

function editMaterial(id) {
    $.ajax({
        url: 'ajax/get_raw_material.php',
        type: 'GET',
        data: { id: id },
        success: function(response) {
            console.log('Edit material response:', response);
            if (response.success) {
                populateEditForm(response.material);
                $('#addMaterialModal').modal('show');
            } else {
                alert('Error loading material: ' + response.message);
                console.error('Error loading material:', response.message);
            }
        },
        error: function(xhr, status, error) {
            alert('Error communicating with the server');
            console.error('AJAX error:', {xhr, status, error});
        }
    });
}

function populateEditForm(material) {
    const form = $('#addMaterialForm');
    form.find('[name="color_id"]').val(material.color_id);
    form.find('[name="length"]').val(material.length);
    form.find('[name="width"]').val(material.width);
    form.find('[name="height"]').val(material.height);
    form.find('[name="quantity"]').val(material.quantity);
    form.find('[name="location"]').val(material.location);
    form.find('[name="min_stock_level"]').val(material.min_stock_level);
    
    // Add material ID for update
    form.find('input[name="material_id"]').remove();
    form.append(`<input type="hidden" name="material_id" value="${material.id}">`);
}

function deleteMaterial(id) {
    if (confirm('Are you sure you want to delete this material?')) {
        $.ajax({
            url: 'ajax/delete_raw_material.php',
            type: 'POST',
            data: { id: id },
            success: function(response) {
                console.log('Delete material response:', response);
                if (response.success) {
                    loadMaterials();
                    alert('Material deleted successfully');
                } else {
                    alert('Error deleting material: ' + response.message);
                    console.error('Error deleting material:', response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error communicating with the server');
                console.error('AJAX error:', {xhr, status, error});
            }
        });
    }
}
