<?php
session_start();
if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
  header("Location: customer_forgot_password.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password</title>
  <link rel="stylesheet" href="customerlogin.css">
</head>
<body>

  <div class="login-container">
    <h2 class="login-title">Reset Your Password</h2>

    <form method="post" action="update_password.php" onsubmit="return validatePassword();">

      <div class="input-group">
        <label for="new_password">New Password</label>
        <div class="password-wrapper">
          <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
          <span class="toggle-password" onclick="togglePassword('new_password')">👁️</span>
        </div>
      </div>

      <div class="input-group">
        <label for="confirm_password">Confirm Password</label>
        <div class="password-wrapper">
          <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
          <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
        </div>
      </div>

      <p id="error-message" class="error-message" style="display:none;"></p>

      <button type="submit" class="login-btn">Reset Password</button>
    </form>
  </div>

  <script>
    function togglePassword(fieldId) {
      const field = document.getElementById(fieldId);
      field.type = field.type === "password" ? "text" : "password";
    }

    function validatePassword() {
      const newPassword = document.getElementById("new_password").value;
      const confirmPassword = document.getElementById("confirm_password").value;
      const errorMessage = document.getElementById("error-message");

      if (newPassword !== confirmPassword) {
        errorMessage.innerText = "Passwords do not match.";
        errorMessage.style.display = "block";
        return false;
      }

      return true;
    }
  </script>

</body>
</html>

