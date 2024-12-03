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
    </style>
</head>
<body>
    <main class="form-signin">
        <div class="card">
            <div class="card-body text-center">
                <img src="../images/logo02.png" alt="Angel Stones Logo" class="logo">
                <h1 class="h3 mb-4 fw-normal">CRM Login</h1>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <a href="<?php echo htmlspecialchars($google_login_url); ?>" class="btn-google">
                    <img src="../images/Google__G__logo.svg" alt="Google Logo">
                    Sign in with Google
                </a>
                <p class="domain-note">Use your @theangelstones.com account to sign in</p>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
