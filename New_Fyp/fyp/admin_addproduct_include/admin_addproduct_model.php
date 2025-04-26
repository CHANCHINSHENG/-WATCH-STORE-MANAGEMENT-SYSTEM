<?php
    function insertdetail(object $pdo,string $name,string $description,float $price,int $stock,string $status,?int $category,?int $brand,string $image_path,string $image_path2,string $image_path3){
        try{
            $query="INSERT INTO 05_PRODUCT (ProductName, Product_Price, Product_Description, Product_Stock_Quantity, Product_Status, CategoryID, BrandID, Product_Image,Product_Image2,Product_Image3) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ? )";

        $stmt=$pdo->prepare($query);
    if (!$stmt) {
        return "❌ Failed to prepare statement.";
    }
    $stmt->execute([$name, $price, $description, $stock, $status, $category, $brand, $image_path,$image_path2,$image_path3]);
    return "✅ Product added successfully!";

    }catch(PDOException $th){
        return "❌ Error adding product: " . $th->getMessage();
    }
             
    }

    function getAllCategories(object $pdo): array {
        $stmt = $pdo->query("SELECT * FROM 04_CATEGORY");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    function getAllBrands(object $pdo): array {
        $stmt = $pdo->query("SELECT * FROM 03_BRAND");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
