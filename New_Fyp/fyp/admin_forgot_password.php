<?php
require_once 'admin_login_include/config_session.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Forgot Password</title>
    <link rel="stylesheet" href="admin_login.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-image"></div>

        <div class="login-content">
            <div class="login-header">
                <h2>Forgot Password</h2>
                <div class="watch-icon">🔐</div>
            </div>

            <p style="margin-bottom: 20px; color: #cbd5e1;">
                Enter your admin email address to receive an OTP.
            </p>

            <?php if (isset($_SESSION['otp_error'])): ?>
                <div class="error-message"><?= $_SESSION['otp_error']; unset($_SESSION['otp_error']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['otp_success'])): ?>
                <div class="success-message"><?= $_SESSION['otp_success']; unset($_SESSION['otp_success']); ?></div>
            <?php endif; ?>

            <form method="POST" action="admin_login_include/send_otp.inc.php" class="login-form">
                <div class="input-group">
                    <label for="email">Admin Email</label>
                    <input type="email" id="email" name="email" placeholder="admin@example.com" required>
                </div>

                <button type="submit" class="login-btn">Send OTP</button>

                <div class="links">
                    <a href="admin_login.php">← Back to Login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
