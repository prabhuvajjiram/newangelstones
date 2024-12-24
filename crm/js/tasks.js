$(document).ready(function() {
     // Initialize daterangepicker
     $('#dateRange').daterangepicker({
        opens: 'left',
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Clear',
            format: 'YYYY-MM-DD'
        }
    });

    // Handle date range selection
    $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
    });

    $('#dateRange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });

    // Handle task status changes
    $('.task-status').change(function() {
        const taskId = $(this).data('task-id');
        const newStatus = $(this).val();

        $.ajax({
            url: 'ajax/update_task_status.php',
            method: 'POST',
            data: {
                task_id: taskId,
                status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Task status updated successfully');
                } else {
                    toastr.error('Failed to update task status');
                    // Revert the select to previous value
                    $(this).val($(this).find('option[selected]').val());
                }
            },
            error: function() {
                toastr.error('An error occurred while updating task status');
                // Revert the select to previous value
                $(this).val($(this).find('option[selected]').val());
            }
        });
    });

    // Handle task deletion
    $('.delete-task').click(function() {
        const taskId = $(this).data('task-id');
        
        if (confirm('Are you sure you want to delete this task?')) {
            $.ajax({
                url: 'ajax/delete_task.php',
                method: 'POST',
                data: { task_id: taskId },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Task deleted successfully');
                        // Remove the task row from the table
                        $(this).closest('tr').fadeOut(400, function() {
                            $(this).remove();
                        });
                    } else {
                        toastr.error('Failed to delete task');
                    }
                },
                error: function() {
                    toastr.error('An error occurred while deleting the task');
                }
            });
        }
    });

    // Handle task filters
    $('#taskFilters').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.ajax({
            url: 'ajax/filter_tasks.php',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Update both task tables with filtered data
                    $('#myTasksTable tbody').html(response.myTasks);
                    $('#assignedTasksTable tbody').html(response.assignedTasks);
                } else {
                    toastr.error('Failed to filter tasks');
                }
            },
            error: function() {
                toastr.error('An error occurred while filtering tasks');
            }
        });
    });

    // Handle task creation
    $('#newTaskForm').submit(function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.ajax({
            url: 'ajax/create_task.php',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success('Task created successfully');
                    $('#newTaskModal').modal('hide');
                    // Refresh the page to show new task
                    location.reload();
                } else {
                    toastr.error(response.message || 'Failed to create task');
                }
            },
            error: function() {
                toastr.error('An error occurred while creating the task');
            }
        });
    });

    // Handle task view
    $('.view-task').click(function() {
        const taskId = $(this).data('task-id');
        
        $.ajax({
            url: 'ajax/get_task_details.php',
            method: 'GET',
            data: { task_id: taskId },
            success: function(response) {
                if (response.success) {
                    // Populate the view task modal with task details
                    $('#viewTaskTitle').text(response.task.title);
                    $('#viewTaskDescription').text(response.task.description);
                    $('#viewTaskPriority').text(response.task.priority);
                    $('#viewTaskStatus').text(response.task.status);
                    $('#viewTaskDueDate').text(response.task.due_date);
                    if (response.task.customer_name) {
                        $('#viewTaskCustomer').text(response.task.customer_name);
                        $('#viewTaskCustomer').closest('.row').show();
                    } else {
                        $('#viewTaskCustomer').closest('.row').hide();
                    }
                    $('#viewTaskModal').modal('show');
                } else {
                    toastr.error('Failed to load task details');
                }
            },
            error: function() {
                toastr.error('An error occurred while loading task details');
            }
        });
    });
});
