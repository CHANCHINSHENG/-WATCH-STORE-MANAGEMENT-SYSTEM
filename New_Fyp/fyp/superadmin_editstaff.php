<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'super admin') {
    header("Location: admin_login.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "<p style='color:red;'>No staff ID provided.</p>";
    exit();
}

$staff_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM 01_admin WHERE AdminID = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$staff) {
    echo "<p style='color:red;'>Staff not found.</p>";
    exit();
}
?>

<link rel="stylesheet" href="admin_edit_profile.css">

<div class="profile-wrapper">
    <div class="profile-container">
        <div class="header">
            <h2>Edit Staff</h2>
        </div>

        <?php if (isset($_SESSION['staff_update_success'])): ?>
            <div class="message success">
                <?= $_SESSION['staff_update_success']; unset($_SESSION['staff_update_success']); ?>
            </div>
        <?php endif; ?>

        <form action="superadmin_updatestaff.php" method="POST" enctype="multipart/form-data">
            <div class="upload-section">
                <label for="profileImage">Profile Picture</label>
                <div class="upload-box">
                    <input type="file" name="profileImage" accept=".jpg, .jpeg, .png, .webp">
                    <p>Drag your images here<br><small>(*.jpeg, *.webp, *.png accepted)</small></p>
                </div>
                <?php if (!empty($staff['ProfileImage'])): ?>
                    <div class="profile-preview">
                        <img src="<?= htmlspecialchars($staff['ProfileImage']) ?>" alt="Profile" />
                        <a href="admin_deleteimg.php?type=staff&id=<?= $staff_id ?>" class="remove-image">✖</a>
                    </div>
                <?php endif; ?>
            </div>

            <input type="hidden" name="currentImage" value="<?= htmlspecialchars($staff['ProfileImage']) ?>">
            <input type="hidden" name="staff_id" value="<?= $staff['AdminID'] ?>">

            <label>Full Name</label>
            <input type="text" name="Admin_Name" value="<?= htmlspecialchars($staff['Admin_Name']) ?>" required>

            <label>Username</label>
            <input type="text" name="Admin_Username" value="<?= htmlspecialchars($staff['Admin_Username']) ?>" required>

            <label>Email</label>
            <input type="email" name="Admin_Email" value="<?= htmlspecialchars($staff['Admin_Email']) ?>" required>

            <label>Admin Role</label>
            <select name="Admin_Role" required>
                <option value="admin" <?= $staff['Admin_Role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="super admin" <?= $staff['Admin_Role'] === 'super admin' ? 'selected' : '' ?>>Super Admin</option>
            </select>

            <div class="button-group">
                <a href="admin_layout.php?page=admin_view_staff" class="btn secondary-btn">Back</a>
                <button type="submit" class="btn primary-btn">Update Staff</button>
            </div>
        </form>
    </div>
</div>
