<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Check if product ID is set
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "❌ Invalid Product ID.";
    exit();
}

$product_id = $_GET['id'];

// Fetch existing product details
$stmt = $conn->prepare("SELECT * FROM PRODUCT WHERE ProductID = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "❌ Product not found.";
    exit();
}

$product = $result->fetch_assoc();
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['ProductName'];
    $description = $_POST['Product_Description'];
    $price = $_POST['Product_Price'];
    $stock = $_POST['Product_Stock_Quantity'];
    $status = $_POST['Product_Status'];

    // Check if changes were made
    if (
        $name !== $product['ProductName'] ||
        $description !== $product['Product_Description'] ||
        $price != $product['Product_Price'] ||
        $stock != $product['Product_Stock_Quantity'] ||
        $status !== $product['Product_Status']
    ) {
        $stmt = $conn->prepare("UPDATE PRODUCT SET ProductName=?, Product_Price=?, Product_Description=?, Product_Stock_Quantity=?, Product_Status=? WHERE ProductID=?");
        $stmt->bind_param("sdsisi", $name, $price, $description, $stock, $status, $product_id);

        if ($stmt->execute()) {
            $success_message = "✅ Product updated successfully!";
            // Refresh the product data after update
            $stmt = $conn->prepare("SELECT * FROM PRODUCT WHERE ProductID = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
        } else {
            echo "❌ Error updating product.";
        }
    }
}
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

        <!-- ✅ Show success message inline -->
        <?php if ($success_message) { ?>
            <p class="success-message"><?= $success_message; ?></p>
        <?php } ?>

        <form method="POST">
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
                <input type="number" name="Product_Price" step="0.01" value="<?= $product['Product_Price']; ?>" required>
            </div>

            <div class="input-group">
                <label>Stock Quantity</label>
                <input type="number" name="Product_Stock_Quantity" value="<?= $product['Product_Stock_Quantity']; ?>" required>
            </div>

            <div class="input-group">
                <label>Status</label>
                <select name="Product_Status" required>
                    <option value="Available" <?= ($product['Product_Status'] == "Available") ? "selected" : ""; ?>>Available</option>
                    <option value="Out of Stock" <?= ($product['Product_Status'] == "Out of Stock") ? "selected" : ""; ?>>Out of Stock</option>
                </select>
            </div>

            <button type="submit">Update Product</button>
        </form>
    </div>
</body>
</html>
