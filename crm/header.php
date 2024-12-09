<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Angel Stones CRM</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    
    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>

    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 60px;
            --sidebar-bg: #2c3e50;
        }

        body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        /* Layout */
        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: #f8f9fa;
            padding: 20px;
        }

        /* Sidebar Navigation */
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.8rem 1rem;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,0.1);
        }

        .sidebar .nav-link.active {
            color: #fff;
            background: #0d6efd;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
            }
            
            .sidebar.show {
                margin-left: 0;
            }

            .main-content {
                margin-left: 0;
            }
        }

        /* Card Styles */
        .card {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: none;
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'navbar.php'; ?>
        <div class="main-content">
            <!-- Main content will be here -->
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
        </div>
    </div>
</body>
</html>
