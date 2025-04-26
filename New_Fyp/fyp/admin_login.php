<?php
require_once 'admin_login_include/admin_login_view.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watch Store - Admin Login</title>
    <link rel="stylesheet" href="admin_login.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <h2>Admin Login</h2>
                <div class="watch-icon">⌚</div>
            </div>
            
            <?php
            require_once 'admin_login_include/config_session.php';
            viewerror();
            ?>

            <form method="POST" action="admin_login_include/admin_login.inc.php" class="login-form">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="Admin_Username" placeholder="Enter your username">
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password"  id="password" name="Admin_Password" placeholder="Enter your password">
                </div>

                <button type="submit" class="login-btn">
                    Login
                </button>
            </form>
            
           
        </div>
    </div>
</body>
</html>