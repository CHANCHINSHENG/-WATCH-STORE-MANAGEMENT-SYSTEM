<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "Category ID is missing.";
    exit();
}

$category_id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM 04_category WHERE CategoryID = ?");
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    echo "Category not found.";
    exit();
}
?>

<link rel="stylesheet" href="admin_edit_category.css">

<div class="page-wrapper">
        <h2>Edit Category</h2>
            <div class="content-container">
<?php if (isset($_SESSION['error_message'])): ?>
    <div class="message error"><?= $_SESSION['error_message']; ?></div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="message success"><?= $_SESSION['success_message']; ?></div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

        <form action="admin_update_category.php" method="POST" class="edit-form">
            <input type="hidden" name="CategoryID" value="<?= $category['CategoryID'] ?>">
            
            <label for="CategoryName">Category Name:</label>
            <input type="text" name="CategoryName" id="CategoryName" required
                   value="<?= htmlspecialchars($category['CategoryName']) ?>">

                <div class="button-group">
            <a href="admin_layout.php?page=admin_view_category" class="btn secondary-btn">Back</a>
            <button type="submit" class="btn primary-btn">Update Category</button>
        </div>

        </form>
    </div>
</div>
