<?php
require_once 'includes/config.php';
require_once 'session_check.php';

if (!isset($_GET['id'])) {
    header('Location: companies.php');
    exit;
}

$companyId = $_GET['id'];

// Get company details
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(DISTINCT cu.id) as contact_count
    FROM companies c
    LEFT JOIN customers cu ON c.id = cu.company_id
    WHERE c.id = ?
    GROUP BY c.id
");
$stmt->execute([$companyId]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    header('Location: companies.php');
    exit;
}

// Get company contacts
$stmt = $pdo->prepare("
    SELECT * FROM customers 
    WHERE company_id = ?
    ORDER BY name ASC
");
$stmt->execute([$companyId]);
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($company['name']) ?> - Company Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container py-4">
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title mb-0">
                                <i class="bi bi-building"></i>
                                <?= htmlspecialchars($company['name']) ?>
                            </h4>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCompanyModal">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>

                        <?php if ($company['industry']): ?>
                        <p class="mb-2">
                            <i class="bi bi-briefcase"></i>
                            <?= htmlspecialchars($company['industry']) ?>
                        </p>
                        <?php endif; ?>

                        <?php if ($company['website']): ?>
                        <p class="mb-2">
                            <i class="bi bi-globe"></i>
                            <a href="<?= htmlspecialchars($company['website']) ?>" target="_blank">
                                <?= htmlspecialchars($company['website']) ?>
                            </a>
                        </p>
                        <?php endif; ?>

                        <?php if ($company['phone']): ?>
                        <p class="mb-2">
                            <i class="bi bi-telephone"></i>
                            <?= htmlspecialchars($company['phone']) ?>
                        </p>
                        <?php endif; ?>

                        <?php if ($company['address']): ?>
                        <p class="mb-2">
                            <i class="bi bi-geo-alt"></i>
                            <?= htmlspecialchars($company['address']) ?><br>
                            <?= htmlspecialchars($company['city']) ?>, <?= htmlspecialchars($company['state']) ?>
                            <?= htmlspecialchars($company['postal_code']) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Company Details</h5>
                        <div class="mb-2">
                            <small class="text-muted">Employee Count</small><br>
                            <?= htmlspecialchars($company['employee_count'] ?? 'Not specified') ?>
                        </div>
                        <?php if ($company['annual_revenue']): ?>
                        <div class="mb-2">
                            <small class="text-muted">Annual Revenue</small><br>
                            $<?= number_format($company['annual_revenue']) ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($company['notes']): ?>
                        <div class="mb-2">
                            <small class="text-muted">Notes</small><br>
                            <?= nl2br(htmlspecialchars($company['notes'])) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title mb-0">Contacts (<?= count($contacts) ?>)</h5>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addContactModal">
                                <i class="bi bi-person-plus"></i> Add Contact
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Job Title</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contacts as $contact): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($contact['name']) ?></td>
                                        <td><?= htmlspecialchars($contact['job_title'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($contact['email']) ?></td>
                                        <td><?= htmlspecialchars($contact['phone']) ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="view_customer.php?id=<?= $contact['id'] ?>" 
                                                   class="btn btn-sm btn-outline-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-primary edit-contact" 
                                                        data-id="<?= $contact['id'] ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
