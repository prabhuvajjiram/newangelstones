document.addEventListener('DOMContentLoaded', function() {
    // Handle New Company Form Submission
    const saveCompanyBtn = document.getElementById('saveCompanyBtn');
    if (saveCompanyBtn) {
        saveCompanyBtn.addEventListener('click', function() {
            const form = document.getElementById('newCompanyForm');
            const formData = new FormData(form);
            const data = {};
            formData.forEach((value, key) => data[key] = value);

            fetch('ajax/save_company.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message and refresh page
                    showToast('Success', 'Company added successfully!', 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast('Error', data.error || 'Failed to add company', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error', 'Failed to add company', 'error');
            });
        });
    }

    // Handle Edit Company
    const editCompanyModal = document.getElementById('editCompanyModal');
    if (editCompanyModal) {
        editCompanyModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const companyId = button.getAttribute('data-company-id');
            
            // Fetch company details and populate form
            fetch(`ajax/get_company.php?id=${companyId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const company = data.company;
                        const form = editCompanyModal.querySelector('form');
                        
                        // Populate form fields
                        Object.keys(company).forEach(key => {
                            const input = form.querySelector(`[name="${key}"]`);
                            if (input) input.value = company[key];
                        });
                        
                        form.setAttribute('data-company-id', companyId);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error', 'Failed to load company details', 'error');
                });
        });
    }

    // Handle Add Contact to Company
    const addContactModal = document.getElementById('addContactModal');
    if (addContactModal) {
        addContactModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const companyId = button.getAttribute('data-company-id');
            const form = addContactModal.querySelector('form');
            form.querySelector('[name="company_id"]').value = companyId;
        });
    }
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
    
    const container = document.getElementById('toastContainer') || document.body;
    container.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remove toast after it's hidden
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}
