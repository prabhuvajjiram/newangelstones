<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Angel Stones CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Add jQuery first -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Then add Bootstrap bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Scripts -->
    <?php
    // Base paths
    $jsBasePath = dirname($_SERVER['PHP_SELF']);
    if (basename($jsBasePath) === 'supplier') {
        $jsBasePath = dirname($jsBasePath);
    }
    ?>
    <script src="<?php echo $jsBasePath; ?>/js/tooltips.js"></script>
    <script src="<?php echo $jsBasePath; ?>/js/session-manager.js"></script>
    
    <?php if (isset($additionalScripts) && is_array($additionalScripts)): ?>
        <?php foreach ($additionalScripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <style>
        body { background-color: #f8f9fa; }
        .navbar { background-color: #2c3e50; }
        .navbar-brand, .navbar-nav .nav-link { color: white; }
        .card { box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        /* Tooltip styles */
        .tooltip {
            font-size: 0.875rem;
        }
        .tooltip-inner {
            max-width: 200px;
            padding: 0.5rem;
            background-color: #333;
        }
    </style>
    <script>
        // Set base path for AJAX calls
        const basePath = '<?php echo $jsBasePath; ?>';
        
        // Session check function
        function checkSession() {
            fetch(basePath + '/check_session.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data.valid) {
                        if (data.message === 'Session expired') {
                            // Show warning modal before redirect
                            $('#sessionWarningModal').modal('show');
                        } else {
                            window.location.href = basePath + '/login.php';
                        }
                    }
                })
                .catch(error => {
                    console.error('Session check error:', error);
                });
        }

        // Function to extend session
        function extendSession() {
            fetch(basePath + '/check_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'extend_session'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#sessionWarningModal').modal('hide');
                } else {
                    window.location.href = basePath + '/login.php';
                }
            })
            .catch(error => {
                console.error('Error extending session:', error);
                window.location.href = basePath + '/login.php';
            });
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
    <div class="container">
