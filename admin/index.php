<?php
require_once 'includes/config.php';
requireLogin();

// Fetch customers for the table
$customers = [];
$result = $conn->query("SELECT * FROM customers ORDER BY id DESC");
while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}

// Get quote count for each customer
foreach ($customers as &$customer) {
    $stmt = $conn->prepare("SELECT COUNT(*) as quote_count FROM quotes WHERE customer_id = ?");
    $stmt->bind_param("i", $customer['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc();
    $customer['quote_count'] = $count['quote_count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Angel Stones - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .action-buttons .btn {
            margin-right: 5px;
        }
        .table-responsive {
            margin-top: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h2>Dashboard</h2>
            </div>
            <div class="col-auto">
                <a href="customers.php" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i> Manage Customers
                </a>
                <a href="settings.php" class="btn btn-secondary">
                    <i class="bi bi-gear"></i> Settings
                </a>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Customers</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Quotes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                <td><?php echo $customer['quote_count']; ?></td>
                                <td class="action-buttons">
                                    <a href="quote.php?customer_id=<?php echo $customer['id']; ?>" class="btn btn-success btn-sm">
                                        <i class="bi bi-file-earmark-plus"></i> New Quote
                                    </a>
                                    <a href="view_quotes.php?customer_id=<?php echo $customer['id']; ?>" class="btn btn-info btn-sm">
                                        <i class="bi bi-files"></i> View Quotes
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
