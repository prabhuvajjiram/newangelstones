<?php
session_start();
require_once 'includes/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Debug statements
    error_log("Login attempt - Username: " . $username);
    error_log("Database connection status: " . ($conn->connect_errno ? "Failed" : "Success"));

    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        $error = "Database error occurred";
    } else {
        $stmt->bind_param("s", $username);
        
        // Debug execution
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            $error = "Database error occurred";
        } else {
            $result = $stmt->get_result();
            error_log("Number of rows found: " . $result->num_rows);
            
            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                error_log("Stored hash: " . $row['password']);
                error_log("Attempting to verify password...");
                
                if (password_verify($password, $row['password'])) {
                    error_log("Password verified successfully!");
                    // Set session variables
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $username;
                    $_SESSION['user_role'] = $row['role'];
                    
                    header("Location: quote.php");
                    exit();
                } else {
                    error_log("Password verification failed for user: " . $username);
                    $error = "Invalid username or password";
                }
            } else {
                error_log("No user found with username: " . $username);
                $error = "Invalid username or password";
            }
        }
        $stmt->close();
    }
}

// Debug: Check if database has the admin user
$debug_query = "SELECT * FROM users WHERE username = 'admin'";
$debug_result = $conn->query($debug_query);
error_log("Debug - Number of admin users in database: " . ($debug_result ? $debug_result->num_rows : "query failed"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Angel Stones - Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Angel Stones</h2>
                        <h4 class="text-center mb-4">Admin Login</h4>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
