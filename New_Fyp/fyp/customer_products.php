<?php
session_start();
include 'db.php';

$result = $conn->query("SELECT * FROM 05_product WHERE Product_Status = 'Available'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shop - Available Products</title>
    <link rel="stylesheet" href="customer_products.css"> 
</head>
<body>
    <div class="page-wrapper">
        <div class="product-page-container">
            <div class="product-page-header">
                <h1>TOP PICKS</h1>
                <li class="nav-item px-2"><a class="nav-link fw-bold" href="cart.php"><img src="img/Cart icon.png" alt="Cart" style="width:24px; height:24px;"></a></li>
                <h3>Discover our latest collection of premium watches</h3>
            </div>

            <div class="product-grid">
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <div class="product-card">
                        <a href="product_details.php?id=<?= $row['ProductID']; ?>" class="product-link">
                            <?php if (!empty($row['Product_Image']) && file_exists($row['Product_Image'])) { ?>
                                <img src="<?= htmlspecialchars($row['Product_Image']); ?>" 
                                     alt="<?= htmlspecialchars($row['ProductName']); ?>" 
                                     style="width: 100%; height: auto; max-height: 200px; border-radius: 12px; object-fit: cover; margin-bottom: 1rem;">
                            <?php } else { ?>
                                <div style="width: 100%; height: 200px; background: #f0f0f0; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #888; margin-bottom: 1rem;">
                                    No Image
                                </div>
                            <?php } ?>

                            <h3><?= htmlspecialchars($row['ProductName']); ?></h3>
                            <p><?= htmlspecialchars($row['Product_Description']); ?></p>
                            <p class="product-price">Price: RM <?= number_format($row['Product_Price'], 2); ?></p>
                            <p>Stock: <?= $row['Product_Stock_Quantity']; ?></p>
                        </a>

                        <form action="add_to_cart.php" method="post">
                            <input type="hidden" name="product_id" value="<?= $row['ProductID']; ?>">
                            <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                        </form>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</body>
</html>