<?php
require_once 'admin_login_include/db.php';

if (!isset($_GET['cid']) || !ctype_digit($_GET['cid'])) {
    die("Invalid or missing customer ID");
}

$customerId = (int)$_GET['cid'];
$limit = 10; // 每頁10筆

// 1. 找出該顧客最新訂單
$stmt = $pdo->prepare("SELECT OrderID FROM 07_order WHERE CustomerID = ? ORDER BY OrderDate DESC LIMIT 1");
$stmt->execute([$customerId]);
$orderId = $stmt->fetchColumn();

if (!$orderId) {
    header("Location: admin_layout.php?page=admin_view_allorder");
    exit();
}

// 2. 找出這筆訂單在第幾筆
$orderIds = $pdo->query("SELECT OrderID FROM 07_order ORDER BY OrderDate DESC")->fetchAll(PDO::FETCH_COLUMN);
$index = array_search($orderId, $orderIds);
$page = floor($index / $limit) + 1;

// 3. 轉去正確頁碼 + anchor
header("Location: admin_layout.php?page=admin_view_allorder&pagenum=$page#order$orderId");
exit();
