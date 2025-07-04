<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$stmt = $pdo->prepare("SELECT * FROM 01_admin WHERE AdminID = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    echo "<p style='color:red;'>Error: Admin not found in database.</p>";
    exit();
}
?>

<link rel="stylesheet" href="admin_edit_profile.css">

<div class="profile-wrapper">
    <div class="profile-container">
        <div class="header">
            <h2>Edit Profile</h2>
        </div>

       <?php if (isset($_SESSION['update_success'])): ?>
    <div class="message success">
        <?= $_SESSION['update_success']; ?>
    </div>
    <?php unset($_SESSION['update_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['update_error'])): ?>
    <div class="message error">
        <?= $_SESSION['update_error']; ?>
    </div>
    <?php unset($_SESSION['update_error']); ?>
<?php endif; ?>


        <form action="admin_update_profile.php" method="POST" enctype="multipart/form-data">
            <div class="upload-section">
                <label for="profileImage">Profile Picture</label>
                <div class="upload-box">
                    <input type="file" name="profileImage" accept=".jpg, .jpeg, .png, .webp">
                    <p>Drag your images here<br><small>(*.jpeg, *.webp, *.png accepted)</small></p>
                </div>
                <?php if (!empty($admin['ProfileImage'])): ?>
                    <div class="profile-preview">
                        <img src="<?= htmlspecialchars($admin['ProfileImage']) ?>" alt="Profile" />
                        <a href="admin_deleteimg.php" class="remove-image">✖</a>
                    </div>
                <?php endif; ?>
            </div>

            <input type="hidden" name="currentImage" value="<?= htmlspecialchars($admin['ProfileImage']) ?>">

            <label>Name</label>
            <input type="text" name="Admin_Username" value="<?= htmlspecialchars($admin['Admin_Username']) ?>" required>

            <label>Email</label>
            <input type="email" name="Admin_Email" 
            value="<?= htmlspecialchars($admin['Admin_Email']) ?>" 
            required 
            pattern="[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$"
            title="Please enter a valid email like example@example.com">

            <label>Admin Role</label>
            <input type="text" name="Admin_Role" value="<?= htmlspecialchars($admin['Admin_Role']) ?>" readonly>

           
            <div class="button-group">
                <a href="admin_layout.php?page=admin_main_page" class="btn secondary-btn">Back</a>
                <button type="submit" class="btn primary-btn">Update Profile</button>
            </div>
        </form>
    </div>
</div>
