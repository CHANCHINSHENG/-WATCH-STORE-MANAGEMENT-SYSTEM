<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';


if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Check if the ProductID is provided in the URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = $_GET['id'];

    // Delete the product
    $stmt = $pdo->prepare("DELETE FROM 05_PRODUCT WHERE ProductID = ?");
    $stmt->bindparam("i", $product_id);

    if ($stmt->execute([$product_id])) {
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