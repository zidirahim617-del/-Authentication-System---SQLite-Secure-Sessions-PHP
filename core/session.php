<?php
// Auth guard for pages inside view/. SESSION_LIFETIME (48 hours) lives in security.php.
require_once __DIR__ . '/security.php';

secure_session_start();

// Check if user is logged in (guarded pages live in view/, next to login.php)
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Check if session is expired
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
    // Expired: clear session and redirect to login
    session_unset();
    session_destroy();
    header('Location: login.php?expired=1');
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Optional: make $user variable available on any page
$user = $_SESSION['user'];
