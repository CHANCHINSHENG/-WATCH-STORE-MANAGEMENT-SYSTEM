<?php
require_once 'admin_login_include/db.php'; // Ensure this file connects to your MySQL database

$admin_name = "chan";
$admin_username = "chanchinsheng";
$admin_email = "chan@example.com";
$pwd = "chan123123"; // Plain text password

$options = [
    'cost' => 12
];

$hashpassword = password_hash($pwd, PASSWORD_BCRYPT, $options);

$query = "INSERT INTO 01_admin(Admin_Name, Admin_Username, Admin_Password, Admin_email) VALUES (?, ?, ?, ?)";
$stmt = $pdo->prepare($query);

if ($stmt->execute([$admin_name, $admin_username, $hashpassword, $admin_email])) {
    echo "✅ Admin account created successfully!";
} else {
    echo "❌ Error creating admin.";
}
?>
