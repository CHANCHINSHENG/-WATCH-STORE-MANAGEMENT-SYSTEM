<?php
session_start();
require 'db.php';

$token = $_GET['token'] ?? '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    } else {
        $stmt = $conn->prepare("SELECT CustomerID FROM password_reset WHERE token = ? AND expires_at > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_assoc()) {
            $customer_id = $row['CustomerID'];
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update password
            $update_stmt = $conn->prepare("UPDATE 02_customer SET Cust_Password = ? WHERE CustomerID = ?");
            $update_stmt->bind_param("si", $hashed_password, $customer_id);
            $update_stmt->execute();

            // Delete token after use
            $delete_stmt = $conn->prepare("DELETE FROM password_reset WHERE token = ?");
            $delete_stmt->bind_param("s", $token);
            $delete_stmt->execute();

            $_SESSION['success'] = "Password reset successful! Please login.";
            header("Location: customer_login.php");
            exit();
        } else {
            $errors[] = "Invalid or expired token.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="customerlogin.css">
</head>
<body>
<div class="login-container">
    <h2 class="login-title">Reset Password</h2>

    <?php if (!empty($errors)): ?>
        <div class="error-message">
            <?php foreach ($errors as $e): echo htmlspecialchars($e) . "<br>"; endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="reset_password.php" method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="input-group">
            <label for="new_password">New Password</label>
            <input type="password" name="new_password" id="new_password" required>
        </div>

        <div class="input-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" required>
        </div>

        <button type="submit" class="login-btn">Set New Password</button>
    </form>
</div>
</body>
</html>
