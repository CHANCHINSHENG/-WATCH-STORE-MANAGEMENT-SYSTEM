<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Check if the ProductID is provided in the URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = $_GET['id'];

    // Delete the product
    $stmt = $conn->prepare("DELETE FROM 05_PRODUCT WHERE ProductID = ?");
    $stmt->bind_param("i", $product_id);

    if ($stmt->execute()) {
        echo "✅ Product deleted successfully!";
    } else {
        echo "❌ Error deleting product.";
    }
} else {
    echo "❌ Invalid Product ID.";
}

// Redirect back to view products
header("Location: admin_view_products.php");
exit();
?>