<?php
/**
 * Minimal pluggable mailer for password reset emails.
 *
 * Settings (environment variables, see README):
 *   APP_BASE_URL    Public base URL of the app, e.g. https://example.com/auth
 *                   (no trailing slash). Required to build reset links.
 *   MAIL_TRANSPORT  "mail" (default) uses PHP mail(); "log" writes the email
 *                   to the PHP error log instead (local development only).
 *   MAIL_FROM       From address, default no-reply@localhost.
 *
 * To plug in another transport (SMTP library, API, ...), replace the body of
 * send_app_mail() or set $GLOBALS['app_mail_transport'] to a callable with
 * the signature function (string $to, string $subject, string $body): bool.
 */

function app_base_url(): ?string
{
    $url = getenv('APP_BASE_URL');
    if ($url === false || trim($url) === '') {
        return null;
    }
    return rtrim(trim($url), '/');
}

function send_app_mail(string $to, string $subject, string $body): bool
{
    if (isset($GLOBALS['app_mail_transport']) && is_callable($GLOBALS['app_mail_transport'])) {
        return (bool) call_user_func($GLOBALS['app_mail_transport'], $to, $subject, $body);
    }

    $transport = getenv('MAIL_TRANSPORT') ?: 'mail';

    if ($transport === 'log') {
        error_log("[mail] To: $to | Subject: $subject\n$body");
        return true;
    }

    $from = getenv('MAIL_FROM') ?: 'no-reply@localhost';
    // Strip CR/LF so a header value can never inject extra headers.
    $from = str_replace(["\r", "\n"], '', $from);
    $headers = "From: $from\r\nContent-Type: text/plain; charset=UTF-8";

    return mail($to, $subject, $body, $headers);
}

function send_password_reset_email(string $to, string $token): bool
{
    $base = app_base_url();
    if ($base === null) {
        error_log('[mail] APP_BASE_URL is not set; password reset email not sent.');
        return false;
    }

    $link = $base . '/view/reset_password.php?token=' . urlencode($token);
    $body = "Someone asked to reset the password for this account.\n\n"
          . "Open this link within 1 hour to choose a new password:\n$link\n\n"
          . "If you did not ask for this, you can ignore this email.";

    return send_app_mail($to, 'Reset your password', $body);
}
