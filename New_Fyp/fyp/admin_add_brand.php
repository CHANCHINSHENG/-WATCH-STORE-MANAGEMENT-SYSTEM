<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';
require_once 'admin_addbrand_include/admin_addbrand_view.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Brand</title>
    <link rel="stylesheet" href="admin_add_brand.css">
</head>
<body>
<div class="page-wrapper">
    <div class="content-container">
        <div class="header">
            <h2><span class="watch-icon">⌚</span> Add Brand</h2>
        </div>

        <?php displayFormMessages(); ?> <!-- ✅ Display messages here -->

        <form action="admin_addbrand_include/admin_addbrand_inc.php" method="POST" enctype="multipart/form-data" class="product-form">
            <div class="form-grid">
                <div class="input-group full-width">
                    <label for="brand_name">Brand Name</label>
                    <input type="text" id="brand_name" name="BrandName" required>
                </div>

                <div class="input-group full-width">
                    <label for="brand_image">Brand Image</label>
                    <input type="file" id="brand_image" name="BrandImage" class="file-upload-input" accept="image/*" required>
                    <div class="input-help">Only image files (jpg, jpeg, png, webp) allowed.</div>
                </div>
            </div>

            <div class="button-group">
                <a href="admin_layout.php?page=admin_viewbrand" class="btn secondary-btn">Back</a>
                <button type="submit" class="btn primary-btn">Add Brand</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
