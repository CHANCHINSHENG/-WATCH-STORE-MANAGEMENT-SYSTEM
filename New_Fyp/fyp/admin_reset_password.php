<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';

$success = '';
$error = '';

if (!isset($_SESSION['verified_email'])) {
    header("Location: admin_forgot_password.php");
    exit();
}

$email = $_SESSION['verified_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE 01_admin SET Admin_Password = ? WHERE Admin_Email = ?");
        $stmt->execute([$hashedPassword, $email]);

        // Clear session
        unset($_SESSION['verified_email']);
        unset($_SESSION['otp']);

        $success = "✅ Password reset successfully. <a href='admin_login.php'>Login Now</a>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="admin_login.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-image"></div>
        <div class="login-content">
            <div class="login-header">
                <h2>Reset Password</h2>
                <div class="watch-icon">🔒</div>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?= $success ?></div>
            <?php else: ?>
                <form method="POST" action="" class="login-form">
                    <div class="input-group">
                        <label for="password">New Password</label>
                        <input type="password" name="password" placeholder="Enter new password" required>
                    </div>
                    <div class="input-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                    </div>
                    <button type="submit" class="login-btn">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
