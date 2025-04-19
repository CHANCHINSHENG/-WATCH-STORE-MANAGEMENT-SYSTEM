<?php
include 'db.php'; // Ensure this file connects to your MySQL database

$admin_name = "Admin new";
$admin_username = "newadmin";
$admin_email = "adminnew@example.com";
$password = "admin12345"; // Plain text password

// Hash the password for security
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Insert the new admin into the database
$stmt = $conn->prepare("INSERT INTO ADMIN (Admin_Name, Admin_Username, Admin_Password, Admin_Email) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $admin_name, $admin_username, $hashed_password, $admin_email);

if ($stmt->execute()) {
    echo "✅ Admin account created successfully!";
} else {
    echo "❌ Error creating admin.";
}
?>