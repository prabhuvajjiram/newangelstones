$(document).ready(function() {
    // Initialize DataTables
    const historyTable = $('#historyTable').DataTable({
        ajax: {
            url: 'ajax/get_batch_history.php',
            dataSrc: ''
        },
        columns: [
            { data: 'id' },
            { data: 'operation_type' },
            { 
                data: 'status',
                render: function(data) {
                    const statusClasses = {
                        'pending': 'badge-warning',
                        'in_progress': 'badge-info',
                        'completed': 'badge-success',
                        'failed': 'badge-danger'
                    };
                    return `<span class="badge ${statusClasses[data]}">${data}</span>`;
                }
            },
            { data: 'created_by' },
            { data: 'created_at' },
            { data: 'completed_at' },
            {
                data: null,
                render: function(data) {
                    return `<button class="btn btn-sm btn-info view-details" data-id="${data.id}">View Details</button>`;
                }
            }
        ],
        order: [[4, 'desc']]
    });

    // Load warehouses for dropdowns
    function loadWarehouses() {
        $.get('ajax/get_warehouses_list.php', function(response) {
            if (response.success && response.data) {
                const options = response.data.map(w => `<option value="${w.id}">${w.name}</option>`).join('');
                $('#sourceWarehouse, #destWarehouse, #quantityWarehouse').html('<option value="">Select Warehouse</option>' + options);
            }
        });
    }

    // Load items based on type
    function loadItems(type, targetSelect) {
        $.get('ajax/get_batch_items.php', { type: type }, function(response) {
            if (response.success && response.data) {
                const options = response.data.map(item => 
                    `<option value="${item.id}">${item.name} (Current Stock: ${item.quantity})</option>`
                ).join('');
                $(targetSelect).html('<option value="">Select Items</option>' + options);
            }
        });
    }

    // Initialize Select2 for multiple select
    $('#moveItems, #priceItems, #quantityItems').select2({
        width: '100%',
        placeholder: 'Select items'
    });

    // Handle item type changes
    $('#moveItemType, #priceItemType, #quantityItemType').change(function() {
        const type = $(this).val();
        const targetSelect = $(this).closest('form').find('select[multiple]');
        loadItems(type, targetSelect);
    });

    // Handle bulk movement form submission
    $('#bulkMovementForm').submit(function(e) {
        e.preventDefault();
        const data = {
            type: $('#moveItemType').val(),
            source_warehouse: $('#sourceWarehouse').val(),
            dest_warehouse: $('#destWarehouse').val(),
            items: $('#moveItems').val()
        };

        $.ajax({
            url: 'ajax/process_bulk_movement.php',
            method: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Bulk movement operation started');
                    historyTable.ajax.reload();
                } else {
                    showAlert('danger', response.error);
                }
            }
        });
    });

    // Handle batch price update form submission (admin only)
    if (typeof isAdmin !== 'undefined' && isAdmin) {
        $('#batchPriceForm').submit(function(e) {
            e.preventDefault();
            const data = {
                type: $('#priceItemType').val(),
                update_type: $('#priceUpdateType').val(),
                value: $('#priceValue').val(),
                items: $('#priceItems').val(),
                reason: $('#priceReason').val()
            };

            $.ajax({
                url: 'ajax/process_batch_price_update.php',
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Batch price update operation started');
                        historyTable.ajax.reload();
                    } else {
                        showAlert('danger', response.error);
                    }
                }
            });
        });
    }

    // Handle quantity adjustment form submission
    $('#quantityAdjustForm').submit(function(e) {
        e.preventDefault();
        const data = {
            type: $('#quantityItemType').val(),
            warehouse_id: $('#quantityWarehouse').val(),
            items: $('#quantityItems').val(),
            adjustment_type: $('#adjustmentType').val(),
            quantity: $('#adjustmentQuantity').val(),
            reason: $('#adjustmentReason').val()
        };

        $.ajax({
            url: 'ajax/process_quantity_adjustment.php',
            method: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Quantity adjustment operation started');
                    historyTable.ajax.reload();
                } else {
                    showAlert('danger', response.error);
                }
            }
        });
    });

    // Handle view details button click
    $('#historyTable').on('click', '.view-details', function() {
        const operationId = $(this).data('id');
        $.get(`ajax/get_operation_details.php?id=${operationId}`, function(data) {
            let detailsHtml = '<table class="table">';
            detailsHtml += '<thead><tr><th>Item</th><th>Status</th><th>Details</th></tr></thead><tbody>';
            
            data.items.forEach(item => {
                detailsHtml += `
                    <tr>
                        <td>${item.item_name}</td>
                        <td><span class="badge ${item.status === 'completed' ? 'badge-success' : 'badge-danger'}">${item.status}</span></td>
                        <td>${item.error_message || item.details}</td>
                    </tr>
                `;
            });
            
            detailsHtml += '</tbody></table>';
            $('#operationDetails').html(detailsHtml);
            $('#operationDetailsModal').modal('show');
        });
    });

    // Initialize
    loadWarehouses();
    $('#moveItemType, #priceItemType, #quantityItemType').trigger('change');

    // Auto-refresh history table every 30 seconds
    setInterval(() => {
        if ($('#history').hasClass('active')) {
            historyTable.ajax.reload(null, false);
        }
    }, 30000);

    // Helper function to show alerts
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;
        $('.container-fluid').prepend(alertHtml);
        setTimeout(() => $('.alert').alert('close'), 5000);
    }
});
