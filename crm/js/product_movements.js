$(document).ready(function() {
    // Initialize DataTable for movements
    const movementsTable = $('#movementsTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: 'ajax/get_product_movements.php',
            type: 'POST',
            error: function(xhr, error, thrown) {
                console.error('DataTables error:', error);
                console.error('Server response:', xhr.responseText);
                showAlert('error', 'Error loading movements: ' + error);
            }
        },
        columns: [
            { 
                data: 'created_at',
                render: function(data) {
                    return moment(data).format('YYYY-MM-DD HH:mm');
                },
                defaultContent: '-'
            },
            { 
                data: 'item_type',
                render: function(data) {
                    const badges = {
                        'Finished Product': 'bg-info',
                        'Raw Material': 'bg-secondary',
                        'Unknown': 'bg-warning'
                    };
                    return `<span class="badge ${badges[data] || 'bg-warning'}">${data}</span>`;
                },
                defaultContent: 'Unknown'
            },
            { 
                data: 'item_name',
                defaultContent: 'Unknown Item'
            },
            { 
                data: 'movement_type',
                render: function(data) {
                    const badges = {
                        'In': 'bg-success',
                        'Out': 'bg-danger',
                        'Transfer': 'bg-primary',
                        'Adjustment': 'bg-warning'
                    };
                    return `<span class="badge ${badges[data] || 'bg-secondary'}">${data}</span>`;
                },
                defaultContent: '-'
            },
            { 
                data: 'quantity',
                defaultContent: '0'
            },
            { 
                data: 'source_warehouse',
                defaultContent: '-'
            },
            { 
                data: 'destination_warehouse',
                defaultContent: '-'
            },
            { 
                data: 'reference',
                defaultContent: '-'
            },
            { 
                data: 'created_by',
                defaultContent: 'System'
            }
        ],
        order: [[0, 'desc']]
    });

    // Initialize Select2 for product dropdown
    function initializeProductSelect() {
        $('#product').select2({
            width: '100%',
            ajax: {
                url: 'ajax/get_movement_items.php',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term,
                        page: params.page || 1,
                        item_type: $('#itemTypeFilter').val(),
                        category: $('#categoryFilter').val(),
                        color: $('#colorFilter').val(),
                        warehouse: $('#warehouseFilter').val()
                    };
                },
                processResults: function (response) {
                    if (!response.success) {
                        console.error('Error fetching items:', response.error);
                        return { results: [] };
                    }
                    
                    return {
                        results: response.data.map(function(item) {
                            return {
                                id: item.id,
                                text: item.display_name,
                                item_type: item.item_type
                            };
                        })
                    };
                },
                cache: true
            },
            placeholder: 'Select an item',
            allowClear: true,
            minimumInputLength: 0
        }).on('select2:select', function(e) {
            $(this).data('item_type', e.params.data.item_type);
        });

        // Initialize other select2 dropdowns
        $('#itemTypeFilter, #categoryFilter, #colorFilter, #warehouseFilter').select2({
            width: '100%'
        });
    }

    // Load filter options
    function loadFilterOptions() {
        // Load categories
        $.ajax({
            url: 'ajax/get_categories.php',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const select = $('#categoryFilter');
                    select.empty().append('<option value="">All Categories</option>');
                    response.categories.forEach(function(category) {
                        select.append(`<option value="${category.id}">${category.name}</option>`);
                    });
                }
            }
        });

        // Load colors
        $.ajax({
            url: 'ajax/get_colors.php',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const select = $('#colorFilter');
                    select.empty().append('<option value="">All Colors</option>');
                    response.colors.forEach(function(color) {
                        select.append(`<option value="${color.id}">${color.name}</option>`);
                    });
                }
            }
        });

        // Load warehouses
        $.ajax({
            url: 'ajax/get_warehouses_list.php',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const select = $('#warehouseFilter');
                    select.empty().append('<option value="">All Warehouses</option>');
                    response.data.forEach(function(warehouse) {
                        select.append(`<option value="${warehouse.id}">${warehouse.name}</option>`);
                    });
                }
            }
        });
    }

    // Handle item type filter changes
    function handleItemTypeChange() {
        const itemType = $('#itemTypeFilter').val();
        
        // Show/hide relevant filter groups
        if (itemType === 'finished_product') {
            $('#categoryFilterGroup').show();
            $('#colorFilterGroup').hide();
        } else if (itemType === 'raw_material') {
            $('#categoryFilterGroup').hide();
            $('#colorFilterGroup').show();
        } else {
            $('#categoryFilterGroup').show();
            $('#colorFilterGroup').show();
        }
        
        // Clear and trigger product dropdown update
        $('#product').val(null).trigger('change');
    }

    // Bind filter change events
    function bindFilterEvents() {
        $('#itemTypeFilter').on('change', handleItemTypeChange);
        
        // Trigger product dropdown update when any filter changes
        $('#categoryFilter, #colorFilter, #warehouseFilter').on('change', function() {
            $('#product').val(null).trigger('change');
        });
    }

    // Load warehouses dropdown
    function loadWarehouses() {
        $.ajax({
            url: 'ajax/get_warehouses_list.php',
            type: 'GET',
            success: function(response) {
                if (response.success && response.data) {
                    const sourceSelect = $('#sourceWarehouse');
                    const destSelect = $('#destinationWarehouse');
                    
                    sourceSelect.empty().append('<option value="">Select Warehouse</option>');
                    destSelect.empty().append('<option value="">Select Warehouse</option>');
                    
                    response.data.forEach(function(warehouse) {
                        const option = `<option value="${warehouse.id}">${warehouse.name}</option>`;
                        sourceSelect.append(option);
                        destSelect.append(option);
                    });
                }
            }
        });
    }

    // Movement type selection
    $('.movement-type-card').click(function() {
        $('.movement-type-card').removeClass('selected');
        $(this).addClass('selected');
        
        const type = $(this).data('type');
        $('#movementType').val(type);
        $('#movementForm').show();
        
        // Show/hide warehouse fields based on movement type
        switch(type) {
            case 'in':
                $('#sourceWarehouseDiv').hide();
                $('#destinationWarehouseDiv').show();
                $('#formTitle').text('Record Stock In');
                break;
            case 'out':
                $('#sourceWarehouseDiv').show();
                $('#destinationWarehouseDiv').hide();
                $('#formTitle').text('Record Stock Out');
                break;
            case 'transfer':
                $('#sourceWarehouseDiv').show();
                $('#destinationWarehouseDiv').show();
                $('#formTitle').text('Record Stock Transfer');
                break;
            case 'adjustment':
                $('#sourceWarehouseDiv').show();
                $('#destinationWarehouseDiv').hide();
                $('#formTitle').text('Record Stock Adjustment');
                break;
        }
    });

    // Cancel movement button
    $('#cancelMovement').click(function() {
        $('#movementForm').hide();
        $('#productMovementForm')[0].reset();
        $('.movement-type-card').removeClass('selected');
    });

    // Handle form submission
    $('#productMovementForm').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            product_id: $('#product').val(),
            movement_type: $('#movementType').val(),
            quantity: $('#quantity').val(),
            source_warehouse_id: $('#sourceWarehouse').val(),
            destination_warehouse_id: $('#destinationWarehouse').val(),
            reference_type: $('#referenceType').val(),
            reference_id: $('#referenceId').val(),
            notes: $('#notes').val(),
            item_type: $('#product').data('item_type')
        };

        $.ajax({
            url: 'ajax/save_product_movement.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Movement recorded successfully');
                    $('#movementForm').hide();
                    $('#productMovementForm')[0].reset();
                    $('.movement-type-card').removeClass('selected');
                    movementsTable.ajax.reload();
                } else {
                    showAlert('danger', response.error || 'Failed to record movement');
                }
            },
            error: function(xhr, status, error) {
                showAlert('danger', 'An error occurred while saving the movement');
            }
        });
    });

    // Initialize
    initializeProductSelect();
    loadFilterOptions();
    bindFilterEvents();
    loadWarehouses();

    // Helper function to show alerts
    function showAlert(type, message) {
        const alertDiv = $(`<div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>`);
        
        $('.container-fluid').prepend(alertDiv);
        setTimeout(() => alertDiv.alert('close'), 5000);
    }

    // Initialize color filter dropdown
    $('#colorFilter').select2({
        placeholder: 'Select Color',
        allowClear: true,
        ajax: {
            url: 'ajax/get_colors.php',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    search: params.term
                };
            },
            processResults: function(data) {
                return {
                    results: data.colors.map(function(color) {
                        return {
                            id: color.id,
                            text: color.name
                        };
                    })
                };
            },
            cache: true
        }
    });
});
