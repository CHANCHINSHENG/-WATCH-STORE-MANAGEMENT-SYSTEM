<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password</title>
  <link rel="stylesheet" href="customerlogin.css">
</head>
<body>

  <div class="login-container">
    <h2 class="login-title">Forgot Password</h2>

    <form id="otp-form">
      <div class="input-group">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" placeholder="Enter your email" required>
      </div>

      <button type="submit" class="login-btn">Send OTP</button>

      <p id="response-message" class="error-message" style="margin-top: 15px;"></p>
    </form>

    <p class="register-link">
      Remember your password? <a href="customer_login.php">Login here</a>
    </p>
  </div>

  <script>
    document.getElementById("otp-form").addEventListener("submit", function (e) {
      e.preventDefault();

      const email = document.getElementById("email").value;

      fetch("send_otp.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ email: email })
      })
      .then(response => response.json())
      .then(data => {
        const messageElement = document.getElementById("response-message");
        messageElement.innerText = data.message;
        if (data.status === "success") {
          messageElement.style.color = "green";
          setTimeout(() => {
            window.location.href = "verify_otp.php";
          }, 1500);
        } else {
          messageElement.style.color = "red";
        }
      });
    });
  </script>

</body>
</html>

