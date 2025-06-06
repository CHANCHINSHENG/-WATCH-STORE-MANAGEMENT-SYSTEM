<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

$stmt = $pdo->prepare("SELECT o.Total_Price, c.Cust_Username, o.OrderDate AS OrderDateTime
                       FROM 07_order o
                       JOIN 02_customer c ON o.CustomerID = c.CustomerID
                       WHERE o.OrderStatus = 'Processing' AND o.Admin_Payment_Confirmation = 'Pending'
                       ORDER BY o.OrderDate DESC");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['newOrders' => $orders]);

