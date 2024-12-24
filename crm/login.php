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

    <script>
        document.getElementById('toggleLogin').addEventListener('click', function() {
            const manualLogin = document.querySelector('.manual-login');
            const toggleBtn = document.getElementById('toggleLogin');
            
            if (manualLogin.style.display === 'none' || !manualLogin.style.display) {
                manualLogin.style.display = 'block';
                toggleBtn.textContent = 'Back to Google Login';
            } else {
                manualLogin.style.display = 'none';
                toggleBtn.textContent = 'Login Manually';
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
