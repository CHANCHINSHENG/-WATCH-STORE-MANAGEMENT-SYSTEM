<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$name = $_POST['Admin_Username'];
$email = $_POST['Admin_Email'];
$profileImagePath = null;

// Handle image upload if exists
if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/admin_picture/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $tmpName = $_FILES['profileImage']['tmp_name'];
    $originalName = basename($_FILES['profileImage']['name']);
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    $safeFilename = 'admin_' . uniqid() . '.' . $ext;
    $targetPath = $uploadDir . $safeFilename;

    if (move_uploaded_file($tmpName, $targetPath)) {
        $profileImagePath = $targetPath;
    }
}

// Prepare SQL update using AdminID
if ($profileImagePath) {
    $stmt = $pdo->prepare("UPDATE 01_admin SET Admin_Username = ?, Admin_Email = ?, ProfileImage = ? WHERE AdminID = ?");
    $stmt->execute([$name, $email, $profileImagePath, $admin_id]);
} else {
    $stmt = $pdo->prepare("UPDATE 01_admin SET Admin_Username = ?, Admin_Email = ? WHERE AdminID = ?");
    $stmt->execute([$name, $email, $admin_id]);
}

// Correct: Only update display name if needed
$_SESSION['admin_name'] = $name;

// Success message
$_SESSION['update_success'] = "Profile updated successfully.";

header("Location: admin_layout.php?page=admin_edit_profile");
exit();
?>
