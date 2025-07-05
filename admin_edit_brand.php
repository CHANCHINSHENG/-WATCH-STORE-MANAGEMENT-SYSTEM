<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "Brand ID is missing.";
    exit();
}

$brand_id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM 03_brand WHERE BrandID = ?");
$stmt->execute([$brand_id]);
$brand = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$brand) {
    echo "Brand not found.";
    exit();
}
?>

<link rel="stylesheet" href="admin_edit_brand.css">

<div class="page-wrapper">
    <h2>Edit Brand</h2>
    <div class="content-container">

    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="message error">
        <span><?= $_SESSION['error_message']; ?></span>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>


        <form action="admin_update_brand.php" method="POST" enctype="multipart/form-data" class="edit-form">
            <input type="hidden" name="BrandID" value="<?= $brand['BrandID'] ?>">

            <label for="BrandName">Brand Name:</label>
            <input type="text" name="BrandName" id="BrandName" required
                   value="<?= htmlspecialchars($brand['BrandName']) ?>">

            <label for="BrandImage">Brand Image:</label>
            <input type="file" name="BrandImage" accept="image/*">
            <?php if (!empty($brand['BrandImage'])): ?>
                <img src="uploads/<?= htmlspecialchars($brand['BrandImage']) ?>" alt="Brand Image"
                     style="max-width: 120px; margin-top: 10px;">
            <?php endif; ?>

            <div class="button-group">
                <a href="admin_layout.php?page=admin_viewbrand" class="btn secondary-btn">Back</a>
                <button type="submit" class="btn primary-btn">Update Brand</button>
            </div>
        </form>
    </div>
</div>
