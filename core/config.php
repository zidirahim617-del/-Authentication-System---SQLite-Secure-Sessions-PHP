<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Database location.
 *
 * Best practice: keep the SQLite file OUTSIDE the web root and point to it
 * with the AUTH_DB_PATH environment variable, for example:
 *   SetEnv AUTH_DB_PATH /var/lib/auth-app/users.db     (Apache)
 *   fastcgi_param AUTH_DB_PATH /var/lib/auth-app/users.db;  (nginx + PHP-FPM)
 *
 * Fallback: databases/users.db inside the project. That folder is blocked by
 * databases/.htaccess on Apache; on nginx you must deny it yourself
 * (see README).
 */
$dbPath = getenv('AUTH_DB_PATH') ?: (__DIR__ . '/../databases/users.db');

try {
    // Create PDO connection
    $db = new PDO("sqlite:" . $dbPath);

    // Set error mode to exceptions for better error reporting
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Optional: Force UTF-8 encoding for safety (not strictly required in SQLite)
    $db->exec("PRAGMA encoding = 'UTF-8';");

} catch (PDOException $e) {
    // Log the details for the admin, but never show the file path or driver
    // message to visitors
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    die("Database connection failed.");
}
