<?php
require_once 'includes/config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {

            $ip = $_SERVER['REMOTE_ADDR'];
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
    
            // Get location data
            $locationData = @file_get_contents("https://ipapi.co/{$ip}/json/");
            $location = '';
            $loginData = null;
            if ($locationData) {
                $locationInfo = json_decode($locationData);
                if ($locationInfo && !isset($locationInfo->error)) {
                    //$location = "{$locationInfo->city}, {$locationInfo->region}, {$locationInfo->country_name}";
                    $loginData = $locationData; // Store raw JSON
                    error_log("Location data: " . $loginData);
                }
            }
    
            // Update last_login, location and full JSON data
            $updateStmt = $pdo->prepare("
                UPDATE users 
                SET last_login = NOW(), 
                    last_login_data = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$loginData, $user['id']]);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            header('Location: index.php');
            exit;
        } else {
            header('Location: login.php?error=invalid');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        header('Location: login.php?error=system');
        exit;
    }
}