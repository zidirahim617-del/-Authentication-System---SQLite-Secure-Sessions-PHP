<?php
require __DIR__ . '/../core/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (login_is_rate_limited($db, $email)) {
        http_response_code(429);
        $error = "Too many failed login attempts. Please wait 15 minutes and try again.";
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$error && $user && password_verify($password, $user['password'])) {
        clear_failed_logins($db, $email);

        // 🔐 Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        // New session, new CSRF token
        unset($_SESSION['csrf_token']);

        // Store user data in session
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ];

        // Set session creation time for 48-hour expiration tracking
        $_SESSION['last_activity'] = time();

        header("Location: dashboard.php");
        exit();
    } elseif (!$error) {
        record_failed_login($db, $email);
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Login - Daily Sermon</title>
</head>
<body>

<h2>Login</h2>

<?php if (isset($_GET['expired'])): ?>
  <p style="color: orange;">Your session has expired. Please log in again.</p>
<?php endif; ?>

<?php if (isset($_GET['logged_out'])): ?>
  <p style="color: green;">You have been logged out successfully.</p>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<form method="POST">
  <?php echo csrf_field(); ?>
  <p>
    <label>Email:<br>
      <input type="email" name="email" required>
    </label>
  </p>
  <p>
    <label>Password:<br>
      <input type="password" name="password" required>
    </label>
  </p>
  <p><button type="submit">Login</button></p>
  <p>Don't have an account? <a href="signup.php">Sign up here</a>.</p>
  <p>Forgot Password? <a href="forgot_password.php">Reset</a>.</p>
</form>

</body>
</html>
