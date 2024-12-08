<?php
require_once 'includes/config.php';
require_once 'session_check.php';
require_once 'includes/ContactManager.php';

// Initialize ContactManager
$contactManager = new ContactManager($pdo);

// Get all companies
$stmt = $pdo->query("
    SELECT c.*, 
           COUNT(DISTINCT cu.id) as contact_count,
           SUM(CASE WHEN cu.lead_score >= 70 THEN 1 ELSE 0 END) as hot_leads_count
    FROM companies c
    LEFT JOIN customers cu ON c.id = cu.company_id
    GROUP BY c.id
    ORDER BY c.name ASC
");
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function to safely handle null values
function safe_html($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Companies - Angel Stones CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .company-card {
            transition: transform 0.2s;
        }
        .company-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stats-badge {
            font-size: 0.8rem;
            padding: 0.3rem 0.5rem;
        }
        .modal-backdrop {
            z-index: 1040;
        }
        .modal {
            z-index: 1050;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Companies</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newCompanyModal">
                <i class="bi bi-building-add"></i> Add Company
            </button>
        </div>

        <!-- Companies List -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($companies as $company): ?>
            <div class="col">
                <div class="card h-100 company-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-building"></i>
                                <?= safe_html($company['name']) ?>
                            </h5>
                            <div class="btn-group">
                                <a href="view_company.php?id=<?= $company['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <button class="btn btn-sm btn-outline-secondary edit-company" data-id="<?= $company['id'] ?>">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <?php if (!empty($company['industry'])): ?>
                            <span class="badge bg-info stats-badge">
                                <i class="bi bi-briefcase"></i> <?= safe_html($company['industry']) ?>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($company['contact_count'])): ?>
                            <span class="badge bg-success stats-badge">
                                <i class="bi bi-people"></i> <?= $company['contact_count'] ?> Contacts
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($company['hot_leads_count'])): ?>
                            <span class="badge bg-danger stats-badge">
                                <i class="bi bi-fire"></i> <?= $company['hot_leads_count'] ?> Hot Leads
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="small text-muted">
                            <?php if (!empty($company['website'])): ?>
                            <div><i class="bi bi-globe"></i> <?= safe_html($company['website']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($company['phone'])): ?>
                            <div><i class="bi bi-telephone"></i> <?= safe_html($company['phone']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- New Company Modal -->
    <div class="modal fade" id="newCompanyModal" tabindex="-1" aria-labelledby="newCompanyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newCompanyModalLabel">Add New Company</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="newCompanyForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company Name *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Industry</label>
                                <select class="form-select" name="industry">
                                    <option value="">Select Industry</option>
                                    <option value="Monument Dealer">Monument Dealer</option>
                                    <option value="Supplier">Supplier</option>
                                    <option value="Cemetery">Cemetery</option>
                                    <option value="Individual">Individual</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Website</label>
                                <input type="url" class="form-control" name="website">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Employee Count</label>
                                <select class="form-select" name="employee_count">
                                    <option value="">Select Range</option>
                                    <option value="1-10">1-10</option>
                                    <option value="11-50">11-50</option>
                                    <option value="51-200">51-200</option>
                                    <option value="201-500">201-500</option>
                                    <option value="501+">501+</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Annual Revenue</label>
                                <input type="number" class="form-control" name="annual_revenue" step="0.01">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="city">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">State</label>
                                <input type="text" class="form-control" name="state">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveCompanyBtn">Save Company</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Initialize Bootstrap components
        var newCompanyModal = new bootstrap.Modal(document.getElementById('newCompanyModal'), {
            backdrop: true,
            keyboard: true
        });

        // Save new company
        $('#saveCompanyBtn').click(function() {
            // Get form data
            const formData = {};
            $('#newCompanyForm').serializeArray().forEach(item => {
                // Only include non-empty values
                if (item.value !== '') {
                    formData[item.name] = item.value;
                }
            });

            // Log the data being sent
            console.log('Sending data:', formData);

            $.ajax({
                url: 'ajax/save_company.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(response) {
                    console.log('Response:', response);
                    if (response.success) {
                        // Show success message
                        alert('Company saved successfully!');
                        // Close the modal
                        newCompanyModal.hide();
                        // Reload the page to show the new company
                        window.location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error details:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    
                    let errorMessage = 'Error saving company';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMessage = response.message || errorMessage;
                    } catch (e) {
                        console.error('Error parsing response:', e);
                    }
                    
                    alert(errorMessage + ': ' + error);
                }
            });
        });

        // Edit company button click handler
        $('.edit-company').click(function() {
            const companyId = $(this).data('id');
            
            // Load company data
            $.ajax({
                url: 'ajax/get_company.php',
                method: 'GET',
                data: { id: companyId },
                success: function(data) {
                    // Populate the edit modal with company data
                    $('#editCompanyId').val(data.id);
                    $('#editCompanyName').val(data.name);
                    $('#editIndustry').val(data.industry);
                    $('#editWebsite').val(data.website);
                    $('#editEmail').val(data.email);
                    $('#editPhone').val(data.phone);
                    $('#editAddress').val(data.address);
                    $('#editCity').val(data.city);
                    $('#editState').val(data.state);
                    $('#editPostalCode').val(data.postal_code);
                    $('#editCountry').val(data.country);
                    $('#editNotes').val(data.notes);
                    
                    // Show the edit modal
                    $('#editCompanyModal').modal('show');
                },
                error: function(xhr, status, error) {
                    console.error('Error loading company:', error);
                    alert('Error loading company data: ' + error);
                }
            });
        });
    });
    </script>
</body>
</html>
