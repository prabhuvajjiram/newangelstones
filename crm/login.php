<?php
require_once 'includes/auth_config.php';

// Generate Google OAuth URL with domain restriction
$oauth_params = array(
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'email profile',
    'access_type' => 'offline',
    'hd' => 'theangelstones.com',
    'prompt' => 'select_account consent'
);

$google_login_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($oauth_params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Login - Angel Stones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1a1a1a;
            color: #ffffff;
            height: 100vh;
            display: flex;
            align-items: center;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        .form-signin {
            width: 100%;
            max-width: 330px;
            padding: 15px;
            margin: auto;
        }
        .card {
            background-color: #2a2a2a;
            border: 1px solid #3a3a3a;
            border-radius: 10px;
            padding: 2rem;
        }
        .logo {
            width: 200px;
            height: auto;
            margin-bottom: 1.5rem;
            filter: brightness(0) invert(1);
        }
        .btn-google {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background-color: #ffffff;
            color: #757575;
            border-radius: 5px;
            padding: 0.5rem 1rem;
            text-decoration: none;
            transition: background-color 0.3s;
            border: 1px solid #dadce0;
            width: 100%;
            margin-top: 1rem;
        }
        .btn-google:hover {
            background-color: #f5f5f5;
            color: #555555;
        }
        .btn-google img {
            width: 24px;
            height: 24px;
        }
        .domain-note {
            color: #888;
            font-size: 0.9rem;
            margin-top: 1rem;
            text-align: center;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            margin: auto;
            padding: 2rem;
        }
        .manual-login {
            display: none;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="text-center mb-4">
                <img src="../images/logo02.png" alt="Angel Stones Logo" height="72">
                <h1 class="h3 mb-3">Angel Stones CRM</h1>
            </div>

            <!-- Primary Google Login -->
            <a href="<?php echo $google_login_url; ?>" class="btn btn-google">
                <img src="../images/Google__G__logo.svg" alt="Google" class="me-2" height="24">
                Sign in with Google
            </a>

            <!-- Toggle Button -->
            <div class="text-center">
                <button type="button" class="btn btn-link text-light" id="toggleLogin">
                    Login Manually
                </button>
            </div>

            <!-- Hidden Manual Login Form -->
            <form class="manual-login" action="process_login.php" method="POST">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" required>
                    <label for="username">Username</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" required>
                    <label for="password">Password</label>
                </div>
                <button type="submit" class="btn btn-primary w-100">Sign In</button>
            </form>
        </div>
    </div>

    <!-- Add Modal after login container -->
    <div class="modal fade" id="manualLoginWarning" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Manual Login Notice
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Please note that manual login is not recommended. Some CRM features may be limited or unavailable without SSO authentication.</p>
                    <p class="mb-0">For full functionality, we recommend:</p>
                    <ul>
                        <li>Using an @theangelstones.com email address</li>
                        <li>Signing in with Google SSO</li>
                    </ul>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="proceedManualLogin">Proceed Anyway</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('toggleLogin').addEventListener('click', function(e) {
            e.preventDefault();
            const modal = new bootstrap.Modal(document.getElementById('manualLoginWarning'));
            modal.show();
        });

        document.getElementById('proceedManualLogin').addEventListener('click', function() {
            const manualLogin = document.querySelector('.manual-login');
            const toggleBtn = document.getElementById('toggleLogin');
            
            manualLogin.style.display = 'block';
            toggleBtn.textContent = 'Back to Google Login';
            bootstrap.Modal.getInstance(document.getElementById('manualLoginWarning')).hide();
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
