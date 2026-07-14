<?php
function startSecureAdminSession(): void {
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function ensureAdminCsrfToken(): string {
    startSecureAdminSession();
    if (empty($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf_token'];
}

function getSubmittedCsrfToken(): string {
    return $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? $_POST['csrf_token']
        ?? '';
}

function requireAdminSession(bool $verifyCsrf = true): void {
    startSecureAdminSession();

    if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }

    if ($verifyCsrf) {
        $expected = ensureAdminCsrfToken();
        $submitted = getSubmittedCsrfToken();

        if (!$submitted || !hash_equals($expected, $submitted)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Invalid security token']);
            exit;
        }
    }
}
?>
