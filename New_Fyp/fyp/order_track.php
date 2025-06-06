<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

require_once 'db.php';

$CustomerID = $_SESSION['customer_id'];

// 检查是否有传入订单 ID
if (!isset($_GET['order_id'])) {
    echo "Invalid request.";
    exit();
}

$order_id = intval($_GET['order_id']);

$order_query = "SELECT TrackingID, OrderDate FROM `07_order` WHERE OrderID = ? AND CustomerID = ?"; // <-- 修改这里：添加 OrderDate
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $CustomerID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Order not found or access denied.";
    exit();
}

$order = $result->fetch_assoc();
$tracking_id = $order['TrackingID'];
$order_date = $order['OrderDate']; // <-- 获取订单日期

// 获取追踪信息
$track_query = "SELECT * FROM `06_tracking` WHERE TrackingID = ?";
$track_stmt = $conn->prepare($track_query);
$track_stmt->bind_param("i", $tracking_id);
$track_stmt->execute();
$track = $track_stmt->get_result()->fetch_assoc();

if (!$track) {
    echo "Tracking information not found.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Track Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #121212; color: #f1f1f1; }
        .container { max-width: 700px; margin-top: 50px; }
        .card { background-color: #1e1e1e; border: none; }
        .card-title { color: #fff; }
        .card-text { color: #ccc; }
        .highlight-gold { color: #FFD700; font-weight: bold; } 
    </style>
</head>
<body>
<div class="container">
    <div class="card p-4">
        <h2 class="card-title mb-4">Tracking Information</h2>
        <p class="card-text"><strong>Tracking Number:</strong> <?= htmlspecialchars($track['Tracking_Number']) ?></p>
        <p class="card-text"><strong>Status:</strong> <?= htmlspecialchars($track['Delivery_Status']) ?></p>

        <?php
            $displayEstimatedDelivery = "N/A"; 
            if (!empty($track['EstimatedDeliveryDate']) && $track['EstimatedDeliveryDate'] !== '0000-00-00') 
            {
                $displayEstimatedDelivery = date('d M Y', strtotime($track['EstimatedDeliveryDate']));
            }
        ?>
        
        <p class="card-text"><strong>Estimated Delivery:</strong> <span class="highlight-gold">📅 <?= $displayEstimatedDelivery; ?></span></p>
        <p class="card-text"><strong>Address:</strong><br>
            <?= htmlspecialchars($track['Delivery_Address']) ?><br>
            <?= htmlspecialchars($track['Delivery_Postcode']) ?> <?= htmlspecialchars($track['Delivery_City']) ?><br>
            <?= htmlspecialchars($track['Delivery_State']) ?>
        </p>
        <a href="customer_profile.php" class="btn btn-outline-light mt-3">Back to Dashboard</a>
    </div>
</div>
</body>
</html>