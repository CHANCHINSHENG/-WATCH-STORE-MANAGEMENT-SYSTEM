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




