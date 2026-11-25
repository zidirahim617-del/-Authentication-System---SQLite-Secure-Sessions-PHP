<?php
require_once __DIR__ . '/../core/config.php';

// Keep the token in the URL from leaking to other sites through the Referer header.
header('Referrer-Policy: no-referrer');

$message = '';
$token = $_GET['token'] ?? '';
$show_form = true;
$user = false;

if (!is_string($token) || $token === '') {
    $message = "Invalid or missing token.";
    $show_form = false;
} else {
    // Tokens are stored hashed; expiry is stored in UTC, same as datetime('now').
    $stmt = $db->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > datetime('now')");
    $stmt->execute([hash('sha256', $token)]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $message = "Invalid or expired token.";
        $show_form = false;
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    csrf_verify();

    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 8) {
        $message = "Password must be at least 8 characters.";
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $update = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $update->execute([$hashed_password, $user['id']]);

        $message = "Password reset successful.";
        $show_form = false;
        $reset_done = true;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
</head>
<body>
    <h2>Reset Password</h2>
    <?php if ($message): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    <?php if (!empty($reset_done)): ?>
        <p>You can now <a href="login.php">log in</a>.</p>
    <?php endif; ?>

    <?php if ($show_form): ?>
        <form method="POST">
            <?php echo csrf_field(); ?>

            <label>New Password (at least 8 characters):</label><br>
            <input type="password" name="password" minlength="8" required><br><br>

            <label>Confirm Password:</label><br>
            <input type="password" name="confirm_password" minlength="8" required><br><br>

            <button type="submit">Reset Password</button>
        </form>
    <?php endif; ?>
</body>
</html>
