<?php
function emptyerrorsaddproduct(string $name, string $description, string $status, string $category, string $brand): bool {
    return empty($name) || empty($description) || empty($category) || empty($brand);
}

function hasNegativePriceOrStock($price, $stock): bool {
    return (!is_numeric($price) || floatval($price) < 0) || (!is_numeric($stock) || intval($stock) < 0);
}

function shouldUpdateProduct(array $original, string $name, string $description, float $price, int $stock, string $status): bool {
    return (
        $name !== $original['ProductName'] ||
        $description !== $original['Product_Description'] ||
        $price != $original['Product_Price'] ||
        $stock != $original['Product_Stock_Quantity'] ||
        $status !== $original['Product_Status']
    );
}

function updateproductdetails($pdo, $product_id, $name, $description, $price, $stock, $status): bool {
    return updateProductDetails($pdo, $product_id, $name, $description, $price, $stock, $status);
}
