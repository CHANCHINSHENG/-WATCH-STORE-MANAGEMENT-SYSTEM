<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['ProductName'];
    $description = $_POST["Product_Description"];
    $price = floatval($_POST["Product_Price"]);
    $stock = intval($_POST["Product_Stock_Quantity"]);
    $status = $_POST["Product_Status"];
    $category = $_POST["CategoryID"] ?? null;
    $brand = $_POST["BrandID"] ?? null;

    try {
        require_once 'admin_addproduct_con.php';
        require_once 'admin_addproduct_model.php';
        require_once '../admin_login_include/db.php';
        require_once '../admin_login_include/config_session.php';

        $upload_dir = 'uploads/products/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed = array("jpg", "jpeg", "png", "webp");

        // Upload main image
        $image_path = '';
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $filename = $_FILES["product_image"]["name"];
            $fileTmpName = $_FILES["product_image"]["tmp_name"];
            $fileEXT = explode(".", $filename);
            $fileActualExt = strtolower(end($fileEXT));

            if (in_array($fileActualExt, $allowed)) {
                $new_file_name = uniqid('product_', true) . '.' . $fileActualExt;
                $image_path = $upload_dir . $new_file_name;
                move_uploaded_file($fileTmpName, $image_path);
            }
        }

        // Upload additional image 1
        $image_path2 = '';
        if (isset($_FILES['product_image2']) && $_FILES['product_image2']['error'] === UPLOAD_ERR_OK) {
            $filename2 = $_FILES["product_image2"]["name"];
            $fileTmpName2 = $_FILES["product_image2"]["tmp_name"];
            $fileEXT2 = explode(".", $filename2);
            $fileActualExt2 = strtolower(end($fileEXT2));

            if (in_array($fileActualExt2, $allowed)) {
                $new_file_name2 = uniqid('product_', true) . '.' . $fileActualExt2;
                $image_path2 = $upload_dir . $new_file_name2;
                move_uploaded_file($fileTmpName2, $image_path2);
            }
        }

        // Upload additional image 2
        $image_path3 = '';
        if (isset($_FILES['product_image3']) && $_FILES['product_image3']['error'] === UPLOAD_ERR_OK) {
            $filename3 = $_FILES["product_image3"]["name"];
            $fileTmpName3 = $_FILES["product_image3"]["tmp_name"];
            $fileEXT3 = explode(".", $filename3);
            $fileActualExt3 = strtolower(end($fileEXT3));

            if (in_array($fileActualExt3, $allowed)) {
                $new_file_name3 = uniqid('product_', true) . '.' . $fileActualExt3;
                $image_path3 = $upload_dir . $new_file_name3;
                move_uploaded_file($fileTmpName3, $image_path3);
            }
        }

        $errors = [];

        if (emptyerrorsaddproduct($name, $description, $status, $category, $brand)) {
            $errors["empty_input"] = "Fill in all fields.";
        }
        if (hasNegativePriceOrStock($price, $stock)) {
            $errors["negative_value"] = "❌ Price and stock must be valid non-negative numbers.";
        }

        if ($errors) {
            $_SESSION["error_signup"] = $errors;
            header("Location: ../admin_layout.php?page=admin_add_product");
            die();
        }

        // ❗ Update insertalldetails to accept 3 images!
        $message = insertalldetails($pdo, $name, $description, $price, $stock, $status, $category, $brand, $image_path, $image_path2, $image_path3);

        if (str_starts_with($message, "✅")) {
            $_SESSION['success'] = $message;
        } else {
            $_SESSION['error_signup']['db'] = $message;
        }

        $pdo = null;
        $stmt = null;

        header("Location: ../admin_layout.php?page=admin_add_product");
        die();

    } catch (PDOException $th) {
        die("The error syntax: " . $th->getMessage());
    }

} else {
    header("Location: ../admin_layout.php?page=admin_add_product");
}
?>
