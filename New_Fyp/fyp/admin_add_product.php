<?php
session_start();
include 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['ProductName']);
    $description = trim($_POST['Product_Description']);
    $price = floatval($_POST['Product_Price']);
    $stock = intval($_POST['Product_Stock_Quantity']);
    $status = $_POST['Product_Status'];
    $category = $_POST['CategoryID'] ?? null;
    $brand = $_POST['BrandID'] ?? null;

    $image_path = '';
    $upload_error = '';

    // Handle file upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['product_image']['tmp_name'];
        $file_name = $_FILES['product_image']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = uniqid('product_', true) . '.' . $file_ext;
            $upload_dir = 'uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $image_path = $upload_dir . $new_file_name;

            if (!move_uploaded_file($file_tmp, $image_path)) {
                $upload_error = "Failed to upload image.";
            }
        } else {
            $upload_error = "Invalid file type. Allowed: JPG, JPEG, PNG, WEBP";
        }
    }

    // Validate and insert
    if ($price < 0) {
        $error = "❌ Price cannot be negative!";
    } elseif ($stock < 0) {
        $error = "❌ Stock quantity cannot be negative!";
    } elseif (!$category || !$brand) {
        $error = "❌ Category and Brand must be selected!";
    } elseif ($upload_error) {
        $error = "❌ $upload_error";
    } else {
        // Prepare SQL statement
        $sql = "INSERT INTO PRODUCT (ProductName, Product_Price, Product_Description, Product_Stock_Quantity, Product_Status, CategoryID, BrandID, Product_Image) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        // Debugging: Check if statement was prepared successfully
        if (!$stmt) {
            die("❌ SQL Error: " . $conn->error); // Show exact SQL error
        }

        // Bind parameters
        $stmt->bind_param("sdsissis", $name, $price, $description, $stock, $status, $category, $brand, $image_path);

        // Execute and check for errors
        if ($stmt->execute()) {
            $success = "✅ Product added successfully!";
        } else {
            $error = "❌ Error adding product: " . $stmt->error;
        }
    }
}

// Fetch categories and brands
$categories = $conn->query("SELECT * FROM CATEGORY");
$brands = $conn->query("SELECT * FROM BRAND");
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

            <?php if (isset($error)) { ?>
                <div class="message error"><?= $error ?></div>
            <?php } elseif (isset($success)) { ?>
                <div class="message success"><?= $success ?></div>
            <?php } ?>

            <form method="POST" enctype="multipart/form-data" class="product-form">
                <div class="form-grid">
                    <div class="input-group">
                        <label for="productName">Product Name</label>
                        <input type="text" name="ProductName" id="productName" required>
                    </div>
                    <div class="input-group">
                        <label for="price">Price (RM)</label>
                        <input type="number" name="Product_Price" id="price" step="0.01" min="0.01" required>
                    </div>
                    <div class="input-group">
                        <label for="stock">Stock Quantity</label>
                        <input type="number" name="Product_Stock_Quantity" id="stock" min="0" required>
                    </div>
                    <div class="input-group">
                        <label for="category">Category</label>
                        <select name="CategoryID" id="category" required>
                            <option value="">Select Category</option>
                            <?php while ($row = $categories->fetch_assoc()) { ?>
                                <option value="<?= $row['CategoryID'] ?>"><?= $row['CategoryName'] ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="brand">Brand</label>
                        <select name="BrandID" id="brand" required>
                            <option value="">Select Brand</option>
                            <?php while ($row = $brands->fetch_assoc()) { ?>
                                <option value="<?= $row['BrandID'] ?>"><?= $row['BrandName'] ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="status">Status</label>
                        <select name="Product_Status" id="status" required>
                            <option value="Available">Available</option>
                            <option value="Out of Stock">Out of Stock</option>
                        </select>
                    </div>
                </div>

                <div class="input-group full-width">
                    <label for="description">Product Description</label>
                    <textarea name="Product_Description" id="description" rows="4" placeholder="Enter product description"></textarea>
                </div>

                <div class="input-group full-width">
                    <label for="product_image">Product Image</label>
                    <input type="file" name="product_image" id="product_image" accept=".jpg,.jpeg,.png,.webp" class="file-upload-input">
                    <div class="file-upload-preview"></div>
                </div>

                <div class="button-group">
                    <a href="admin_dashboard.php" class="btn secondary-btn">Back</a>
                    <button type="submit" class="btn primary-btn">Add Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('product_image').addEventListener('change', function (e) {
            const file = e.target.files[0];
            const preview = document.querySelector('.file-upload-preview');
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.innerHTML = `<img src="${e.target.result}" class="upload-preview-image" alt="Preview">`;
                }
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        });
    </script>
</body>
</html>
