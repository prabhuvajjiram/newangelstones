<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Angel Stones CRM</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
<!-- Add jQuery first -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Then add Bootstrap bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body { background-color: #f8f9fa; }
        .navbar { background-color: #2c3e50; }
        .navbar-brand, .navbar-nav .nav-link { color: white; }
        .card { box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    </style>
    <script>
        // Session check function
        function checkSession() {
            fetch('check_session.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.valid) {
                        window.location.href = 'login.php';
                    }
                })
                .catch(error => console.error('Session check error:', error));
        }

        // Check session every minute
        setInterval(checkSession, 60000);

        // Also check when user becomes active after being idle
        let activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'];
        let activityTimeout;

        function resetActivityTimer() {
            clearTimeout(activityTimeout);
            activityTimeout = setTimeout(checkSession, 30 * 60 * 1000); // 30 minutes
        }

        activityEvents.forEach(event => {
            document.addEventListener(event, resetActivityTimer);
        });

        // Initial setup
        resetActivityTimer();
    </script>
    <script src="js/session-manager.js"></script>
</head>
<body>
    <!-- Session Warning Modal -->
    <div class="modal fade" id="sessionWarningModal" tabindex="-1" aria-labelledby="sessionWarningModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sessionWarningModalLabel">Session Timeout Warning</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Your session is about to expire. Would you like to continue?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Logout</button>
                    <button type="button" class="btn btn-primary" onclick="extendSession()">Continue Session</button>
                </div>
            </div>
        </div>
    </div>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Angel Stones CRM</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="customers.php">Customers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="quotes.php">Quotes</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container">
