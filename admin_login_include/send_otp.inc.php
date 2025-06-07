<?php
require_once 'config_session.php';
require_once 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['otp_error'] = 'Invalid email format.';
        header("Location: ../admin_forgot_password.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT Admin_Username FROM 01_admin WHERE Admin_Email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            $admin_name = $admin['Admin_Username'];
            $otp = rand(100000, 999999);
            $expiry = time() + 300;

            $_SESSION['otp'] = [
                'email' => $email,
                'code' => $otp,
                'expiry' => $expiry
            ];

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'sy0829715@gmail.com';
            $mail->Password = 'pcuv yaxk yobd rtjz';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('TIGOAdmin@gmail.com', 'Tigo Admin Support');
            $mail->addAddress($email, $admin_name);
            $mail->isHTML(true);
            $mail->Subject = 'Tigo Admin - OTP for Password Reset';
            $mail->Body = "Hi {$admin_name},<br>Your OTP is <b>{$otp}</b>. Valid for 5 minutes.";
            $mail->AltBody = "OTP: {$otp}";

            $mail->send();

            $_SESSION['reset_email'] = $email;
            header("Location: ../admin_verify_otp.php");
            exit();
        } else {
            $_SESSION['otp_error'] = 'Email not registered in admin table.';
            header("Location: ../admin_forgot_password.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['otp_error'] = "Mailer error: {$mail->ErrorInfo}";
        header("Location: ../admin_forgot_password.php");
        exit();
    }
} else {
    $_SESSION['otp_error'] = 'Email is required.';
    header("Location: ../admin_forgot_password.php");
    exit();
}
