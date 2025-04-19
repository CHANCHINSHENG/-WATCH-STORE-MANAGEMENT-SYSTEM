<?php
include 'db.php';

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("❌ Product ID is missing.");
}

$product_id = intval($_GET['id']); // Convert ID to integer for security

// Fetch product details
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
    <div class="product-detail-container">
        <!-- Product Image -->
        <div class="product-image">
            <img src="<?= htmlspecialchars($product['Product_Image']); ?>" 
                 alt="<?= htmlspecialchars($product['ProductName']); ?>">
        </div>
        
        <!-- Product Information -->
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
