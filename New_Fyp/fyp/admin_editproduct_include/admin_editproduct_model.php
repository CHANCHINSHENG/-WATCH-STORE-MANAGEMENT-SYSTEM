<?php
function getProductById($pdo, $product_id) {
    $stmt = $pdo->prepare("
        SELECT 
            p.*, 
            c.CategoryName, 
            b.BrandName 
        FROM 05_product p
        LEFT JOIN 04_category c ON p.CategoryID = c.CategoryID
        LEFT JOIN 03_brand b ON p.BrandID = b.BrandID
        WHERE p.ProductID = ?
    ");
    $stmt->execute([$product_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateProduct(PDO $pdo, $id, $name, $description, $price, $stock, $status): bool {
    $stmt = $pdo->prepare("UPDATE 05_product 
        SET ProductName = ?, Product_Description = ?, Product_Price = ?, Product_Stock_Quantity = ?, Product_Status = ?
        WHERE ProductID = ?");
    return $stmt->execute([$name, $description, $price, $stock, $status, $id]);
}
