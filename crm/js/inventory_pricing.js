$(document).ready(function() {
    // Initialize DataTables
    const finishedProductsTable = $('#finishedProductsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: {
            details: true
        },
        ajax: {
            url: 'ajax/get_inventory_pricing.php',
            type: 'POST',
            data: function(d) {
                d.type = 'finished_product';
                // Add cache buster
                d.timestamp = new Date().getTime();
            }
        },
        columns: [
            { data: 'sku' },
            { 
                data: 'name',
                render: function(data, type, row) {
                    let html = `<div class="fw-medium">${data}</div>`;
                    if (row.description) {
                        html += `<small class="text-muted">${row.description}</small>`;
                    }
                    return html;
                }
            },
            { data: 'category_name' },
            { data: 'color_name' },
            {
                data: null,
                render: function(data, type, row) {
                    return `${row.length}×${row.width}×${row.height}`;
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
                className: 'text-center dt-nowrap',
                orderable: false,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-primary btn-sm update-price"
                                    data-id="${row.id}" data-type="finished_product">
                                <i class="bi bi-currency-dollar"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        stateSave: false,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
        }
    });

    const rawMaterialsTable = $('#rawMaterialsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: {
            details: true
        },
        ajax: {
            url: 'ajax/get_inventory_pricing.php',
            type: 'POST',
            data: function(d) {
                d.type = 'raw_material';
                // Add cache buster
                d.timestamp = new Date().getTime();
            }
        },
        columns: [
            { data: 'id' },
            { data: 'color_name' },
            {
                data: null,
                render: function(data, type, row) {
                    return `${row.length}×${row.width}×${row.height}`;
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    return `${row.warehouse_name}${row.location_details ? ' - ' + row.location_details : ''}`;
                }
            },
            { data: 'quantity' },
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
                className: 'text-center dt-nowrap',
                orderable: false,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-primary btn-sm update-price"
                                    data-id="${row.id}" data-type="raw_material">
                                <i class="bi bi-currency-dollar"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        stateSave: false,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
        }
    });

    // Handle price update button click
    $(document).on('click', '.update-price', function() {
        const id = $(this).data('id');
        const type = $(this).data('type');
        const table = type === 'finished_product' ? finishedProductsTable : rawMaterialsTable;
        
        // Get the row data properly
        const tr = $(this).closest('tr');
        let rowData = table.row(tr).data();
        
        if (!rowData) {
            // If row data not found in parent tr, try getting from child row (responsive view)
            const parentTr = $(this).closest('tr').prev();
            if (parentTr.length) {
                rowData = table.row(parentTr).data();
            }
        }
        
        if (!rowData) {
            console.error('Could not find row data');
            return;
        }

        $('#itemId').val(id);
        $('#itemType').val(type);
        $('#unitPrice').val(rowData.unit_price || '');
        $('#finalPrice').val(rowData.final_price || '');
        $('#markup').val(rowData.markup_percentage || '');

        const modal = new bootstrap.Modal($('#priceUpdateModal'));
        modal.show();
    });

    // Calculate final price based on unit price and markup
    $('#markup, #unitPrice').on('input', function() {
        const unitPrice = parseFloat($('#unitPrice').val()) || 0;
        const markup = parseFloat($('#markup').val()) || 0;
        const finalPrice = unitPrice * (1 + markup / 100);
        $('#finalPrice').val(finalPrice.toFixed(2));
    });

    // Function to show alerts
    function showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        // Create alert container if it doesn't exist
        if ($('#alertContainer').length === 0) {
            $('body').prepend('<div id="alertContainer" class="position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 1050;"></div>');
        }
        
        // Remove existing alerts
        $('#alertContainer').empty();
        
        // Add new alert
        $('#alertContainer').html(alertHtml);
        
        // Auto-hide after 3 seconds
        setTimeout(() => {
            $('.alert').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 3000);
    }

    // Handle save price button click
    $('#savePriceBtn').click(function() {
        const data = {
            id: parseInt($('#itemId').val()),
            type: $('#itemType').val(),
            unit_price: parseFloat($('#unitPrice').val()),
            markup_percentage: parseFloat($('#markup').val()),
            final_price: parseFloat($('#finalPrice').val())
        };

        $.ajax({
            url: 'ajax/update_inventory_price.php',
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    $('#priceUpdateModal').modal('hide');
                    showAlert('success', 'Price updated successfully');
                    finishedProductsTable.ajax.reload();
                    rawMaterialsTable.ajax.reload();
                } else {
                    showAlert('error', response.message || response.error || 'Failed to update price');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error updating price';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.error || response.message || errorMsg;
                } catch (e) {}
                showAlert('error', errorMsg);
                console.error('Update error:', xhr.responseText);
            }
        });
    });

    // Handle global markup modal show
    $('#globalMarkupModal').on('show.bs.modal', function(e) {
        const button = $(e.relatedTarget);
        const type = button.data('type');
        $('#globalItemType').val(type);
    });

    // Handle save global markup button click
    $('#saveGlobalMarkupBtn').click(function() {
        const data = {
            type: $('#globalItemType').val(),
            markup_percentage: $('#globalMarkup').val()
        };

        $.ajax({
            url: 'ajax/update_global_markup.php',
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    $('#globalMarkupModal').modal('hide');
                    showAlert('success', 'Global markup updated successfully');
                    finishedProductsTable.ajax.reload();
                    rawMaterialsTable.ajax.reload();
                } else {
                    showAlert('error', response.message || 'Failed to update global markup');
                }
            },
            error: function(xhr) {
                showAlert('error', 'Error updating global markup');
            }
        });
    });
});
