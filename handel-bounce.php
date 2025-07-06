<?php
// === CONFIGURATION ===
$clientId     = '1_25mi5zfaqj8k4s4wgoko8w4s8osk0sgkg488ccsw844k0co0k4';
$clientSecret = '577azf53jf48wo48cow0go448ossgo4s40g00c04osc48g0ocg';
$mauticUrl    = 'https://theangelstones.com/mautic';
$logFile      = __DIR__ . '/bounced-emails.log';
// =====================

header("Content-Type: application/json");

// === STEP 1: Confirm SNS Subscription ===
$rawPost = file_get_contents('php://input');
$data = json_decode($rawPost, true);

if (isset($data['Type']) && $data['Type'] === 'SubscriptionConfirmation') {
    file_get_contents($data['SubscribeURL']);
    echo 'SNS subscription confirmed';
    exit;
}

// === STEP 2: Get Access Token ===
$tokenRequest = http_build_query([
    'grant_type'    => 'client_credentials',
    'client_id'     => $clientId,
    'client_secret' => $clientSecret,
]);

$context = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $tokenRequest
    ]
]);

$tokenResponse = file_get_contents("$mauticUrl/oauth/v2/token", false, $context);
$tokenData = json_decode($tokenResponse, true);

$accessToken = $tokenData['access_token'] ?? null;
if (!$accessToken) {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " | ERROR: Failed to get token\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve token']);
    exit;
}

// === STEP 3: Parse bounce or complaint ===
$emails = [];
$reason = '';

if (isset($data['Type']) && $data['Type'] === 'Notification') {
    $message = json_decode($data['Message'], true);
    
    if (!empty($message['bounce'])) {
        $emails = array_column($message['bounce']['bouncedRecipients'], 'emailAddress');
        $reason = $message['bounce']['bounceType'] ?? 'unknown';
    } elseif (!empty($message['complaint'])) {
        $emails = array_column($message['complaint']['complainedRecipients'], 'emailAddress');
        $reason = 'complaint';
    }
}

// === STEP 4: For each email, mark DNC in Mautic ===
foreach ($emails as $email) {
    // 1. Search contact by email
    $searchUrl = "$mauticUrl/api/contacts?search=$email";
    $searchResponse = file_get_contents($searchUrl, false, stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => "Authorization: Bearer $accessToken"
        ]
    ]));

    $searchData = json_decode($searchResponse, true);
    if (empty($searchData['contacts'])) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " | SKIPPED: $email not found\n", FILE_APPEND);
        continue;
    }

    $contactId = array_key_first($searchData['contacts']);

    // 2. Mark as DNC (Do Not Contact)
    $dncUrl = "$mauticUrl/api/contacts/$contactId/dnc/email/add";
    $dncPayload = json_encode(['reason' => $reason, 'channel' => 'email']);

    $dncContext = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => [
                "Authorization: Bearer $accessToken",
                "Content-Type: application/json"
            ],
            'content' => $dncPayload
        ]
    ]);

    $dncResponse = file_get_contents($dncUrl, false, $dncContext);

    // 3. Log result
    file_put_contents($logFile, date('Y-m-d H:i:s') . " | $email | $reason | Contact ID: $contactId\n", FILE_APPEND);
}

echo json_encode(['status' => 'done']);