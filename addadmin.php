<?php
// Database connection
$host = "localhost";
$user = "root";
$password = "";
$database = "final_watch_store_db"; // ⬅️ Change this to your database name

$conn = mysqli_connect($host, $user, $password, $database);

// Check connection
if (!$conn) {
    die("❌ Connection failed: " . mysqli_connect_error());
}

// Example admin values (you can replace with values from a form or another script)
$adminID = '1234';
$adminName = 'New Admin';
$adminUsername = 'admin1234';
$adminPassword = 'admin1234';
$adminEmail = 'admin2025@example.com';
// jeff ：我改的，可以不用管他

// Hash the password before saving
$hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);

// Insert into database
$stmt = $conn->prepare("INSERT INTO 01_admin (AdminID, Admin_Name, Admin_Username, Admin_Password, Admin_Email) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $adminID, $adminName, $adminUsername, $hashedPassword, $adminEmail);

if ($stmt->execute()) {
    echo "✅ Admin created successfully!";
} else {
    echo "❌ Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
