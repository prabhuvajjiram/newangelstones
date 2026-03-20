<?php
// contact-submit.php

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

// ------------------------
// 1. Basic honeypot check
// ------------------------
if (!empty($_POST['mauticform']['email2'] ?? '')) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Spam detected']));
}

// ------------------------
// 2. Turnstile token check
// ------------------------
$turnstileToken = $_POST['cf-turnstile-response'] ?? '';

if (!$turnstileToken) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Please complete the security check.']));
}

// ------------------------
// 3. Secret key
// IMPORTANT: replace with your real Cloudflare secret key
// Do NOT put this in HTML or JS
// ------------------------
$config = require '/home/theangel/turnstile-config.php';
$turnstileSecret = $config['turnstile_secret'] ?? '';

if (!$turnstileSecret) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'Server configuration error. Please contact us directly.']));
}

// ------------------------
// 4. Validate with Cloudflare
// ------------------------
$postData = http_build_query([
    'secret'   => $turnstileSecret,
    'response' => $turnstileToken,
    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
]);

$ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

$response = curl_exec($ch);

if ($response === false) {
    curl_close($ch);
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'Security verification error. Please try again.']));
}

curl_close($ch);

$result = json_decode($response, true);

if (empty($result['success'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Security check failed. Please refresh and try again.']));
}

// ------------------------
// 5. Optional quick spam keyword filter
// ------------------------
$name    = strtolower($_POST['mauticform']['f_name'] ?? '');
$email   = strtolower($_POST['mauticform']['email'] ?? '');
$phone   = strtolower($_POST['mauticform']['phone'] ?? '');
$message = strtolower($_POST['mauticform']['f_message'] ?? '');

$combined = $name . ' ' . $email . ' ' . $phone . ' ' . $message;

$blockedWords = [
    'crypto', 'trx', 'usdt', 'bitcoin', 'refund',
    'compensation', 'blockchain', 'wallet', 'airdrop'
];

foreach ($blockedWords as $word) {
    if (strpos($combined, $word) !== false) {
        http_response_code(403);
        exit(json_encode(['success' => false, 'message' => 'Spam detected']));
    }
}

// ------------------------
// 6. Forward to Mautic
// ------------------------
$mauticUrl = 'https://theangelstones.com/mautic/form/submit?formId=1';

$forwardData = http_build_query($_POST);

$ch = curl_init($mauticUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $forwardData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

$mauticResponse = curl_exec($ch);
$mauticHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($mauticResponse === false) {
    curl_close($ch);
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'Could not submit your message. Please try again.']));
}

curl_close($ch);

echo json_encode(['success' => true, 'message' => 'Thank you for your message! We will get back to you soon.']);
exit;