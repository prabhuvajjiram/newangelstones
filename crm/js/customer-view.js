$(document).ready(function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Handle lifecycle stage changes
    $('#lifecycleStage').change(function() {
        const stageId = $(this).val();
        const customerId = $(this).data('customer-id');
        
        $.post('ajax/update_lifecycle_stage.php', {
            customer_id: customerId,
            stage_id: stageId
        })
        .done(function(response) {
            if (response.success) {
                showToast('Success', 'Lifecycle stage updated successfully');
            } else {
                showToast('Error', 'Failed to update lifecycle stage', 'error');
            }
        })
        .fail(function() {
            showToast('Error', 'Failed to update lifecycle stage', 'error');
        });
    });

    // Handle task status updates
    $(document).on('click', '.task-status-update', function(e) {
        e.preventDefault();
        const taskId = $(this).data('task-id');
        const status = $(this).data('status');
        const taskCard = $(this).closest('.task-card');
        
        $.post('ajax/update_task_status.php', {
            task_id: taskId,
            status: status
        })
        .done(function(response) {
            if (response.success) {
                if (status === 'completed') {
                    taskCard.fadeOut();
                }
                showToast('Success', 'Task status updated');
            } else {
                showToast('Error', 'Failed to update task status', 'error');
            }
        })
        .fail(function() {
            showToast('Error', 'Failed to update task status', 'error');
        });
    });

    // Handle activity timeline filters
    $('.activity-filter').click(function(e) {
        e.preventDefault();
        const type = $(this).data('type');
        
        $('.activity-item').each(function() {
            if (type === 'all' || $(this).data('type') === type) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        
        // Update active filter
        $('.activity-filter').removeClass('active');
        $(this).addClass('active');
    });

    // Handle communication card click
    $('a[data-filter="communication"]').click(function(e) {
        e.preventDefault();
        const targetId = $(this).attr('href');
        
        // Scroll to activity timeline
        $('html, body').animate({
            scrollTop: $(targetId).offset().top - 100
        }, 500);
        
        // Filter for communications
        $('.activity-item').hide();
        $('.activity-item[data-type="communication"]').fadeIn();
        
        // Update filter button state
        $('.filter-btn').removeClass('active');
        $('.filter-btn[data-type="communication"]').addClass('active');
    });

    // Toast notification system
    function showToast(title, message, type = 'success') {
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${title}</strong><br>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        
        const toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            $('body').append('<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
        }
        
        const toast = $(toastHtml);
        $('#toast-container').append(toast);
        
        const bsToast = new bootstrap.Toast(toast[0], {
            autohide: true,
            delay: 3000
        });
        bsToast.show();
        
        // Remove toast after it's hidden
        toast.on('hidden.bs.toast', function() {
            $(this).remove();
        });
    }

    // Handle form submissions
    function handleFormSubmit(formId, url, successMessage) {
        $(formId).submit(function(e) {
            e.preventDefault();
            const form = $(this);
            const modal = form.closest('.modal');
            
            $.ajax({
                url: url,
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        showToast('Success', successMessage);
                        modal.modal('hide');
                        // Reload relevant section or entire page
                        location.reload();
                    } else {
                        showToast('Error', response.message || 'An error occurred', 'error');
                    }
                },
                error: function() {
                    showToast('Error', 'Failed to process request', 'error');
                }
            });
        });
    }

    // Initialize form handlers
    handleFormSubmit('#newTaskForm', 'ajax/save_task.php', 'Task created successfully');
    handleFormSubmit('#communicationForm', 'ajax/save_communication.php', 'Communication logged successfully');
    handleFormSubmit('#uploadDocumentForm', 'ajax/save_document.php', 'Document uploaded successfully');
    handleFormSubmit('#editCustomerForm', 'ajax/update_customer.php', 'Customer information updated successfully');
});
