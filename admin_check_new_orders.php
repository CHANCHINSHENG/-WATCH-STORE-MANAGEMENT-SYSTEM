<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

$stmt = $pdo->prepare("
    SELECT o.OrderID, o.Total_Price, o.OrderDate AS OrderDateTime,
           c.Cust_Username, c.CustomerID
    FROM 08_order o
    JOIN 02_customer c ON o.CustomerID = c.CustomerID
    WHERE o.OrderStatus = 'Processing' AND o.Admin_Payment_Confirmation = 'Pending'
    ORDER BY o.OrderDate DESC, o.OrderID DESC
");


$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['newOrders' => $orders]);
