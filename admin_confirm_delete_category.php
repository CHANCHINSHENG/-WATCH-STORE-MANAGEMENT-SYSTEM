<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_id']) && is_numeric($_POST['category_id'])) {
    $category_id = $_POST['category_id'];

    $productStmt = $pdo->prepare("SELECT ProductID FROM 05_product WHERE CategoryID = ?");
    $productStmt->execute([$category_id]);
    $productIDs = $productStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($productIDs)) {
        $in = str_repeat('?,', count($productIDs) - 1) . '?';
        $deleteDetails = $pdo->prepare("DELETE FROM 08_order_details WHERE ProductID IN ($in)");
        $deleteDetails->execute($productIDs);

    
        $deleteProducts = $pdo->prepare("DELETE FROM 05_product WHERE CategoryID = ?");
        $deleteProducts->execute([$category_id]);
    }

    $deleteCategory = $pdo->prepare("DELETE FROM 04_category WHERE CategoryID = ?");
    $deleteCategory->execute([$category_id]);
}


header("Location: admin_layout.php?page=admin_view_category");
exit();
