<?php
session_start();
require_once 'includes/config.php';

// Temporary debug code - remove after testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug session status
error_log("Session status: " . session_status());
error_log("Session ID: " . session_id());

try {
    // Test database connection
    $test = $pdo->query("SELECT 1");
    error_log("Database connection successful");
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection error: " . $e->getMessage());
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        // Debug log
        error_log("Login attempt for username: " . $username);
        
        // Prepare SQL statement to prevent SQL injection
        $stmt = $pdo->prepare("SELECT id, password, role FROM users WHERE username = ?");
        if (!$stmt) {
            error_log("Failed to prepare statement");
            throw new PDOException("Failed to prepare statement");
        }
        
        $stmt->execute([$username]);
        error_log("Query executed, found rows: " . $stmt->rowCount());
        
        if ($stmt->rowCount() === 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("User found, stored password hash: " . $row['password']);
            
            if (password_verify($password, $row['password'])) {
                error_log("Password verified successfully for user: " . $username);
                // Set session variables
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $username;
                $_SESSION['user_role'] = $row['role'];
                
                error_log("Session variables set - user_id: " . $_SESSION['user_id'] . ", username: " . $_SESSION['username'] . ", role: " . $_SESSION['user_role']);
                
                // Make sure headers haven't been sent yet
                if (!headers_sent($filename, $linenum)) {
                    error_log("Redirecting to quote.php");
                    header("Location: quote.php");
                    exit();
                } else {
                    error_log("Headers already sent in $filename on line $linenum");
                    echo "<script>window.location.href = 'quote.php';</script>";
                    exit();
                }
            } else {
                error_log("Password verification failed for user: " . $username);
                $error = "Invalid password";  
            }
        } else {
            error_log("No user found with username: " . $username);
            $error = "Username not found";  
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "Database error: " . $e->getMessage();  
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Angel Stones</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
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
        .form-signin .form-floating {
            margin-bottom: 1rem;
        }
        .brand-logo {
            width: 150px;
            margin-bottom: 1.5rem;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            padding: 0.75rem;
            font-size: 1rem;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
        .alert {
            border-radius: 0.5rem;
        }
    </style>
</head>
<body>
    <main class="form-signin">
        <div class="card">
            <div class="card-body text-center">
                <img src="../images/logo.png" alt="Angel Stones Logo" class="brand-logo">
                <h1 class="h3 mb-4 fw-normal">Admin Login</h1>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username" required value="<?php echo htmlspecialchars($username ?? ''); ?>">
                        <label for="username">Username</label>
                    </div>
                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password">Password</label>
                    </div>
                    <div class="form-check text-start mb-3">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label" for="remember">
                            Remember me
                        </label>
                    </div>
                    <button class="w-100 btn btn-lg btn-primary mb-3" type="submit">
                        <i class="fas fa-sign-in-alt me-2"></i>Sign in
                    </button>
                    <div class="text-muted">
                        <small>Protected by Angel Stones Security</small>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
