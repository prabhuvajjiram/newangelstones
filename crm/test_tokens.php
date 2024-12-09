<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['email'])) {
    die("Not logged in");
}

$stmt = $pdo->prepare("SELECT refresh_token, gmail_refresh_token, oauth_token FROM users WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$user = $stmt->fetch();

echo "<pre>";
echo "Tokens for user {$_SESSION['email']}:\n";
print_r($user);
echo "</pre>"; 