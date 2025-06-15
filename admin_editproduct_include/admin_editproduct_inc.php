<?php
require_once '../admin_login_include/config_session.php';
require_once '../admin_login_include/db.php';
require_once 'admin_editproduct_model.php';
require_once 'admin_editproduct_con.php';


if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Product ID.");
}

$product_id = (int)$_GET['id'];
$product = getProductById($pdo, $product_id);



if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['ProductName'] ?? '';
    $description = $_POST['Product_Description'] ?? '';
    $price = $_POST['Product_Price'] ?? '';
    $stock = $_POST['Product_Stock_Quantity'] ?? '';
    $status = $_POST['Product_Status'] ?? '';
    $category_id = $_POST['CategoryID'] ?? null;
$brand_id = $_POST['BrandID'] ?? null;


    // Set actual upload directory for saving images
    $upload_dir = '../admin_addproduct_include/uploads/products/';
    $db_image_prefix = 'uploads/products/';

    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    function handleImageUpdate($input_name, $current_path) {
        global $upload_dir, $db_image_prefix, $allowed;
        if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
            $filename = $_FILES[$input_name]["name"];
            $tmp = $_FILES[$input_name]["tmp_name"];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $new_name = uniqid('product_', true) . "." . $ext;
                $full_path = $upload_dir . $new_name;
                if (move_uploaded_file($tmp, $full_path)) {
                    return $db_image_prefix . $new_name;
                }
            }
        }
        return $current_path;
    }

    // Handle uploaded image replacements or retain existing
    $img1 = handleImageUpdate('product_image', $product['Product_Image']);
    $img2 = handleImageUpdate('product_image2', $product['Product_Image2']);
    $img3 = handleImageUpdate('product_image3', $product['Product_Image3']);

    if (emptyerrorsaddproduct($name, $description, $status, $category_id, $brand_id)) {
        $_SESSION['error_signup'] = ["❌ Please fill in all required fields."];
        header("Location: ../admin_layout.php?page=admin_edit_product&id=$product_id");
        exit();
    }

    if (hasNegativePriceOrStock($price, $stock)) {
        $_SESSION['error_signup'] = ["❌ Price and Stock must be valid non-negative numbers."];
        header("Location: ../admin_layout.php?page=admin_edit_product&id=$product_id");
        exit();
    }

    try {
        $stock_quantity = (int)$_POST['Product_Stock_Quantity'];
$status = ($stock_quantity == 0) ? 'Out of Stock' : 'Available';


$stmt = $pdo->prepare("UPDATE 05_product 
    SET ProductName=?, Product_Description=?, Product_Price=?, 
        Product_Stock_Quantity=?, Product_Status=?, 
        Product_Image=?, Product_Image2=?, Product_Image3=?, 
        CategoryID=?, BrandID=?
    WHERE ProductID=?");

$success = $stmt->execute([
    $name, $description, $price, $stock, $status,
    $img1, $img2, $img3, $category_id, $brand_id, $product_id
]);



        if ($success) {
            $_SESSION['success'] = "✅ Product updated successfully.";
        } else {
            $_SESSION['error_signup'] = ["❌ Failed to update product."];
        }

    } catch (PDOException $e) {
        $_SESSION['error_signup'] = ["❌ Error: " . $e->getMessage()];
    }

    header("Location: ../admin_layout.php?page=admin_edit_product&id=$product_id");
    die();
    exit();
} else {
    header("Location: ../admin_layout.php?page=admin_view_products");
    exit();
}
