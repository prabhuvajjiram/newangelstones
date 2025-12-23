<?php
// Set session cookie to expire when browser closes
ini_set('session.cookie_lifetime', 0);
session_start();
require_once '../crm/includes/config.php';

header('Content-Type: application/json');

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Username and password required']);
        exit;
    }
    
    try {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Update last login
            $updateStmt = $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            // Set session
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'username' => $user['username'],
                    'email' => $user['email']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
        }
    } catch (PDOException $e) {
        error_log('Login error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    exit;
}

// Check login status
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'username' => $_SESSION['admin_username'] ?? ''
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'logged_in' => false
        ]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request method']);
?>
