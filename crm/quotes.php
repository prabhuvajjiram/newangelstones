<?php
require_once 'session_check.php';
requireLogin();
require_once 'includes/config.php';

try {
    // Enable error logging
    error_log("Fetching quotes for user: " . $_SESSION['email'] . " with role: " . $_SESSION['role']);

    // Base query with all necessary joins
    $baseQuery = "
        SELECT 
            q.*,
            c.name as customer_name,
            c.email as customer_email,
            COUNT(qi.id) as item_count,
            COALESCE(SUM(qi.total_price), 0) as total_amount,
            u.first_name as created_by_first_name,
            u.last_name as created_by_last_name,
            DATE_FORMAT(q.created_at, '%Y-%m-%d') as formatted_date
        FROM quotes q
        LEFT JOIN customers c ON q.customer_id = c.id
        LEFT JOIN quote_items qi ON q.id = qi.quote_id
        LEFT JOIN users u ON q.username = u.email
        WHERE 1=1
    ";

    // Staff can only see their own quotes
    if (!isAdmin()) {
        $baseQuery .= " AND q.username = :username";
        error_log("Adding staff restriction for username: " . $_SESSION['email']);
    }

    // Complete the query with GROUP BY and ORDER BY
    $baseQuery .= " GROUP BY q.id ORDER BY q.created_at DESC";

    $stmt = $pdo->prepare($baseQuery);
    
    // Bind parameters if not admin
    if (!isAdmin()) {
        $stmt->bindParam(':username', $_SESSION['email'], PDO::PARAM_STR);
    }

    $stmt->execute();
    $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Successfully fetched " . count($quotes) . " quotes");
} catch (PDOException $e) {
    error_log("Error fetching quotes: " . $e->getMessage());
    $_SESSION['error'] = "There was an error fetching the quotes. Please try again later.";
    $quotes = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Quotes - Angel Stones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>All Quotes</h1>
            <a href="quote.php" class="btn btn-primary">
                <i class="bi bi-plus"></i> New Quote
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Quote #</th>
                                <th>Customer</th>
                                <th>Project</th>
                                <th>Items</th>
                                <th>Total Amount</th>
                                <th>Created</th>
                                <?php if (isAdmin()): ?>
                                <th>Created By</th>
                                <?php endif; ?>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($quotes)): ?>
                                <tr>
                                    <td colspan="<?php echo isAdmin() ? '9' : '8'; ?>" class="text-center">No quotes found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($quotes as $quote): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($quote['id']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($quote['customer_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($quote['customer_email']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($quote['project_name'] ?? 'N/A'); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo $quote['item_count']; ?></span></td>
                                        <td>$<?php echo number_format($quote['total_amount'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($quote['formatted_date'])); ?></td>
                                        <?php if (isAdmin()): ?>
                                        <td><?php echo htmlspecialchars($quote['created_by_first_name'] . ' ' . $quote['created_by_last_name']); ?></td>
                                        <?php endif; ?>
                                        <td>
                                            <span class="badge bg-<?php echo $quote['status'] === 'pending' ? 'warning' : ($quote['status'] === 'approved' ? 'success' : 'danger'); ?>">
                                                <?php echo ucfirst($quote['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="preview_quote.php?id=<?php echo $quote['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="generate_pdf.php?id=<?php echo $quote['id']; ?>" class="btn btn-sm btn-success" target="_blank">
                                                <i class="bi bi-file-pdf"></i>
                                            </a>
                                            <button onclick="sendQuoteEmail(<?php echo $quote['id']; ?>, event)" class="btn btn-sm btn-info">
                                                <i class="bi bi-envelope"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
   function sendQuoteEmail(quoteId, event) {
    if (!quoteId) {
        console.error('No quote ID provided');
        alert('Error: Invalid quote ID');
        return;
    }

    console.log('Sending quote ID:', quoteId);

    if (!confirm('Are you sure you want to send this quote via email?')) {
        return;
    }

    // Show loading state
    const button = event.target.closest('button');
    const originalContent = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';

    fetch('api/send_quote.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ 
            quote_id: parseInt(quoteId, 10) 
        })
    })
    .then(async response => {
        const text = await response.text();
        console.log('Raw response:', text);
        
        try {
            const data = JSON.parse(text);
            console.log('Parsed response:', data);
            
            if (response.status === 401 || data.needsAuth) {
                console.log('Gmail auth required, redirecting...');
                // Redirect to gmail_auth.php with quote_id and return URL
                const returnUrl = encodeURIComponent(window.location.href);
                window.location.href = `gmail_auth.php?quote_id=${quoteId}&return=${returnUrl}`;
                return null;
            }
            
            if (!response.ok) {
                throw new Error(data.message || 'Server error');
            }
            
            return data;
        } catch (e) {
            console.error('JSON Parse Error:', e);
            console.error('Response text:', text);
            throw new Error('Error processing response: ' + e.message);
        }
    })
    .then(data => {
        if (data === null) return; // Auth redirect in progress
        
        if (data.success) {
            alert('Quote sent successfully!');
        } else {
            throw new Error(data.message || 'Unknown error occurred');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error sending quote: ' + error.message);
    })
    .finally(() => {
        if (!document.location.href.includes('gmail_auth.php')) {
            button.disabled = false;
            button.innerHTML = originalContent;
        }
    });
}

// Check for resend parameter on page load
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const resendQuoteId = urlParams.get('resend');
    if (resendQuoteId) {
        // Remove the parameter from URL
        urlParams.delete('resend');
        const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        history.replaceState({}, '', newUrl);
        // Try sending again
        sendQuoteEmail(parseInt(resendQuoteId, 10));
    }
});
    </script>
</body>
</html>
