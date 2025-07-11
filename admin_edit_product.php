    <?php
    require_once 'admin_login_include/config_session.php';
    require_once 'admin_login_include/db.php';
    require_once 'admin_editproduct_include/admin_editproduct_model.php';
    require_once 'admin_editproduct_include/admin_editproduct_view.php';

    if (!isset($_SESSION['admin_id'])) {
        header("Location: admin_login.php");
        exit();
    }

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        die("❌ Invalid Product ID.");
    }

    $product_id = (int)$_GET['id'];
    $product = getProductById($pdo, $product_id);
    $productImages = getProductImages($pdo, $product_id);
    if (!$product) {
        die("❌ Product not found.");
    }

    $categories = getAllCategories($pdo);
    $brands = getAllBrands($pdo);

    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Edit Product - TIGO</title>
        <link rel="stylesheet" href="admin_edit_product.css">
    
    </head>
    <body>
    <div class="dashboard-container">
        <h2>Edit Product</h2>
        <?php displayFormMessages() ?>

        <form method="POST" enctype="multipart/form-data" action="admin_editproduct_include/admin_editproduct_inc.php?id=<?= $product_id ?>">
            <div class="input-group">
                <label>Product Name</label>
                <input type="text" name="ProductName" value="<?= htmlspecialchars($product['ProductName']); ?>" required>
            </div>
            <div class="input-group">
                <label>Description</label>
                <textarea name="Product_Description" required><?= htmlspecialchars($product['Product_Description']); ?></textarea>
            </div>
            <div class="input-group">
                <label>Price</label>
                <input type="number" name="Product_Price" step="0.01" value="<?= htmlspecialchars($product['Product_Price']); ?>" required  >
            </div>
            <div class="input-group">
                <label>Stock Quantity</label>
                <input type="number" name="Product_Stock_Quantity" value="<?= htmlspecialchars($product['Product_Stock_Quantity']); ?>" required  >
            </div>
    <div class="input-group">
        <label>Status</label>
        <input type="text" value="<?= ($product['Product_Stock_Quantity'] == 0) ? 'Out of Stock' : 'Available' ?>" readonly>
    </div>
        <div class="input-group">
        <label>Category</label>
        <select name="CategoryID" required>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['CategoryID'] ?>" <?= $product['CategoryID'] == $cat['CategoryID'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['CategoryName']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="input-group">
        <label>Brand</label>
        <select name="BrandID" required>
            <?php foreach ($brands as $brand): ?>
                <option value="<?= $brand['BrandID'] ?>" <?= $product['BrandID'] == $brand['BrandID'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($brand['BrandName']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>


            <!-- Image Replacements -->
            <div class="input-group">
                <label>Replace Image 1</label>
                <input type="file" name="product_image">
<?php if (isset($productImages[0])): ?>
    <img src="admin_addproduct_include/<?= htmlspecialchars($productImages[0]) ?>" class="preview-image" alt="Image 1">
<?php else: ?>
    <p style="color: red;">No image 1 found.</p>
<?php endif; ?>
            </div>

            <div class="input-group">
                <label>Replace Image 2</label>
                <input type="file" name="product_image2">
<?php if (isset($productImages[1])): ?>
    <img src="admin_addproduct_include/<?= htmlspecialchars($productImages[1]) ?>" class="preview-image" alt="Image 2">
<?php else: ?>
    <p style="color: red;">No image 2 found.</p>
<?php endif; ?>


            </div>  
            <div class="input-group">
                <label>Replace Image 3</label>
                <input type="file" name="product_image3">
 <?php if (isset($productImages[2])): ?>
    <img src="admin_addproduct_include/<?= htmlspecialchars($productImages[2]) ?>" class="preview-image" alt="Image 3">
<?php else: ?>
    <p style="color: red;">No image 3 found.</p>
<?php endif; ?>
            </div>

            <div class="button-group">
                <a href="admin_layout.php?page=admin_view_products" class="btn secondary-btn">Back</a>
                <button type="submit" class="btn primary-btn">Update Product</button>
            </div>
        </form>
    </div>
    </body>
    </html>
