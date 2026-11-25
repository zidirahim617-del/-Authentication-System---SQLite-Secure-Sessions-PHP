<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/mailer.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        die('Invalid CSRF token');
    }

    $email = trim($_POST['email'] ?? '');

    $stmt = $db->prepare("SELECT id, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // The raw token goes only into the email; the database keeps its hash.
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        // Stored in UTC to match SQLite's datetime('now') in reset_password.php.
        $expires = gmdate('Y-m-d H:i:s', time() + 3600);

        $update = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
        $update->execute([$tokenHash, $expires, $user['id']]);

        send_password_reset_email($user['email'], $token);
    }

    // Same answer whether or not the account exists.
    $message = "If an account exists for that email address, a password reset link has been sent to it.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
</head>
<body>
    <h2>Forgot Password</h2>
    <?php if ($message): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <label>Email Address:</label><br>
        <input type="email" name="email" required><br><br>
        <button type="submit">Send Reset Link</button>
    </form>
    <p><a href="login.php">Back to login</a></p>
</body>
</html>
