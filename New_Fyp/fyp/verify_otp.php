<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verify OTP</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="customerlogin.css">
</head>
<body>

  <div class="login-container">
    <h2 class="login-title">Enter the OTP sent to your email</h2>

    <form method="post" action="check_otp.php">
      <div class="input-group">
        <label for="otp">OTP</label>
        <input type="text" name="otp" id="otp" maxlength="6" placeholder="Enter 6-digit OTP" required>
      </div>

      <button type="submit" class="login-btn">Verify</button>
    </form>

    <p class="register-link">
      Didn't receive an OTP? <a href="forgot_password.php">Resend OTP</a>
    </p>
  </div>

</body>
</html>

