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

function getAllCategories($pdo) {
    $stmt = $pdo->query("SELECT * FROM 04_category");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllBrands($pdo) {
    $stmt = $pdo->query("SELECT * FROM 03_brand");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProductImages($pdo, $product_id) {
    $stmt = $pdo->prepare("SELECT ImagePath FROM 06_product_images WHERE ProductID = ? ORDER BY IsPrimary DESC, ImageOrder ASC");
    $stmt->execute([$product_id]);
    $result = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $images = [null, null, null];
    foreach ($result as $i => $imgPath) {
        if ($i < 3) {
            $images[$i] = $imgPath;
        }
    }
    return $images;
}




