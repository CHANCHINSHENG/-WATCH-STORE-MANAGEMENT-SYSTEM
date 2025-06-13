<?php
require_once 'admin_login_include/db.php';

if (!isset($_GET['oid']) || !is_numeric($_GET['oid'])) {
    die("Invalid or missing order ID");
}

$orderId = (int)$_GET['oid'];
$limit = 10;

$orderIds = $pdo->query("SELECT OrderID FROM 07_order ORDER BY OrderDate DESC")->fetchAll(PDO::FETCH_COLUMN);
$index = array_search($orderId, $orderIds);
$page = floor($index / $limit) + 1;

header("Location: admin_layout.php?page=admin_view_allorder&pagenum=$page#order$orderId");
exit();
