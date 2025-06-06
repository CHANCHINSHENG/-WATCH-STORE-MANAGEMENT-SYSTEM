<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'super admin') {
    header("Location: admin_login.php");
    exit();
}

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];

    // Check for existing username/email
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM 01_admin WHERE Admin_Username = ? OR Admin_Email = ?");
    $checkStmt->execute([$username, $email]);
    if ($checkStmt->fetchColumn() > 0) {
        $message = "Username or Email already exists!";
        $messageType = "error";
    } else {
        $profileImage = null;
        if (!empty($_FILES['profile']['name'])) {
            $ext = pathinfo($_FILES['profile']['name'], PATHINFO_EXTENSION);
            $newName = 'admin_' . uniqid() . '.' . $ext;
            $target = 'uploads/admin_picture/' . $newName;
            move_uploaded_file($_FILES['profile']['tmp_name'], $target);
            $profileImage = $target;
        }

        $stmt = $pdo->prepare("INSERT INTO 01_admin (Admin_Name, Admin_Username, Admin_Email, Admin_Password, Admin_Role, ProfileImage) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $username, $email, $password, $role, $profileImage]);

        $message = "New staff added successfully!";
        $messageType = "success";
    }
}
?>

<link rel="stylesheet" href="admin_add_newstaff.css">

<div class="page-wrapper">
    <div class="content-container">
        <div class="header">
            <h2>Add New Staff</h2>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="product-form">
            <div class="form-grid">
                <div class="input-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="input-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="admin">Admin</option>
                        <option value="super admin">Super Admin</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>Profile Image</label>
                    <input type="file" name="profile">
                </div>
            </div>
            <div class="button-group">
                <a href="admin_layout.php?page=admin_view_staff" class="btn secondary-btn">Back to Staff</a>
                <button type="submit" class="btn primary-btn">Add Staff</button>
            </div>
        </form>
    </div>
</div>
