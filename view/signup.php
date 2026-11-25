<?php
require __DIR__ . '/../core/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name     = trim((string) ($_POST['name'] ?? ''));
    $email    = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['confirm_password'] ?? '');

    // Validate inputs
    if (strlen($name) < 2) {
        $error = "Name must be at least 2 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Check for existing user
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = "Email is already registered.";
        } else {
            // Hash and insert
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $email, $hashed])) {
                $success = "Account created. <a href='login.php'>Login here</a>.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Sign Up - Daily Sermon</title>
</head>
<body>

<h2>Sign Up</h2>

<?php if ($error): ?>
  <p style="color: red;"><?php echo $error; ?></p>
<?php elseif ($success): ?>
  <p style="color: green;"><?php echo $success; ?></p>
<?php endif; ?>

<form method="POST">
  <?php echo csrf_field(); ?>

  <p>
    <label>Full Name:<br>
      <input type="text" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
    </label>
  </p>

  <p>
    <label>Email:<br>
      <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
    </label>
  </p>

  <p>
    <label>Password:<br>
      <input type="password" name="password" required>
    </label>
  </p>

  <p>
    <label>Confirm Password:<br>
      <input type="password" name="confirm_password" required>
    </label>
  </p>

  <p><button type="submit">Sign Up</button></p>

  <p>Already have an account? <a href="login.php">Login here</a>.</p>
</form>

</body>
</html>
