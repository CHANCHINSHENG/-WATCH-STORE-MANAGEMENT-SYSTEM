<?php
session_start();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $enteredOtp = trim($_POST['otp']);

    if (
        isset($_SESSION['otp']['code']) &&
        $enteredOtp == $_SESSION['otp']['code'] &&
        time() < $_SESSION['otp']['expiry']
    ) {
        $_SESSION['otp_verified'] = true;
        header("Location: customer_reset_password.php");
        exit;
    } else {
        $message = "Invalid or expired OTP.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>OTP Verification</title>
    <link rel="stylesheet" href="customerlogin.css" />
</head>
<body>
  <div class="login-container">
    <h2 class="login-title">OTP Verification</h2>

    <?php if ($message): ?>
      <div class="error-message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post" action="check_otp.php">
      <div class="input-group">
        <label for="otp">Enter OTP Code</label>
        <input type="text" id="otp" name="otp" maxlength="6" placeholder="Enter OTP" required />
      </div>

      <button type="submit" class="login-btn">Verify</button>
    </form>
  </div>
</body>
</html>