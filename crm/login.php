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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #ffffff;
            height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('../images/logo02.png') center no-repeat;
            opacity: 0.1;
            pointer-events: none;
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            padding: 2.5rem;
            max-width: 450px;
            width: 100%;
            margin: auto;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo-section img {
            height: 80px;
            margin-bottom: 1rem;
        }

        .btn-google {
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            padding: 0.8rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-google:hover {
            background: #fff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .divider {
            margin: 1.5rem 0;
            display: flex;
            align-items: center;
            text-align: center;
            color: rgba(255,255,255,0.5);
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .divider span {
            padding: 0 1rem;
        }

        .manual-login {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-floating > label {
            color: #666;
        }

        .form-control {
            background: rgba(255,255,255,0.9);
            border: none;
        }

        .form-control:focus {
            background: #fff;
            box-shadow: 0 0 0 3px rgba(255,255,255,0.1);
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="container">
            <div class="login-container">
                <div class="logo-section">
                    <img src="../images/logo02.png" alt="Angel Stones Logo">
                    <h4 class="mb-0">Welcome to Angel Stones CRM</h4>
                    <p class="text-muted">Sign in to continue to your account</p>
                </div>

                <a href="<?php echo $google_login_url; ?>" class="btn btn-google w-100">
                    <img src="../images/Google__G__logo.svg" alt="Google" height="24">
                    <span>Sign in with Google</span>
                </a>

                <div class="divider">
                    <span>or</span>
                </div>

                <button type="button" class="btn btn-outline-light w-100" id="toggleLogin">
                    <i class="bi bi-key-fill me-2"></i>Login Manually
                </button>

                <form class="manual-login mt-4" action="process_login.php" method="POST">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="username" name="username" required>
                        <label for="username">Username</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <label for="password">Password</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </button>
                </form>
            </div>
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
