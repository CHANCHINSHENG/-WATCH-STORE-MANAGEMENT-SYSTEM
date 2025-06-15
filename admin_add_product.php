<?php
 require_once 'admin_login_include/db.php';
 require_once 'admin_login_include/config_session.php';
 require_once 'admin_addproduct_include/admin_addproduct_model.php';
 require_once 'admin_addproduct_include/admin_addproduct_view.php';


error_reporting(E_ALL);

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
$categories = getAllCategories($pdo);
$brands = getAllBrands($pdo);
?>  


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product - Admin</title>
    <link rel="stylesheet" href="admin_add_product.css">
</head>
<body>
    <div class="page-wrapper">
        <div class="content-container">
            <div class="header">
                <h2>Add New Product</h2>
                <div class="watch-icon">⌚</div>
            </div>
<?php
$formdata = $_SESSION['formdata'] ?? [];
$errors = $_SESSION['error_signup'] ?? [];
?>
            <?php displayFormMessages(); ?>

            
            <form method="POST" action="admin_addproduct_include/admin_addproduct_inc.php" enctype="multipart/form-data" class="product-form">
                <div class="form-grid">
                    <div class="input-group">
                        <label for="productName">Product Name</label>
                        <input type="text" name="ProductName" id="productName" value="<?= htmlspecialchars($formdata['ProductName'] ?? '') ?>">
                    </div>
                    <div class="input-group">
                        <label for="price">Price (RM)</label>
                        <input type="number" name="Product_Price" id="price" value="<?= htmlspecialchars($formdata['Product_Price'] ?? '') ?>">

                    </div>
                    <div class="input-group">
                        <label for="stock">Stock Quantity</label>
                        <input type="number" name="Product_Stock_Quantity" value="<?= htmlspecialchars($formdata['Product_Stock_Quantity'] ?? '') ?>">

                    </div>
                    <div class="input-group">
                        <label for="category">Category</label>
                       <select name="CategoryID" id="category">
    <option value="">Select Category</option>
    <?php foreach ($categories as $row): ?>
        <option value="<?= htmlspecialchars($row['CategoryID']) ?>" 
            <?= ($formdata['CategoryID'] ?? '') == $row['CategoryID'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($row['CategoryName']) ?>
        </option>
    <?php endforeach; ?>
</select>
                    </div>
                    <div class="input-group">
                        <label for="brand">Brand</label>
                      <select name="BrandID" id="brand">
    <option value="">Select Brand</option>
    <?php foreach ($brands as $row): ?>
        <option value="<?= htmlspecialchars($row['BrandID']) ?>" 
            <?= ($formdata['BrandID'] ?? '') == $row['BrandID'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($row['BrandName']) ?>
        </option>
    <?php endforeach; ?>
</select>
                    </div>
                    <div class="input-group">
                        <label for="status">Status</label>
                       <select name="Product_Status" id="status">
    <option value="">Select Status</option>
    <option value="Available" <?= ($formdata['Product_Status'] ?? '') == 'Available' ? 'selected' : '' ?>>In stock</option>
    <option value="Out of Stock" <?= ($formdata['Product_Status'] ?? '') == 'Out of Stock' ? 'selected' : '' ?>>Out of Stock</option>
</select>
                    </div>
                </div>

                <div class="input-group full-width">
                    <label for="description">Product Description</label>
                    <textarea name="Product_Description" id="description" rows="4" placeholder="Enter product description"><?= htmlspecialchars($formdata['Product_Description'] ?? '') ?></textarea>
                </div>

                <div class="input-group full-width">
                    <label for="product_image">Product Image</label>
                    <input type="file" name="product_image" id="product_image" accept=".jpg,.jpeg,.png,.webp" class="file-upload-input">
                    <div class="file-upload-preview" id="preview1"></div>
                </div>

                <div class="input-group full-width">
                    <label for="product_image2">Product Image 2</label>
                    <input type="file" name="product_image2" id="product_image2" accept=".jpg,.jpeg,.png,.webp" class="file-upload-input">
                    <div class="file-upload-preview" id="preview2"></div>
                </div>

                <div class="input-group full-width">
                    <label for="product_image3">Product Image 3</label>
                    <input type="file" name="product_image3" id="product_image3" accept=".jpg,.jpeg,.png,.webp" class="file-upload-input">
                    <div class="file-upload-preview" id="preview3"></div>
                </div>

                <div class="button-group">
                    <a href="admin_layout.php?page=admin_view_products" class="btn secondary-btn">Back</a>
                    <button type="submit" class="btn primary-btn">Add Product</button>
                </div>
            </form>
        </div>
    </div>


</body>
<?php
unset($_SESSION['formdata']);
unset($_SESSION['error_signup']);
?>
</html>
