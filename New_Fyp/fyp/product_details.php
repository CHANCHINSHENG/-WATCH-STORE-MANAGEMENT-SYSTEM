<?php
session_start();
require_once 'db.php';

if (isset($_SESSION['customer_id']) && isset($_GET['id'])) {
    $customerID = $_SESSION['customer_id'];
    $productID = intval($_GET['id']);

    // 插入浏览记录
    $stmt = $conn->prepare("INSERT INTO `15_view_history` (CustomerID, ProductID, ViewTime) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $customerID, $productID);
    $stmt->execute();
}


if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("❌ Product ID is missing.");
}

$product_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM 05_product WHERE ProductID = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    die("❌ Product not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['ProductName']); ?> - TIGO</title>
    <link rel="stylesheet" href="product_details.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="top-nav">
        <button onclick="window.location.href='customermainpage.php'">🏠 Home</button>
        <button onclick="history.back()">🔙 Back</button>
    </nav>

    <div class="product-detail-container">
        <div class="product-image">
            <img src="<?= htmlspecialchars($product['Product_Image']); ?>" 
                 alt="<?= htmlspecialchars($product['ProductName']); ?>">
        </div>

        <div class="product-info">
            <h1><?= htmlspecialchars($product['ProductName']); ?></h1>
            <p class="product-price">RM <?= number_format($product['Product_Price'], 2); ?></p>
            <p class="product-description"><?= htmlspecialchars($product['Product_Description']); ?></p>
            <p class="product-stock">Stock: <?= $product['Product_Stock_Quantity']; ?></p>
            <button class="add-to-cart-btn">Add to Cart</button>
        </div>
    </div>
</body>
</html>
