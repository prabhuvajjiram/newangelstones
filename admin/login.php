<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth_config.php';

// Define admin base URL
$server_name = $_SERVER['SERVER_NAME'];
if ($server_name === 'www.theangelstones.com' || $server_name === 'theangelstones.com') {
    define('ADMIN_BASE_URL', '/admin/');
} else {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $port = $_SERVER['SERVER_PORT'];
    $port_suffix = ($port != '80' && $port != '443') ? ":$port" : '';
    define('ADMIN_BASE_URL', $protocol . $server_name . $port_suffix . '/admin/');
}

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ADMIN_BASE_URL . 'quote.php');
    exit();
}

// Generate Google OAuth URL
$google_auth_url = 'https://accounts.google.com/o/oauth2/v2/auth';
$params = array(
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'email profile',
    'access_type' => 'online',
    'prompt' => 'select_account'
);
$google_login_url = $google_auth_url . '?' . http_build_query($params);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT id, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() === 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $username;
                $_SESSION['roles'] = [$row['role']]; // Store role in roles array
                
                // Use ADMIN_BASE_URL for proper redirection
                $redirect_to = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : ADMIN_BASE_URL . 'quote.php';
                unset($_SESSION['redirect_after_login']); // Clear the stored URL
                
                header("Location: " . $redirect_to);
                exit();
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "Username not found";
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "Database error occurred";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Angel Stones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        .form-signin {
            width: 100%;
            max-width: 400px;
            padding: 15px;
            margin: auto;
        }
        .form-signin .card {
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .form-signin .card-body {
            padding: 2rem;
        }
        .google-btn {
            width: 100%;
            background: #fff;
            color: #757575;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .google-btn:hover {
            background: #f1f1f1;
            text-decoration: none;
            color: #757575;
        }
        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }
        .divider::before,
        .divider::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background: #ddd;
        }
        .divider::before { left: 0; }
        .divider::after { right: 0; }
    </style>
</head>
<body>
    <main class="form-signin">
        <div class="card">
            <div class="card-body text-center">
                <img src="../images/logo.png" alt="Angel Stones Logo" class="mb-4" style="width: 150px;">
                <h1 class="h3 mb-4 fw-normal">Admin Login</h1>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <!-- Google Login Button -->
                <a href="<?php echo htmlspecialchars($google_login_url); ?>" class="google-btn">
                    <img src="https://www.google.com/favicon.ico" alt="Google" width="20" height="20">
                    Sign in with Google
                </a>

                <div class="divider">OR</div>

                <form method="POST" action="">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                        <label for="username">Username</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password">Password</label>
                    </div>
                    <button class="w-100 btn btn-lg btn-primary" type="submit">Sign in</button>
                </form>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
