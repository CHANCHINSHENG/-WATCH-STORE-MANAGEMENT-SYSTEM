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

// 验证该订单是否属于当前用户
$order_query = "
SELECT * FROM `07_order`
WHERE OrderID = ? AND CustomerID = ?
";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $CustomerID);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo "Order not found or access denied.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #121212; color: #f1f1f1; }
        .container { margin-top: 50px; }
        .section { background-color: #1e1e1e; padding: 20px; border-radius: 10px; }
        a { color: #0dcaf0; }
    </style>
</head>
<body>
<div class="container">
    <div class="section">
        <h2>Order #<?= $order['OrderID'] ?></h2>
        <p><strong>Date:</strong> <?= $order['OrderDate'] ?></p>
        <p><strong>Status:</strong> <?= $order['OrderStatus'] ?></p>
        <p><strong>Total:</strong> $<?= number_format($order['Total_Price'], 2) ?></p>

        <h4 class="mt-4">Shipping Information</h4>
        <p><?= htmlspecialchars($order['Shipping_Name']) ?></p>
        <p><?= htmlspecialchars($order['Shipping_Address']) ?></p>
        <p><?= htmlspecialchars($order['Shipping_Postcode'] . ' ' . $order['Shipping_City']) ?></p>
        <p><?= htmlspecialchars($order['Shipping_State']) ?></p>
        <p>Phone: <?= htmlspecialchars($order['Shipping_Phone']) ?></p>

        <a href="customer_profile.php" class="btn btn-outline-light mt-4">← Back to Dashboard</a>
    </div>
</div>
</body>
</html>
