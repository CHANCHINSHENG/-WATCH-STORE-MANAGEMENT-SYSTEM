<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

require_once 'db.php';

$CustomerID = $_SESSION['customer_id'];

// 获取订单ID
if (!isset($_GET['order_id'])) {
    echo "Invalid request.";
    exit();
}

$order_id = intval($_GET['order_id']);

// 获取订单信息，验证归属与状态（不能取消已发货或已完成的订单）
$order_query = "SELECT * FROM `07_order` WHERE OrderID = ? AND CustomerID = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $CustomerID);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo "Order not found or access denied.";
    exit();
}

if ($order['OrderStatus'] === 'Shipped' || $order['OrderStatus'] === 'Delivered' || $order['OrderStatus'] === 'Cancelled') {
    echo "This order cannot be cancelled.";
    exit();
}

// 执行取消（更新订单状态为 Cancelled）
$cancel_query = "UPDATE `07_order` SET OrderStatus = 'Cancelled' WHERE OrderID = ? AND CustomerID = ?";
$cancel_stmt = $conn->prepare($cancel_query);
$cancel_stmt->bind_param("ii", $order_id, $CustomerID);

if ($cancel_stmt->execute()) {
    header("Location: order_view.php?order_id=" . $order_id);
    exit();
} else {
    echo "Failed to cancel order.";
}
?>
