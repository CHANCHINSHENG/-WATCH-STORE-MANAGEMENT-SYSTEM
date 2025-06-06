<?php
session_start();
include 'db.php';

$message = '';
$success = false;

if (isset($_SESSION['reset_email']) && isset($_POST['new_password'])) {
    $email = $_SESSION['reset_email'];
    $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE 02_customer SET Cust_Password = ? WHERE Cust_Email = ?");
    $stmt->bind_param("ss", $newPassword, $email);

    if ($stmt->execute()) {
        $message = "✅ Your password has been updated successfully.";
        $success = true;
        session_destroy();
    } else {
        $message = "❌ Failed to update your password. Please try again.";
    }

    $stmt->close();
    $conn->close();
} else {
    $message = "⚠️ Session expired or invalid access.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Password Reset</title>
  <link rel="stylesheet" href="customerlogin.css" />
  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #121212;
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .card {
      background-color: #1e1e1e;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
      width: 90%;
      max-width: 400px;
      color: #fff;
      text-align: center;
    }

    .card h2 {
      color: #00bfff;
      margin-bottom: 20px;
    }

    .message {
      font-size: 16px;
      margin-bottom: 30px;
      color: <?= $success ? '#00ff7f' : '#ff4d4d' ?>;
    }

    .button {
      display: inline-block;
      padding: 12px 24px;
      font-size: 14px;
      background-color: <?= $success ? '#00bfff' : '#ff4d4d' ?>;
      color: #fff;
      border: none;
      border-radius: 8px;
      text-decoration: none;
      transition: background-color 0.3s ease;
    }

    .button:hover {
      background-color: <?= $success ? '#009acd' : '#e60000' ?>;
    }
  </style>
</head>
<body>
  <div class="card">
    <h2>Password Reset</h2>
    <p class="message"><?= $message ?></p>
    <a href="<?= $success ? 'customer_login.php' : 'customer_reset_password.php' ?>" class="button">
      <?= $success ? 'Go to Login' : 'Try Again' ?>
    </a>
  </div>
</body>
</html>

