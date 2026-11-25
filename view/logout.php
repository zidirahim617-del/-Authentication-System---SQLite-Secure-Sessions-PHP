<?php
require_once __DIR__ . '/../core/security.php';

secure_session_start();

// Logout must be a POST with a valid CSRF token, so other sites cannot log users out
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}
csrf_verify();

$_SESSION = [];

// Remove the session cookie from the browser too
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'],
    ]);
}

session_destroy();
header('Location: login.php?logged_out=1');
exit;
