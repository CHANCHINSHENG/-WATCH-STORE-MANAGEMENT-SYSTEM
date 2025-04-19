<?php
session_start();
include 'db.php'; // Connect to database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['Admin_Username'];
    $password = $_POST['Admin_Password'];

    // Fetch admin details from database
    $stmt = $conn->prepare("SELECT AdminID, Admin_Password FROM 01_admin WHERE Admin_Username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    // Bind result variables
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($AdminID, $hashed_password);
        $stmt->fetch();

        // Verify password
        if (password_verify($password, $hashed_password)) {
            $_SESSION['admin_id'] = $AdminID;
            header("Location: admin_dashboard.php"); // Redirect to dashboard
            exit();
        } else {
            $error = "❌ Invalid password.";
        }
    } else {
        $error = "❌ Invalid username.";
    }
}
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
            
            <?php if (isset($error)) { ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php } ?>

            <form method="POST" action="admin_login.php" class="login-form">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username"
                        name="Admin_Username" 
                        placeholder="Enter your username"
                        required
                    >
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password"
                        name="Admin_Password" 
                        placeholder="Enter your password"
                        required
                    >
                </div>

                <button type="submit" class="login-btn">
                    Login
                </button>
            </form>
            
           
        </div>
    </div>
</body>
</html>