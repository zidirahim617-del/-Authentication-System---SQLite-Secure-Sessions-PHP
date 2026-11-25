<?php
/**
 * Shared security helpers: hardened session start, CSRF tokens,
 * and login rate limiting. Included by core/config.php.
 */

// 48 hours, matching the README's session expiry promise.
if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', 48 * 60 * 60);
}

// Login rate limiting: max failed attempts within the window.
if (!defined('LOGIN_MAX_ATTEMPTS')) {
    define('LOGIN_MAX_ATTEMPTS', 5);          // per email + IP
    define('LOGIN_MAX_ATTEMPTS_PER_IP', 20);  // per IP, any email
    define('LOGIN_WINDOW', 15 * 60);          // 15 minutes
}

/**
 * True when the current request arrived over HTTPS.
 */
function is_https_request(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    return isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443;
}

/**
 * Start the session with hardened settings. Safe to call more than once.
 */
function secure_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');      // reject uninitialized session IDs
    ini_set('session.use_only_cookies', '1');     // no session IDs in URLs
    ini_set('session.use_trans_sid', '0');
    ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);

    session_set_cookie_params([
        'lifetime' => 0,                 // cookie ends with the browser session
        'path'     => '/',
        'secure'   => is_https_request(), // only send over HTTPS when served over HTTPS
        'httponly' => true,               // not readable from JavaScript
        'samesite' => 'Lax',              // not sent on cross-site POSTs
    ]);

    session_start();
}

/**
 * Return the per-session CSRF token, creating it if needed.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Hidden form field carrying the CSRF token.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Stop the request with 400 unless the posted CSRF token matches the session.
 */
function csrf_verify(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || empty($_SESSION['csrf_token'])
        || !hash_equals($_SESSION['csrf_token'], $sent)) {
        http_response_code(400);
        exit('Invalid CSRF token. Please go back, reload the page and try again.');
    }
}

/**
 * Create the login_attempts table if it does not exist yet.
 */
function ensure_login_attempts_table(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip TEXT NOT NULL,
            email TEXT NOT NULL,
            attempted_at INTEGER NOT NULL
        )
    ");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_login_attempts_ip ON login_attempts (ip, attempted_at)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_login_attempts_email ON login_attempts (email, ip, attempted_at)");
}

function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

/**
 * True when this IP (or this IP + email) has too many recent failed logins.
 */
function login_is_rate_limited(PDO $db, string $email): bool
{
    ensure_login_attempts_table($db);
    $since = time() - LOGIN_WINDOW;
    $ip = client_ip();
    $email = strtolower($email);

    // Drop old rows so the table stays small.
    $db->prepare("DELETE FROM login_attempts WHERE attempted_at < ?")->execute([$since]);

    $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE email = ? AND ip = ? AND attempted_at >= ?");
    $stmt->execute([$email, $ip, $since]);
    if ((int) $stmt->fetchColumn() >= LOGIN_MAX_ATTEMPTS) {
        return true;
    }

    $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at >= ?");
    $stmt->execute([$ip, $since]);
    return (int) $stmt->fetchColumn() >= LOGIN_MAX_ATTEMPTS_PER_IP;
}

function record_failed_login(PDO $db, string $email): void
{
    ensure_login_attempts_table($db);
    $db->prepare("INSERT INTO login_attempts (ip, email, attempted_at) VALUES (?, ?, ?)")
       ->execute([client_ip(), strtolower($email), time()]);
}

function clear_failed_logins(PDO $db, string $email): void
{
    ensure_login_attempts_table($db);
    $db->prepare("DELETE FROM login_attempts WHERE email = ? AND ip = ?")
       ->execute([strtolower($email), client_ip()]);
}
