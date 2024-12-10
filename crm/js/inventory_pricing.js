$(document).ready(function() {
    // Initialize DataTables
    const finishedProductsTable = $('#finishedProductsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: 'ajax/get_inventory_pricing.php',
            type: 'POST',
            data: function(d) {
                d.type = 'finished_product';
            }
        },
        columns: [
            { data: 'sku' },
            { 
                data: 'name',
                render: function(data, type, row) {
                    return `<div class="fw-medium">${data}</div>`;
                }
            },
            { data: 'category_name' },
            { data: 'color_name' },
            {
                data: null,
                render: function(data, type, row) {
                    return `${row.length}x${row.width}x${row.height}`;
                }
            },
            {
                data: 'unit_price',
                render: function(data, type, row) {
                    return `$${parseFloat(data).toFixed(2)}`;
                }
            },
            {
                data: 'final_price',
                render: function(data, type, row) {
                    return data ? `$${parseFloat(data).toFixed(2)}` : '-';
                }
            },
            {
                data: null,
                className: 'text-center',
                render: function(data, type, row) {
                    return `
                        <button type="button" class="btn btn-outline-primary btn-sm update-price"
                                data-id="${row.id}" data-type="finished_product">
                            <i class="fas fa-dollar-sign"></i>
                        </button>
                    `;
                }
            }
        ],
        order: [[1, 'asc']],
        pageLength: 25
    });

    const rawMaterialsTable = $('#rawMaterialsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: 'ajax/get_inventory_pricing.php',
            type: 'POST',
            data: function(d) {
                d.type = 'raw_material';
            }
        },
        columns: [
            { data: 'id' },
            { data: 'color_name' },
            {
                data: null,
                render: function(data, type, row) {
                    return `${row.length}x${row.width}x${row.height}`;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `${row.warehouse_name}${row.location_details ? ' - ' + row.location_details : ''}`;
                }
            },
            { 
                data: 'quantity',
                className: 'text-center'
            },
            {
                data: 'unit_price',
                render: function(data, type, row) {
                    return `$${parseFloat(data).toFixed(2)}`;
                }
            },
            {
                data: 'final_price',
                render: function(data, type, row) {
                    return data ? `$${parseFloat(data).toFixed(2)}` : '-';
                }
            },
            {
                data: null,
                className: 'text-center',
                render: function(data, type, row) {
                    return `
                        <button type="button" class="btn btn-outline-primary btn-sm update-price"
                                data-id="${row.id}" data-type="raw_material">
                            <i class="fas fa-dollar-sign"></i>
                        </button>
                    `;
                }
            }
        ],
        order: [[1, 'asc']],
        pageLength: 25
    });

    // Handle price update button click
    $('.table').on('click', '.update-price', function() {
        const id = $(this).data('id');
        const type = $(this).data('type');
        const table = type === 'finished_product' ? finishedProductsTable : rawMaterialsTable;
        const row = table.row($(this).closest('tr')).data();

        $('#itemId').val(id);
        $('#itemType').val(type);
        $('#unitPrice').val(row.unit_price);
        $('#finalPrice').val(row.final_price || '');
        $('#markup').val('');

        const modal = new bootstrap.Modal($('#priceUpdateModal'));
        modal.show();
    });

    // Calculate final price based on unit price and markup
    $('#markup').on('input', function() {
        const unitPrice = parseFloat($('#unitPrice').val()) || 0;
        const markup = parseFloat($(this).val()) || 0;
        const finalPrice = unitPrice * (1 + markup / 100);
        $('#finalPrice').val(finalPrice.toFixed(2));
    });

    // Calculate markup based on unit price and final price
    $('#finalPrice').on('input', function() {
        const unitPrice = parseFloat($('#unitPrice').val()) || 0;
        const finalPrice = parseFloat($(this).val()) || 0;
        
        if (unitPrice > 0 && finalPrice > 0) {
            const markup = ((finalPrice / unitPrice) - 1) * 100;
            $('#markup').val(markup.toFixed(1));
        }
    });

    // Handle save button click
    $('#savePriceBtn').click(function() {
        const unitPrice = parseFloat($('#unitPrice').val());
        const finalPrice = parseFloat($('#finalPrice').val()) || null;
        
        if (isNaN(unitPrice) || unitPrice < 0) {
            showAlert('danger', 'Please enter a valid unit price');
            return;
        }
        
        if (finalPrice !== null && (isNaN(finalPrice) || finalPrice < 0)) {
            showAlert('danger', 'Please enter a valid final price');
            return;
        }

        const data = {
            id: $('#itemId').val(),
            type: $('#itemType').val(),
            unit_price: unitPrice,
            final_price: finalPrice
        };

        $.ajax({
            url: 'ajax/update_inventory_price.php',
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message || 'Price updated successfully');
                    $('#priceUpdateModal').modal('hide');
                    
                    // Refresh the appropriate table
                    if (data.type === 'finished_product') {
                        finishedProductsTable.ajax.reload(null, false);
                    } else {
                        rawMaterialsTable.ajax.reload(null, false);
                    }
                } else {
                    showAlert('danger', response.error || 'Failed to update price');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while updating the price';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        errorMessage = response.error;
                    }
                } catch (e) {
                    console.error('Error parsing error response:', e);
                }
                showAlert('danger', errorMessage);
            }
        });
    });

    // Handle global markup modal show
    $('#globalMarkupModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const type = button.data('type');
        $('#globalItemType').val(type);
        $('#globalMarkup').val('');
    });

    // Handle global markup save
    $('#saveGlobalMarkupBtn').click(function() {
        const markup = parseFloat($('#globalMarkup').val());
        const type = $('#globalItemType').val();
        
        if (isNaN(markup) || markup < 0) {
            showAlert('danger', 'Please enter a valid markup percentage');
            return;
        }

        // Show confirmation dialog
        if (!confirm(`Are you sure you want to apply ${markup}% markup to all ${type.replace('_', ' ')}s? This action cannot be undone.`)) {
            return;
        }

        const data = {
            type: type,
            markup: markup
        };

        $.ajax({
            url: 'ajax/update_global_markup.php',
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    $('#globalMarkupModal').modal('hide');
                    
                    // Refresh the appropriate table
                    if (type === 'finished_product') {
                        finishedProductsTable.ajax.reload(null, false);
                    } else {
                        rawMaterialsTable.ajax.reload(null, false);
                    }
                } else {
                    showAlert('danger', response.error || 'Failed to update global markup');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while updating global markup';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        errorMessage = response.error;
                    }
                } catch (e) {
                    console.error('Error parsing error response:', e);
                }
                showAlert('danger', errorMessage);
            }
        });
    });

    // Helper function to show alerts
    function showAlert(type, message) {
        const alertDiv = $(`
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);
        $('.container-fluid').prepend(alertDiv);
        setTimeout(() => alertDiv.alert('close'), 5000);
    }
});
