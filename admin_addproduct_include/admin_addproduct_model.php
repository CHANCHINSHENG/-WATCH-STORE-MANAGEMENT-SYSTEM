<?php
   function insertProductOnly($pdo, $name, $desc, $price, $stock, $status, $category, $brand) {
    $sql = "INSERT INTO 05_product 
           (ProductName, Product_Description, Product_Price, Product_Stock_Quantity, Product_Status, CategoryID, BrandID) 
           VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $desc, $price, $stock, $status, $category, $brand]);
    return $pdo->lastInsertId();
}

function insertProductImage($pdo, $productId, $imagePath, $isPrimary = false, $imageOrder = 1) {
    $sql = "INSERT INTO 06_product_images (ProductID, ImagePath, IsPrimary, ImageOrder) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$productId, $imagePath, $isPrimary, $imageOrder]);
}


    function getAllCategories(object $pdo): array {
        $stmt = $pdo->query("SELECT * FROM 04_CATEGORY");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    function getAllBrands(object $pdo): array {
        $stmt = $pdo->query("SELECT * FROM 03_BRAND");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
