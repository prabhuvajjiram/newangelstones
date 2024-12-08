$(document).ready(function() {
    // Initialize task form submission
    $('#newTaskForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'ajax/create_task.php',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        // Close modal and refresh page
                        $('#newTaskModal').modal('hide');
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to create task. Please try again.');
                    }
                } catch (e) {
                    alert('An error occurred. Please try again.');
                }
            },
            error: function() {
                alert('Failed to create task. Please try again.');
            }
        });
    });

    // Initialize communication form submission
    $('#newCommunicationForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'ajax/log_communication.php',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                try {
                    const data = JSON.parse(response);
                    if (data.success) {
                        // Close modal and refresh page
                        $('#communicationModal').modal('hide');
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to log communication. Please try again.');
                    }
                } catch (e) {
                    alert('An error occurred. Please try again.');
                }
            },
            error: function() {
                alert('Failed to log communication. Please try again.');
            }
        });
    });

    // Set default due date to tomorrow for new tasks
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    $('input[name="due_date"]').val(tomorrow.toISOString().split('T')[0]);

    // Reset forms when modals are closed
    $('.modal').on('hidden.bs.modal', function() {
        $(this).find('form').trigger('reset');
        // Reset due date to tomorrow
        $('input[name="due_date"]').val(tomorrow.toISOString().split('T')[0]);
    });
});
