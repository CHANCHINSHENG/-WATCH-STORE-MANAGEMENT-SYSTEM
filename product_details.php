<?php
session_start();
require_once 'db.php';

if (isset($_SESSION['customer_id']) && isset($_GET['id'])) {
    $customerID = $_SESSION['customer_id'];
    $productID = intval($_GET['id']);

    $stmt = $conn->prepare("INSERT INTO `15_view_history` (CustomerID, ProductID, ViewTime) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $customerID, $productID);
    $stmt->execute();
}


if (!isset($_GET['id']) || empty($_GET['id'])) 
{
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
    <nav class="top-nav">
        <button onclick="window.location.href='customermainpage.php'">🏠 Home</button>
        <button onclick="history.back()">🔙 Back</button>
    </nav>

    <div class="product-detail-container">
        <div class="product-gallery">
        <div class="main-image-container">
            <button class="arrow left" onclick="prevImage()">&#10094;</button>
            <img id="mainImage" src="admin_addproduct_include/<?= htmlspecialchars($product['Product_Image']); ?>" alt="Main Image">
            <button class="arrow right" onclick="nextImage()">&#10095;</button>
        </div>

        <div class="thumbnail-container">
            <?php if (!empty($product['Product_Image'])): ?>
                <img class="thumbnail" src="admin_addproduct_include/<?= htmlspecialchars($product['Product_Image']); ?>" onclick="showImage(0)">
            <?php endif; ?>
            <?php if (!empty($product['Product_Image2'])): ?>
                <img class="thumbnail" src="admin_addproduct_include/<?= htmlspecialchars($product['Product_Image2']); ?>" onclick="showImage(1)">
            <?php endif; ?>
            <?php if (!empty($product['Product_Image3'])): ?>
                <img class="thumbnail" src="admin_addproduct_include/<?= htmlspecialchars($product['Product_Image3']); ?>" onclick="showImage(2)">
            <?php endif; ?>
        </div>
    </div>
    <script>
    const images = [
        <?= json_encode($product['Product_Image']); ?>,
        <?= json_encode($product['Product_Image2']); ?>,
        <?= json_encode($product['Product_Image3']); ?>
    ].filter(img => img);

    let currentIndex = 0;

    function showImage(index) {
    currentIndex = index;
    document.getElementById("mainImage").src = "admin_addproduct_include/" + images[currentIndex];
}


    function prevImage() {
        currentIndex = (currentIndex - 1 + images.length) % images.length;
        showImage(currentIndex);
    }

    function nextImage() {
        currentIndex = (currentIndex + 1) % images.length;
        showImage(currentIndex);
    }
</script>

        <div class="product-info">
            <h1><?= htmlspecialchars($product['ProductName']); ?></h1>
            <p class="product-price">RM <?= number_format($product['Product_Price'], 2); ?></p>
            <p class="product-description"><?= nl2br(htmlspecialchars($product['Product_Description'])); ?></p>
            <p class="product-stock">Stock: <?= $product['Product_Stock_Quantity']; ?></p>
            <button class="add-to-cart-btn">Add to Cart</button>
        </div>
    </div>
</body>
</html>