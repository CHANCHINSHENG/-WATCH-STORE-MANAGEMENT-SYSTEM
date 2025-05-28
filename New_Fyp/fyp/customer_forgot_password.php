<?php
// customer_forgot_password.php
session_start();
require 'db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['Cust_Email'];

    // 检查邮箱是否存在
    $stmt = $conn->prepare("SELECT CustomerID FROM 02_customer WHERE Cust_Email = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error); // 打印错误信息并中止
}
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // 生成 token
        $token = bin2hex(random_bytes(16));
        $expiry = date("Y-m-d H:i:s", strtotime("+30 minutes"));

        $stmt->bind_result($customerId);
        $stmt->fetch();

        // 保存 token 到数据库（你需要有一个 token 表，例如 password_reset）
        $insert = $conn->prepare("INSERT INTO password_reset (CustomerID, Token, Expiry) VALUES (?, ?, ?)");
        $insert->bind_param("iss", $customerId, $token, $expiry);
        $insert->execute();

        // 发送邮件（在开发环境可以先 echo）
        $resetLink = "http://yourdomain.com/reset_password.php?token=$token";
        // mail($email, "Reset Your Password", "Click here to reset your password: $resetLink");
        $message = "✅ A password reset link has been sent to your email. (Dev Mode: <a href='$resetLink'>Reset Now</a>)";
    } else {
        $message = "❌ Email not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="customerlogin.css">
</head>
<body>
<div class="login-container">
    <h2 class="login-title">Forgot Password</h2>

    <?php if (!empty($message)): ?>
        <div class="error-message"><?= $message ?></div>
    <?php endif; ?>

    <form action="customer_forgot_password.php" method="POST">
        <div class="input-group">
            <label for="email">Email Address</label>
            <input type="email" name="Cust_Email" id="email" placeholder="Enter your registered email" required>
        </div>

        <button type="submit" class="login-btn">Send Reset Link</button>

        <p class="register-link">
            Remembered your password? <a href="customer_login.php">Go back to login</a>
        </p>
    </form>
</div>
</body>
</html>
