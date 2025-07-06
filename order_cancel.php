<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

require_once 'db.php';

$CustomerID = $_SESSION['customer_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid order ID']);
    exit();
}

// get order
$order_query = "SELECT * FROM `08_order` WHERE OrderID = ? AND CustomerID = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $CustomerID);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo json_encode(['status' => 'error', 'message' => 'Order not found']);
    exit();
}

// reject if processed
if ($order['OrderStatus'] === 'Cancelled') {
    echo json_encode(['status' => 'error', 'message' => '❌ This order is already cancelled.']);
    exit();
} elseif ($order['OrderStatus'] === 'Done Processing' || $order['OrderStatus'] === 'Delivered') {
    echo json_encode(['status' => 'error', 'message' => '❌ This order already delivered.']);
    exit();
}

// to cancel
$cancel_query = "UPDATE `08_order` SET OrderStatus = 'Cancelled' WHERE OrderID = ? AND CustomerID = ?";
$cancel_stmt = $conn->prepare($cancel_query);
$cancel_stmt->bind_param("ii", $order_id, $CustomerID);

if ($cancel_stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => '✅ Order cancelled successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => '❌ Failed to cancel order.']);
}
