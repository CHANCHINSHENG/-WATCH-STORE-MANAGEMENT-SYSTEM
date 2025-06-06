<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

// Only super admins can update staff
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'super admin') {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id = $_POST['staff_id'];
    $name = trim($_POST['Admin_Name']);
    $username = trim($_POST['Admin_Username']);
    $email = trim($_POST['Admin_Email']);
    $role = $_POST['Admin_Role'];
    $currentImage = $_POST['currentImage'];

    // Handle profile image
    $newImage = $currentImage;
    if (!empty($_FILES['profileImage']['name'])) {
        $ext = pathinfo($_FILES['profileImage']['name'], PATHINFO_EXTENSION);
        $newName = 'admin_' . uniqid() . '.' . $ext;
        $target = 'uploads/admin_picture/' . $newName;
        move_uploaded_file($_FILES['profileImage']['tmp_name'], $target);
        $newImage = $target;

        // Optional: Delete old image file if exists and not default
        if (!empty($currentImage) && file_exists($currentImage)) {
            unlink($currentImage);
        }
    }

    $stmt = $pdo->prepare("UPDATE 01_admin SET Admin_Name = ?, Admin_Username = ?, Admin_Email = ?, Admin_Role = ?, ProfileImage = ? WHERE AdminID = ?");
    $stmt->execute([$name, $username, $email, $role, $newImage, $staff_id]);

    $_SESSION['staff_update_success'] = "Staff updated successfully!";
    header("Location: admin_layout.php?page=superadmin_editstaff&id=" . $staff_id);
    exit();
} else {
    header("Location: admin_layout.php?page=admin_view_staff");
    exit();
}
