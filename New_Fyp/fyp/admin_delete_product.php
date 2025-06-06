<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = $_GET['id'];

    $stmt = $pdo->prepare("DELETE FROM 05_PRODUCT WHERE ProductID = ?");
    $stmt->execute([$product_id]);
}

header("Location: admin_layout.php?page=admin_view_products");
exit();
?>
