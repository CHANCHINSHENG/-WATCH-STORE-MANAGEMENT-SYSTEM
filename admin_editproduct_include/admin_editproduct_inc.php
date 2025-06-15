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

function handleImageUpdate($input_name) {
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
    return '';
}

function insertProductImage($pdo, $productId, $imagePath) {
    $stmt = $pdo->prepare("INSERT INTO 06_product_images (ProductID, ImagePath) VALUES (?, ?)");
    $stmt->execute([$productId, $imagePath]);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['ProductName'] ?? '';
    $description = $_POST['Product_Description'] ?? '';
    $price = $_POST['Product_Price'] ?? '';
    $stock = $_POST['Product_Stock_Quantity'] ?? '';
    $status = $_POST['Product_Status'] ?? '';
    $category_id = $_POST['CategoryID'] ?? null;
    $brand_id = $_POST['BrandID'] ?? null;

    $upload_dir = '../admin_addproduct_include/uploads/products/';
    $db_image_prefix = 'uploads/products/';
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

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
        $stock_quantity = (int)$stock;
        $status = ($stock_quantity == 0) ? 'Out of Stock' : 'Available';

        $stmt = $pdo->prepare("UPDATE 05_product 
            SET ProductName=?, Product_Description=?, Product_Price=?, 
                Product_Stock_Quantity=?, Product_Status=?, 
                CategoryID=?, BrandID=?
            WHERE ProductID=?");

        $success = $stmt->execute([
            $name, $description, $price, $stock_quantity, $status,
            $category_id, $brand_id, $product_id
        ]);


$img1 = handleImageUpdate('product_image');
$img2 = handleImageUpdate('product_image2');
$img3 = handleImageUpdate('product_image3');

// 取得現有圖片（optional，但可以保留刪除）
$existingImages = getProductImages($pdo, $product_id);

// 主圖 image 1
if (!empty($img1)) {
    if (isset($existingImages[0])) {
        $pdo->prepare("DELETE FROM 06_product_images WHERE ProductID = ? AND ImagePath = ?")
            ->execute([$product_id, $existingImages[0]]);
    }
    insertProductImage($pdo, $product_id, $img1, true, 1); // ✅ 主圖
}

// 圖片 2
if (!empty($img2)) {
    if (isset($existingImages[1])) {
        $pdo->prepare("DELETE FROM 06_product_images WHERE ProductID = ? AND ImagePath = ?")
            ->execute([$product_id, $existingImages[1]]);
    }
    insertProductImage($pdo, $product_id, $img2, false, 2); // ✅ 非主圖，排序2
}

// 圖片 3
if (!empty($img3)) {
    if (isset($existingImages[2])) {
        $pdo->prepare("DELETE FROM 06_product_images WHERE ProductID = ? AND ImagePath = ?")
            ->execute([$product_id, $existingImages[2]]);
    }
    insertProductImage($pdo, $product_id, $img3, false, 3); // ✅ 非主圖，排序3
}


        if ($success) {
            $_SESSION['success'] = "✅ Product updated successfully.";
        } else {
            $_SESSION['error_signup'] = ["❌ Failed to update product."];
        }

    } catch (PDOException $e) {
        $_SESSION['error_signup'] = ["❌ Error: " . $e->getMessage()];
    }

    header("Location: ../admin_layout.php?page=admin_edit_product&id=$product_id");
    exit();
} else {
    header("Location: ../admin_layout.php?page=admin_view_products");
    exit();
}
