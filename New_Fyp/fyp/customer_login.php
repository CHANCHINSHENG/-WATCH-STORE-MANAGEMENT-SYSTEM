<?php
session_start();
include 'db.php'; // Connect to database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['Cust_Username'];
    $password = $_POST['Cust_Password'];

    // Fetch customer details from database
    $stmt = $conn->prepare("SELECT CustomerID, Cust_Password FROM 02_CUSTOMER WHERE Cust_Username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    // Check if user exists
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($CustomerID, $hashed_password);
        $stmt->fetch();


        // Verify password
        if (password_verify($password, $hashed_password)) {
            $_SESSION['customer_id'] = $CustomerID;
            header("Location: customermainpage.php"); // Redirect to main page
            exit();
        } else {
            $error = "❌ Incorrect password. Please try again.";
        }
    } else {
        $error = "❌ Username not found.";
    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TIGO Customer Login</title>
    <link rel="stylesheet" href="customerlogin.css">
</head>
<body>

    <div class="login-container">
        <h2 class="login-title">Customer Login</h2>

        <?php if (isset($error)) { ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php } ?>

        <form action="customer_login.php" method="POST">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="Cust_Username" placeholder="Enter your username" required>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="Cust_Password" placeholder="Enter your password" required>
                    <span class="toggle-password" onclick="togglePassword()">👁️</span>
                </div>
            </div>

            <button type="submit" class="login-btn">Login</button>

            <p class="register-link">
                Don't have an account? <a href="customer_signup.php">Sign up here</a>
            </p>
        </form>
    </div>

    <script>
        function togglePassword() {
            let passwordField = document.getElementById("password");
            passwordField.type = passwordField.type === "password" ? "text" : "password";
        }
    </script>

</body>
</html>
