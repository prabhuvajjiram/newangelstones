// Document ready handler
$(document).ready(function() {
    // Initialize DataTable
    initializeDataTable();
    
    // Load dropdowns
    loadCategories();
    loadColors();
    loadWarehouses();
    
    // Add Product button click
    $('#addProductBtn').click(function() {
        resetForm();
        $('#modalTitle').text('Add New Product');
        $('#productId').val(''); // Ensure productId is empty for new products
        $('#addProductModal').modal('show');
    });
    
    // Save Product button click
    $('#saveProduct').click(function(e) {
        e.preventDefault();
        saveProduct(e);
    });
    
    // Form submit handler
    $('#productForm').submit(function(e) {
        e.preventDefault();
        saveProduct(e);
    });
    
    // Add unit conversion row button
    $('#addUnitRow').click(function() {
        addUnitConversionRow();
    });
    
    // Initialize image upload
    initializeImageUpload();
    
    // Handle image deletion
    $(document).on('click', '.delete-image', function() {
        const imageId = $(this).data('image-id');
        deleteProductImage(imageId);
    });
});

// Initialize DataTable
function initializeDataTable() {
    if (!$.fn.DataTable.isDataTable('#productsTable')) {
        const table = $('#productsTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: 'ajax/get_products.php',
                dataSrc: function(json) {
                    console.log('Raw AJAX response:', json);
                    
                    if (!json.success) {
                        showAlert('danger', json.message || 'Error loading products');
                        return [];
                    }
                    
                    if (!json.products || !Array.isArray(json.products)) {
                        console.error('Invalid products data:', json);
                        showAlert('danger', 'Invalid products data received from server');
                        return [];
                    }
                    
                    console.log('Processed products:', json.products);
                    return json.products;
                },
                error: function(xhr, error, thrown) {
                    console.error('AJAX error:', {
                        xhr: xhr,
                        error: error,
                        thrown: thrown
                    });
                    showAlert('danger', 'Failed to load products');
                }
            },
            columns: [
                { 
                    data: 'sku',
                    render: function(data) {
                        return data || '';
                    }
                },
                { 
                    data: 'name',
                    render: function(data) {
                        return data || '';
                    }
                },
                { 
                    data: 'category_name',
                    render: function(data) {
                        return data || '';
                    }
                },
                { 
                    data: 'color_name',
                    render: function(data) {
                        return data || '';
                    }
                },
                { 
                    data: null,
                    render: function(data, type, row) {
                        const length = row.length || '';
                        const width = row.width || '';
                        const height = row.height || '';
                        return length && width && height ? `${length}×${width}×${height}` : '';
                    }
                },
                { 
                    data: 'current_stock',
                    render: function(data) {
                        return data || '0';
                    }
                },
                {
                    data: 'status',
                    render: function(data, type, row) {
                        const stock = parseInt(row.current_stock || 0);
                        let badgeClass = 'bg-success';
                        let status = 'In Stock';
                        
                        if (stock <= 0) {
                            badgeClass = 'bg-danger';
                            status = 'Out of Stock';
                        } else if (stock < 5) {
                            badgeClass = 'bg-warning';
                            status = 'Low Stock';
                        }
                        
                        return `<span class="badge ${badgeClass}">${status}</span>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        return `
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-primary view-product" data-id="${row.id}">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button type="button" class="btn btn-warning edit-product" data-id="${row.id}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-success stock-movement" data-id="${row.id}">
                                    <i class="bi bi-box-arrow-in-down"></i>
                                </button>
                                <button type="button" class="btn btn-danger delete-product" data-id="${row.id}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        `;
                    }
                }
            ],
            order: [[0, 'asc']],  // Sort by SKU by default
            responsive: true,
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            language: {
                emptyTable: 'No products found',
                loadingRecords: 'Loading products...',
                processing: 'Processing...',
                zeroRecords: 'No matching products found'
            },
            drawCallback: function(settings) {
                console.log('DataTable draw callback:', {
                    data: settings.aoData,
                    rows: settings.aiDisplay.length
                });
            }
        });

        // Log when table is initialized
        console.log('DataTable initialized:', {
            table: table,
            columns: table.columns().header().toArray()
        });
    }
}

// Edit product button click handler
$(document).on('click', '.edit-product', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const id = $(this).data('id');
    if (id) {
        editProduct(id);
    } else {
        console.error('No product ID found for edit button');
        showAlert('danger', 'Invalid product ID');
    }
});

// Edit product function
function editProduct(id) {
    console.log('Editing product:', id);
    
    // Reset form first
    resetForm();
    
    // Load product details
    $.ajax({
        url: 'ajax/get_product_details.php',
        type: 'GET',
        data: { id: id },
        success: function(response) {
            console.log('Product details response:', response);
            
            if (response.success && response.product) {
                // Set modal title and show modal
                $('#modalTitle').text('Edit Product');
                $('#productId').val(id); // Set productId for update
                $('#addProductModal').modal('show');
                
                // Populate form with product data
                populateEditForm(response.product);
                
                // Load product images
                loadProductImages(id);
            } else {
                showAlert('danger', response.message || 'Error loading product details');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading product details:', {
                xhr: xhr,
                status: status,
                error: error
            });
            showAlert('danger', 'Error loading product details');
        }
    });
}

// Reset form function
function resetForm() {
    console.log('Resetting form');
    
    // Reset form fields
    $('#productForm')[0].reset();
    
    // Clear validation states
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();
    
    // Clear tables
    $('#unitConversionsTable tbody').empty();
    
    // Reset hidden fields
    $('#productId').val(''); // Clear productId on reset
    
    console.log('Form reset complete');
}

// Populate edit form
function populateEditForm(product) {
    console.log('Populating form with product:', product);
    
    // Set hidden product ID
    $('#productId').val(product.id);
    
    // Basic details
    $('#sku').val(product.sku || '');
    $('#name').val(product.name || '');
    
    // Wait for dropdowns to load before setting values
    setTimeout(function() {
        $('#category_id').val(product.category_id || '').trigger('change');
        $('#color_id').val(product.color_id || '').trigger('change');
        $('#location_id').val(product.location_id || '').trigger('change');
    }, 500);
    
    // Dimensions
    $('#length').val(product.length || '');
    $('#width').val(product.width || '');
    $('#height').val(product.height || '');
    $('#weight').val(product.weight || '');
    
    // Stock and location
    $('#quantity').val(product.current_stock || '0');
    $('#location_details').val(product.location_details || '');
    
    // Description
    $('#description').val(product.description || '');
    
    // Clear and populate unit conversions
    $('#unitConversionsTable tbody').empty();
    if (product.unit_conversions && Array.isArray(product.unit_conversions)) {
        product.unit_conversions.forEach(function(unit) {
            addUnitConversionRow(unit);
        });
    }
    
    console.log('Form populated with product data');
}

// Save product
function saveProduct(event) {
    event.preventDefault();
    
    const $form = $('#productForm');
    const $saveButton = $('#saveProduct');
    const originalText = $saveButton.text();
    
    // Log form data for debugging
    console.log('Form data:', {
        productId: $('#productId').val(),
        sku: $('#sku').val(),
        name: $('#name').val(),
        category_id: $('#category_id').val(),
        color_id: $('#color_id').val(),
        location_id: $('#location_id').val(),
        quantity: $('#quantity').val(),
        length: $('#length').val(),
        width: $('#width').val(),
        height: $('#height').val(),
        weight: $('#weight').val(),
        description: $('#description').val(),
        location_details: $('#location_details').val()
    });
    
    // Basic validation
    if (!$('#sku').val() || !$('#name').val() || !$('#category_id').val()) {
        showAlert('danger', 'Please fill in all required fields');
        return;
    }
    
    // Validate numeric fields
    const numericFields = ['length', 'width', 'height', 'weight', 'quantity'];
    for (const field of numericFields) {
        const value = $(`#${field}`).val();
        if (value && isNaN(value)) {
            showAlert('danger', `${field.charAt(0).toUpperCase() + field.slice(1)} must be a number`);
            return;
        }
    }
    
    // Disable save button
    $saveButton.prop('disabled', true).text('Saving...');
    
    // Create FormData object
    const formData = new FormData($form[0]);
    
    // Add unit conversions
    const unitConversions = [];
    $('#unitConversionsTable tbody tr').each(function() {
        const $row = $(this);
        const unitType = $row.find('.unit-type').val();
        const baseUnit = $row.find('.base-unit').val();
        const conversionRatio = $row.find('.conversion-ratio').val();
        
        if (unitType && baseUnit && conversionRatio) {
            unitConversions.push({
                unit_type: unitType,
                base_unit: baseUnit,
                conversion_ratio: conversionRatio
            });
        }
    });
    formData.append('unit_conversions', JSON.stringify(unitConversions));
    
    // Make the AJAX request
    $.ajax({
        url: 'ajax/save_product.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log('Save response:', response);
            
            try {
                // Parse response if it's a string
                const result = typeof response === 'string' ? JSON.parse(response) : response;
                
                if (result.success) {
                    showAlert('success', result.message || 'Product saved successfully!');
                    
                    // Upload images if any
                    if ($('#productImages')[0].files.length > 0) {
                        uploadProductImages(result.productId);
                    }
                    
                    // Close the modal
                    $('#addProductModal').modal('hide');
                    
                    // Reset the form
                    resetForm();
                    
                    // Refresh the products table
                    if ($.fn.DataTable.isDataTable('#productsTable')) {
                        $('#productsTable').DataTable().ajax.reload(null, false);
                    }
                } else {
                    throw new Error(result.message || 'Failed to save product');
                }
            } catch (error) {
                console.error('Error processing response:', error);
                showAlert('danger', error.message || 'Failed to save product');
            }
        },
        error: function(xhr, status, error) {
            console.error('Save error:', {
                xhr: xhr,
                status: status,
                error: error,
                response: xhr.responseText
            });
            
            try {
                const response = JSON.parse(xhr.responseText);
                showAlert('danger', response.message || 'Failed to save product');
            } catch (e) {
                showAlert('danger', 'An unexpected error occurred while saving');
            }
        },
        complete: function() {
            // Re-enable save button
            $saveButton.prop('disabled', false).text(originalText);
        }
    });
}

// Handle image upload after product save
function uploadProductImages(productId) {
    console.log('Uploading images for product:', productId);
    const $input = $('#productImages')[0];
    
    if ($input.files.length === 0) {
        console.log('No images to upload');
        return;
    }
    
    const formData = new FormData();
    formData.append('productId', productId);
    
    // Log files being uploaded
    console.log('Files to upload:', Array.from($input.files).map(f => f.name));
    
    Array.from($input.files).forEach((file, index) => {
        formData.append('images[]', file);
    });
    
    $.ajax({
        url: 'ajax/upload_product_images.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log('Upload response:', response);
            
            if (response.success) {
                if (response.images.length > 0) {
                    showAlert('success', 'Images uploaded successfully');
                }
                if (response.errors.length > 0) {
                    showAlert('warning', 'Some images failed to upload: ' + response.errors.join(', '));
                }
                // Refresh images
                loadProductImages(productId);
            } else {
                showAlert('danger', response.message || 'Failed to upload images');
            }
        },
        error: function(xhr, status, error) {
            console.error('Upload error:', {
                xhr: xhr,
                status: status,
                error: error,
                response: xhr.responseText
            });
            showAlert('danger', 'Failed to upload images');
        }
    });
}

// Load categories
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

// Populate category dropdowns
function populateCategoryDropdowns(categories) {
    console.log('Populating categories:', categories);
    
    // Filter dropdown (with All Categories option)
    const filterSelect = $('#categoryFilter');
    filterSelect.empty();
    filterSelect.append('<option value="">All Categories</option>');
    
    // Form dropdown (without All Categories option)
    const formSelect = $('#category_id');
    formSelect.empty();
    formSelect.append('<option value="">Select Category</option>');
    
    if (Array.isArray(categories)) {
        categories.forEach(function(category) {
            const categoryId = category.id || category.category_id;
            const categoryName = category.name || category.category_name;
            
            console.log('Adding category:', {
                id: categoryId,
                name: categoryName,
                raw: category
            });
            
            // Add to filter dropdown
            filterSelect.append(
                $('<option></option>')
                    .val(categoryId)
                    .text(categoryName)
            );
            
            // Add to form dropdown
            formSelect.append(
                $('<option></option>')
                    .val(categoryId)
                    .text(categoryName)
            );
        });

        // Log the final state
        console.log('Category dropdowns populated:', {
            filterOptions: filterSelect.find('option').length,
            formOptions: formSelect.find('option').length,
            formValue: formSelect.val(),
            formSelected: formSelect.find('option:selected').text()
        });
    } else {
        console.error('Categories is not an array:', categories);
        showAlert('danger', 'Invalid categories data received from server');
    }
}

// Load colors
function loadColors() {
    $.ajax({
        url: 'ajax/get_colors.php',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                populateColorDropdowns(response.colors);
            } else {
                console.error('Failed to load colors:', response.message);
                showAlert('danger', 'Failed to load colors');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading colors:', error);
            showAlert('danger', 'Error loading colors');
        }
    });
}

// Populate color dropdowns
function populateColorDropdowns(colors) {
    console.log('Populating colors:', colors);
    
    // Filter dropdown (with All Colors option)
    const filterSelect = $('#colorFilter');
    filterSelect.empty();
    filterSelect.append('<option value="">All Colors</option>');
    
    // Form dropdown
    const formSelect = $('#color_id');
    formSelect.empty();
    formSelect.append('<option value="">Select Color</option>');
    
    if (Array.isArray(colors)) {
        colors.forEach(function(color) {
            const colorId = color.id;
            const colorName = color.name;
            
            // Add to filter dropdown
            filterSelect.append(
                $('<option></option>')
                    .val(colorId)
                    .text(colorName)
            );
            
            // Add to form dropdown
            formSelect.append(
                $('<option></option>')
                    .val(colorId)
                    .text(colorName)
            );
        });

        console.log('Color dropdowns populated:', {
            filterOptions: filterSelect.find('option').length,
            formOptions: formSelect.find('option').length
        });
    } else {
        console.error('Colors is not an array:', colors);
        showAlert('danger', 'Invalid colors data received from server');
    }
}

// Load warehouses
function loadWarehouses() {
    $.ajax({
        url: 'ajax/get_warehouses.php',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                populateWarehouseDropdown(response.warehouses);
            } else {
                console.error('Failed to load warehouses:', response.message);
                showAlert('danger', 'Failed to load locations');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading warehouses:', error);
            showAlert('danger', 'Error loading locations');
        }
    });
}

// Populate warehouse dropdown
function populateWarehouseDropdown(warehouses) {
    console.log('Populating warehouses:', warehouses);
    
    // Form dropdown
    const formSelect = $('#location_id');
    formSelect.empty();
    formSelect.append('<option value="">Select Location</option>');
    
    if (Array.isArray(warehouses)) {
        warehouses.forEach(function(warehouse) {
            formSelect.append(
                $('<option></option>')
                    .val(warehouse.id)
                    .text(warehouse.name)
            );
        });

        console.log('Location dropdown populated:', {
            options: formSelect.find('option').length
        });
    } else {
        console.error('Warehouses is not an array:', warehouses);
        showAlert('danger', 'Invalid location data received from server');
    }
}

// View product
function viewProduct(productId) {
    $.ajax({
        url: 'ajax/get_product_details.php',
        type: 'GET',
        data: { id: productId },
        success: function(response) {
            if (response.success) {
                populateViewModal(response.product);
                $('#viewProductModal').modal('show');
            } else {
                showAlert('danger', 'Error loading product details: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            showAlert('danger', 'Error loading product details');
        }
    });
}

// Populate view modal
function populateViewModal(product) {
    // Basic information
    $('#view-sku').text(product.sku);
    $('#view-name').text(product.name);
    $('#view-category').text(product.category_name);
    $('#view-color').text(product.color_name);
    
    // Dimensions
    $('#view-length').text(product.length + ' cm');
    $('#view-width').text(product.width + ' cm');
    $('#view-height').text(product.height + ' cm');
    $('#view-volume').text(((product.length * product.width * product.height) / 1000000).toFixed(3) + ' m³');
    
    // Unit conversions
    let unitsHtml = `
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Unit Type</th>
                    <th>Base Unit</th>
                    <th>Conversion Ratio</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    if (product.units && product.units.length > 0) {
        product.units.forEach(unit => {
            unitsHtml += `
                <tr>
                    <td>${unit.unit_type}</td>
                    <td>${unit.base_unit}</td>
                    <td>${unit.conversion_ratio}</td>
                </tr>
            `;
        });
    } else {
        unitsHtml += '<tr><td colspan="3" class="text-center">No unit conversions defined</td></tr>';
    }
    
    unitsHtml += '</tbody></table>';
    $('#view-units-table').html(unitsHtml);
    
    // Supplier information
    let suppliersHtml = `
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Supplier</th>
                    <th>Product Code</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    if (product.suppliers && product.suppliers.length > 0) {
        product.suppliers.forEach(supplier => {
            const statusClass = 
                supplier.status === 'active' ? 'success' :
                supplier.status === 'inactive' ? 'warning' : 'danger';
            
            suppliersHtml += `
                <tr>
                    <td>${supplier.name}</td>
                    <td>${supplier.product_code}</td>
                    <td><span class="badge bg-${statusClass}">${supplier.status}</span></td>
                </tr>
            `;
        });
    } else {
        suppliersHtml += '<tr><td colspan="3" class="text-center">No suppliers assigned</td></tr>';
    }
    
    suppliersHtml += '</tbody></table>';
    $('#view-suppliers-table').html(suppliersHtml);
}

// Delete product
function deleteProduct(id) {
    if (!confirm('Are you sure you want to delete this product?')) {
        return;
    }
    
    console.log('Deleting product:', id);
    
    $.ajax({
        url: 'ajax/delete_finished_product.php',
        type: 'POST',
        data: { id: id },
        success: function(response) {
            console.log('Delete response:', response);
            
            if (response.success) {
                showAlert('success', 'Product deleted successfully!');
                
                // Refresh the products table
                if ($.fn.DataTable.isDataTable('#productsTable')) {
                    $('#productsTable').DataTable().ajax.reload(null, false);
                }
            } else {
                showAlert('danger', response.message || 'Failed to delete product');
            }
        },
        error: function(xhr, status, error) {
            console.error('Delete error:', {
                xhr: xhr,
                status: status,
                error: error
            });
            showAlert('danger', 'Error deleting product');
        }
    });
}

// Save stock movement
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

// Show alert function
function showAlert(type, message, autoDismiss = true) {
    // Remove any existing alerts
    $('.alert').remove();
    
    // Create the alert element
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Add the alert to the page
    if ($('#alertPlaceholder').length) {
        $('#alertPlaceholder').html(alertHtml);
    } else {
        // If no placeholder exists, add alert before the form
        $('#productForm').before(alertHtml);
    }
    
    // Auto dismiss success alerts after 3 seconds
    if (autoDismiss && type === 'success') {
        setTimeout(function() {
            $('.alert').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 3000);
    }
}

// Add image upload functionality
function initializeImageUpload() {
    const $imageUploadContainer = $(`
        <div class="mb-3">
            <label class="form-label">Product Images</label>
            <div class="image-upload-container">
                <div class="current-images mb-2"></div>
                <input type="file" class="form-control" id="productImages" name="images[]" multiple accept="image/*">
                <small class="text-muted">You can select multiple images. Supported formats: JPG, PNG, GIF</small>
            </div>
            <div id="imagePreview" class="mt-2 d-flex flex-wrap gap-2"></div>
        </div>
    `);
    
    // Add after location details
    $('#location_details').closest('.mb-3').after($imageUploadContainer);
    
    // Handle file selection
    $('#productImages').change(function(e) {
        const files = e.target.files;
        const $preview = $('#imagePreview');
        $preview.empty();
        
        if (files.length > 0) {
            Array.from(files).forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const $img = $(`
                            <div class="position-relative" style="width: 100px;">
                                <img src="${e.target.result}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                <div class="position-absolute top-0 end-0">
                                    <button type="button" class="btn btn-danger btn-sm remove-preview" data-file="${file.name}">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                        `);
                        $preview.append($img);
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    });
    
    // Handle preview image removal
    $(document).on('click', '.remove-preview', function() {
        const fileName = $(this).data('file');
        const $input = $('#productImages')[0];
        const dt = new DataTransfer();
        
        Array.from($input.files).forEach(file => {
            if (file.name !== fileName) {
                dt.items.add(file);
            }
        });
        
        $input.files = dt.files;
        $(this).closest('.position-relative').remove();
    });
}

// Function to load product images
function loadProductImages(productId) {
    $.ajax({
        url: 'ajax/get_product_images.php',
        type: 'GET',
        data: { productId: productId },
        success: function(response) {
            if (response.success && response.images) {
                const $container = $('.current-images');
                $container.empty();
                
                if (response.images.length > 0) {
                    $container.append('<div class="d-flex flex-wrap gap-2 mb-2"></div>');
                    const $imageContainer = $container.find('.d-flex');
                    
                    response.images.forEach(image => {
                        const $img = $(`
                            <div class="position-relative" style="width: 100px;">
                                <img src="/${image.path}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                <div class="position-absolute top-0 end-0">
                                    <button type="button" class="btn btn-danger btn-sm delete-image" data-image-id="${image.id}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        `);
                        $imageContainer.append($img);
                    });
                }
            }
        }
    });
}

// Delete product image
function deleteProductImage(imageId) {
    if (!confirm('Are you sure you want to delete this image?')) return;
    
    $.ajax({
        url: 'ajax/delete_product_image.php',
        type: 'POST',
        data: { imageId: imageId },
        success: function(response) {
            if (response.success) {
                showAlert('success', 'Image deleted successfully');
                loadProductImages($('#productId').val());
            } else {
                showAlert('danger', response.message || 'Failed to delete image');
            }
        },
        error: function() {
            showAlert('danger', 'Failed to delete image');
        }
    });
}
