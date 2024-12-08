document.addEventListener('DOMContentLoaded', function() {
    // Handle lifecycle stage changes
    const lifecycleSelect = document.getElementById('lifecycleStage');
    if (lifecycleSelect) {
        lifecycleSelect.addEventListener('change', function() {
            const customerId = this.dataset.customerId;
            const stageId = this.value;
            
            fetch('ajax/update_lifecycle_stage.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    customer_id: customerId,
                    stage_id: stageId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showToast('Success', 'Lifecycle stage updated successfully', 'success');
                } else {
                    showToast('Error', data.error || 'Failed to update lifecycle stage', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error', 'Failed to update lifecycle stage', 'error');
            });
        });
    }

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Handle custom field updates
    document.querySelectorAll('.custom-field-edit').forEach(button => {
        button.addEventListener('click', function() {
            const fieldId = this.dataset.fieldId;
            const fieldType = this.dataset.fieldType;
            const currentValue = this.dataset.currentValue;
            
            // Show edit modal with appropriate input type
            const modal = new bootstrap.Modal(document.getElementById('editFieldModal'));
            const input = document.getElementById('fieldValue');
            input.value = currentValue;
            
            if (fieldType === 'date') {
                input.type = 'date';
            } else if (fieldType === 'number') {
                input.type = 'number';
            } else {
                input.type = 'text';
            }
            
            modal.show();
        });
    });
});

// Helper function to show toast notifications
function showToast(title, message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <strong>${title}</strong><br>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    document.getElementById('toastContainer').appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remove toast after it's hidden
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}
