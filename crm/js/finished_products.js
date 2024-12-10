$(document).ready(function() {
    // DataTable initialization
    const table = $('#productsTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: 'ajax/get_finished_products.php',
            type: 'POST',
            data: function(d) {
                return {
                    ...d,
                    category: $('#categoryFilter').val(),
                    color: $('#colorFilter').val(),
                    status: $('#statusFilter').val()
                };
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables error:', error);
                showAlert('danger', 'Error loading products: ' + error);
            }
        },
        columns: [
            { data: 'sku' },
            { data: 'name' },
            { data: 'category_name' },
            { data: 'color_name' },
            { data: 'dimensions' },
            { data: 'total_stock' },
            { 
                data: 'status',
                render: function(data, type, row) {
                    const badgeClass = data === 'Out of Stock' ? 'bg-danger' :
                                     data === 'Low Stock' ? 'bg-warning' : 'bg-success';
                    return `<span class="badge ${badgeClass}">${data}</span>`;
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    const id = row.DT_RowId.replace('row_', '');
                    return `
                        <button class="btn btn-sm btn-primary edit-btn" data-id="${id}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-success stock-btn" data-id="${id}">
                            <i class="bi bi-box-seam"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="${id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    `;
                }
            }
        ],
        order: [[0, 'asc']],
        responsive: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            processing: "Loading products...",
            emptyTable: "No products found",
            zeroRecords: "No matching products found"
        }
    });

    // Load initial data for dropdowns
    loadCategories();
    loadColors();
    loadWarehouses();

    // Event handlers for filters
    $('#categoryFilter, #colorFilter, #statusFilter').on('change', function() {
        table.ajax.reload();
    });

    // Clear filters button
    $('#clearFilters').on('click', function() {
        $('#categoryFilter, #colorFilter, #statusFilter').val('');
        table.ajax.reload();
    });

    // Search input
    $('#searchInput').on('keyup', function() {
        table.ajax.reload();
    });

    // Save product button click
    $('#saveProduct').click(function() {
        saveProduct();
    });

    // Save stock movement button click
    $('#saveMovement').click(function() {
        saveStockMovement();
    });

    // Edit button click
    $('#productsTable').on('click', '.edit-btn', function() {
        const productId = $(this).data('id');
        editProduct(productId);
    });

    // Stock button click
    $('#productsTable').on('click', '.stock-btn', function() {
        const productId = $(this).data('id');
        $('#movementProductId').val(productId);
        $('#stockMovementModal').modal('show');
    });

    // Delete button click
    $('#productsTable').on('click', '.delete-btn', function() {
        const productId = $(this).data('id');
        if (confirm('Are you sure you want to delete this product?')) {
            deleteProduct(productId);
        }
    });
});

function loadCategories() {
    $.ajax({
        url: 'ajax/get_categories.php',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                populateCategoryDropdowns(response.categories);
            } else {
                console.error('Failed to load categories:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading categories:', error);
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
            if (response.success) {
                populateWarehouseDropdown(response);
            } else {
                console.error('Failed to load warehouses:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading warehouses:', error);
        }
    });
}

function populateWarehouseDropdown(response) {
    if (!response || !response.data) {
        console.error('Invalid warehouse data received:', response);
        return;
    }

    const select = $('#warehouse');
    select.empty();
    select.append('<option value="">Select Location</option>');
    
    response.data.forEach(function(warehouse) {
        select.append(
            $('<option></option>')
                .val(warehouse.id)
                .text(warehouse.name)
        );
    });
}

function saveProduct() {
    const formData = {
        productId: $('#productId').val(),
        sku: $('#sku').val(),
        name: $('#name').val(),
        category: $('#category').val(),
        color: $('#color').val(),
        length: $('#length').val(),
        width: $('#width').val(),
        height: $('#height').val(),
        weight: $('#weight').val(),
        quantity: $('#quantity').val(),
        warehouse: $('#warehouse').val(),
        location_details: $('#location_details').val(),
        description: $('#description').val()
    };

    // Show loading state
    const saveBtn = $('#saveProduct');
    const originalText = saveBtn.text();
    saveBtn.text('Saving...').prop('disabled', true);

    $.ajax({
        url: 'ajax/save_finished_product.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                // Close modal and reset form
                $('#addProductModal').modal('hide');
                $('#productForm')[0].reset();
                
                // Show success message
                showAlert('success', 'Product saved successfully');
                
                // Reload the table
                $('#productsTable').DataTable().ajax.reload();
            } else {
                showAlert('danger', 'Failed to save product: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            showAlert('danger', 'Error saving product: ' + error);
        },
        complete: function() {
            // Reset button state
            saveBtn.text(originalText).prop('disabled', false);
        }
    });
}

function saveStockMovement() {
    const form = $('#stockMovementForm');
    const formData = new FormData(form[0]);
    
    // Add warehouse name to form data
    const warehouseSelect = form.find('#warehouse');
    const selectedOption = warehouseSelect.find('option:selected');
    formData.append('warehouse_name', selectedOption.attr('data-name') || '');

    // Show loading state
    const saveBtn = $('#saveMovement');
    const originalText = saveBtn.text();
    saveBtn.text('Saving...').prop('disabled', true);

    $.ajax({
        url: 'ajax/save_stock_movement.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                // Close modal and reset form
                $('#stockMovementModal').modal('hide');
                form[0].reset();
                
                // Show success message
                showAlert('success', 'Stock movement saved successfully');
                
                // Reload the table
                $('#productsTable').DataTable().ajax.reload();
            } else {
                showAlert('danger', 'Failed to save stock movement: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            showAlert('danger', 'Error saving stock movement: ' + error);
        },
        complete: function() {
            // Reset button state
            saveBtn.text(originalText).prop('disabled', false);
        }
    });
}

function editProduct(productId) {
    // Reset form and update modal title
    $('#productForm')[0].reset();
    $('#modalTitle').text('Edit Product');
    $('#productId').val(productId);

    // Load product data
    $.ajax({
        url: 'ajax/get_finished_product.php',
        type: 'GET',
        data: { id: productId },
        success: function(response) {
            if (response.success) {
                populateEditForm(response.product);
                $('#addProductModal').modal('show');
            } else {
                showAlert('danger', 'Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            showAlert('danger', 'Error loading product data');
            console.error('AJAX error:', {xhr, status, error});
        }
    });
}

function populateEditForm(product) {
    $('#productId').val(product.id);
    $('#sku').val(product.sku);
    $('#name').val(product.name);
    $('#category').val(product.category_id);
    $('#color').val(product.color_id);
    $('#length').val(product.length);
    $('#width').val(product.width);
    $('#height').val(product.height);
    $('#weight').val(product.weight);
    $('#quantity').val(product.quantity);
    $('#warehouse').val(product.warehouse_id);
    $('#location_details').val(product.location_details);
    $('#description').val(product.description);
    $('#modalTitle').text('Edit Product');
}

function deleteProduct(productId) {
    $.ajax({
        url: 'ajax/delete_finished_product.php',
        type: 'POST',
        data: { id: productId },
        success: function(response) {
            if (response.success) {
                showAlert('success', 'Product deleted successfully');
                $('#productsTable').DataTable().ajax.reload();
            } else {
                showAlert('danger', 'Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            showAlert('danger', 'Error deleting product');
            console.error('AJAX error:', {xhr, status, error});
        }
    });
}

function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Remove any existing alerts
    $('.alert').remove();
    
    // Add new alert at the top of the container
    $('.container-fluid').prepend(alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}

function populateCategoryDropdowns(categories) {
    console.log('Populating categories:', categories);
    
    // Filter dropdown (with All Categories option)
    const filterSelect = $('#categoryFilter');
    filterSelect.empty();
    filterSelect.append('<option value="">All Categories</option>');
    
    // Form dropdown (without All Categories option)
    const formSelect = $('#category');
    formSelect.empty();
    formSelect.append('<option value="">Select Category</option>');
    
    if (Array.isArray(categories)) {
        categories.forEach(function(category) {
            console.log('Adding category:', category);
            
            // Add to filter dropdown
            filterSelect.append(
                $('<option></option>')
                    .val(category.id)
                    .text(category.name)
            );
            
            // Add to form dropdown
            formSelect.append(
                $('<option></option>')
                    .val(category.id)
                    .text(category.name)
            );
        });
    } else {
        console.error('Categories is not an array:', categories);
        showAlert('danger', 'Invalid categories data received from server');
    }
    
    console.log('Category dropdowns populated');
    console.log('Filter select options:', filterSelect.find('option').length);
    console.log('Form select options:', formSelect.find('option').length);
}

function populateColorDropdowns(colors) {
    // Filter dropdown (with All Colors option)
    const filterSelect = $('#colorFilter');
    filterSelect.empty();
    filterSelect.append('<option value="">All Colors</option>');
    
    // Form dropdown (without All Colors option)
    const formSelect = $('#color');
    formSelect.empty();
    formSelect.append('<option value="">Select Color</option>');
    
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
