<?php
session_start();
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // 你的 PHPMailer 路径

include 'db.php'; // 连接数据库

$response = ['status' => 'error', 'message' => 'Unknown error'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format.';
    } else {
        $stmt = $conn->prepare("SELECT Cust_First_Name FROM 02_customer WHERE Cust_Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            $user_name = $user['Cust_First_Name'];
            $otp = rand(100000, 999999);
            $expiry = time() + 300;

            $_SESSION['otp'] = [
                'email' => $email,
                'code' => $otp,
                'expiry' => $expiry
            ];

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'sy0829715@gmail.com';
                $mail->Password = 'pcuv yaxk yobd rtjz';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('xunxun710@gmail.com', 'Tigo Support');
                $mail->addAddress($email, $user_name);
                $mail->isHTML(true);
                $mail->Subject = 'Tigo - OTP for Password Reset';
                $mail->Body = "Hi {$user_name},<br>Your OTP for password reset is <b>{$otp}</b>. It is valid for 5 minutes.";
                $mail->AltBody = "OTP: {$otp} (Valid 5 minutes)";

                $mail->send();
                $response = ['status' => 'success', 'message' => 'OTP sent to your email.'];
                $_SESSION['reset_email'] = $email;
            } catch (Exception $e) {
                $response['message'] = "Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $response['message'] = 'Email not registered.';
        }

        $stmt->close();
        $conn->close();
    }
} else {
    $response['message'] = 'Email is required.';
}

echo json_encode($response);
