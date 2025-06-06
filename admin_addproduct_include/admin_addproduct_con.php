<?php
function emptyerrorsaddproduct(string $name, string $description,string $status,string $category,string $brand): bool {
    if (empty($name) || empty($description)||empty($category)|| empty($brand)  ) {
        return true; 
    }

    return false; 
}

function hasNegativePriceOrStock($price, $stock): bool {
    if (!is_numeric($price) || floatval($price) < 0) {
        return true; 
    }

    if (!is_numeric($stock) || intval($stock) < 0) {
        return true; 
    }

    return false; 
}
function insertalldetails(object $pdo,string $name,string $description,float $price,int $stock,string $status,?int $category,?int $brand,string $image_path,string $image_path2,string $image_path3){
 return insertdetail($pdo, $name, $description, $price, $stock, $status, $category, $brand, $image_path,$image_path2,$image_path3);
}



