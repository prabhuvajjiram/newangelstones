// Initialize Chart.js for email activity
const ctx = document.getElementById('emailActivityChart').getContext('2d');
const emailActivityChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [], // Will be populated with dates
        datasets: [{
            label: 'Sent Emails',
            data: [],
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
        }, {
            label: 'Received Emails',
            data: [],
            borderColor: 'rgb(255, 99, 132)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'Email Activity (Last 30 Days)'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Load email activity data
function loadEmailActivity() {
    fetch('api/email_activity.php')
        .then(response => response.json())
        .then(data => {
            emailActivityChart.data.labels = data.dates;
            emailActivityChart.data.datasets[0].data = data.sent;
            emailActivityChart.data.datasets[1].data = data.received;
            emailActivityChart.update();
        })
        .catch(error => console.error('Error loading email activity:', error));
}

// Template form handling
const templateForm = document.getElementById('templateForm');
const templateModal = new bootstrap.Modal(document.getElementById('templateModal'));

templateForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(templateForm);
    const data = Object.fromEntries(formData.entries());
    
    fetch('api/save_template.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            templateModal.hide();
            showAlert('Template saved successfully!', 'success');
            location.reload(); // Refresh to show new template
        } else {
            showAlert('Failed to save template: ' + result.error, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while saving the template', 'danger');
    });
});

// Edit template
function editTemplate(id) {
    fetch(`api/get_template.php?id=${id}`)
        .then(response => response.json())
        .then(template => {
            document.querySelector('[name="template_id"]').value = template.id;
            document.querySelector('[name="name"]').value = template.name;
            document.querySelector('[name="category"]').value = template.category;
            document.querySelector('[name="subject"]').value = template.subject;
            document.querySelector('[name="content"]').value = template.content;
            templateModal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to load template', 'danger');
        });
}

// Delete template
function deleteTemplate(id) {
    if (!confirm('Are you sure you want to delete this template?')) {
        return;
    }

    fetch(`api/delete_template.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showAlert('Template deleted successfully!', 'success');
            location.reload();
        } else {
            showAlert('Failed to delete template: ' + result.error, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while deleting the template', 'danger');
    });
}

// Disconnect email account
function disconnectEmail(id) {
    if (!confirm('Are you sure you want to disconnect this email account?')) {
        return;
    }

    fetch(`api/disconnect_email.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showAlert('Email account disconnected successfully!', 'success');
            location.reload();
        } else {
            showAlert('Failed to disconnect email account: ' + result.error, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while disconnecting the email account', 'danger');
    });
}

// Show alert message
function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Load initial data
loadEmailActivity();
