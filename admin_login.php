<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/admin_login_view.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Watch Store - Admin Login</title>
    <link rel="stylesheet" href="admin_login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-image"></div>

        <div class="login-content">
            <div class="login-header">
                <h2>Admin Login</h2>
                <div class="watch-icon">⌚</div>
            </div>

            <?php viewerror(); ?>

            <form method="POST" action="admin_login_include/admin_login.inc.php" class="login-form">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="Admin_Username" placeholder="Enter your username" required>
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="Admin_Password" placeholder="Enter your password" required>
                </div>
                

                <button type="submit" class="login-btn">Login</button>
                
                <div class="links">
    <a href="admin_forgot_password.php">Forgot your password?</a>
</div>
            </form>
        </div>
    </div>
</body>
</html>
