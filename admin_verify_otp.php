<?php
require_once 'admin_login_include/config_session.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_otp = trim($_POST['otp']);
    
    if (!isset($_SESSION['otp']) || time() > $_SESSION['otp']['expiry']) {
        $error = "❌ OTP has expired. Please request a new one.";
    } elseif ($input_otp != $_SESSION['otp']['code']) {
        $error = "⚠️ Invalid OTP. Please try again.";
    } else {
        // OTP matched
        $_SESSION['verified_email'] = $_SESSION['otp']['email']; // store for password reset
        $success = "✅ OTP verified. You may now reset your password.";
        header("Refresh: 2; URL=reset_password.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="admin_login.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-image"></div>

        <div class="login-content">
            <div class="login-header">
                <h2>Verify OTP</h2>
                <div class="watch-icon">🔑</div>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST" action="admin_verify_otp.php" class="login-form">
                <div class="input-group">
                    <label for="otp">Enter OTP</label>
                    <input type="text" id="otp" name="otp" placeholder="6-digit code" required>
                </div>

                <button type="submit" class="login-btn">Verify</button>

                <div class="links">
                    <a href="forgot_password.php">← Back to Forgot Password</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
