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
                $_SESSION['user_role'] = $row['role']; // Store role directly
                
                // Redirect to stored URL or default page
                $redirect_to = $_SESSION['redirect_after_login'] ?? ADMIN_BASE_URL . 'quote.php';
                unset($_SESSION['redirect_after_login']); // Clear stored URL
                header('Location: ' . $redirect_to);
                exit();
            }
        }
        $error = 'Invalid username or password';
    } catch (PDOException $e) {
        $error = 'Database error occurred';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Angel Stones Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-signin {
            width: 100%;
            max-width: 400px;
            padding: 15px;
            margin: auto;
            margin-top: 100px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .btn-google {
            background-color: #fff;
            color: #757575;
            border: 1px solid #ddd;
            padding: 10px;
            width: 100%;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-google img {
            margin-right: 10px;
            width: 20px;
        }
    </style>
</head>
<body>
    <main class="form-signin">
        <div class="card">
            <div class="card-body text-center">
                <img src="../images/logo.png" alt="Angel Stones Logo" class="mb-4" style="width: 150px;">
                <h1 class="h3 mb-3 fw-normal">Admin Login</h1>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                        <label for="username">Username</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password">Password</label>
                    </div>
                    <button class="w-100 btn btn-lg btn-primary mb-3" type="submit">Sign in</button>
                </form>
                
                <a href="<?php echo htmlspecialchars($google_login_url); ?>" class="btn btn-google">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg" alt="Google Logo">
                    Sign in with Google
                </a>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
