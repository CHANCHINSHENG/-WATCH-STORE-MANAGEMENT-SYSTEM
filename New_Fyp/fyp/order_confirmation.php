<?php

include 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

$customerID = $_SESSION['customer_id'];
$orderID = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orderID <= 0) 
{
    echo "Invalid order ID.";
    exit();
}

$query = "
    SELECT o.OrderID, o.Total_Price, t.Tracking_Number
    FROM `07_order` o
    JOIN `06_tracking` t ON o.TrackingID = t.TrackingID
    WHERE o.OrderID = $orderID AND o.CustomerID = $customerID
";
$result = $conn->query($query);

if ($row = $result->fetch_assoc()) 
{
    echo "<h1>Order Confirmation</h1>";
    echo "<p><strong>Order ID:</strong> {$row['OrderID']}</p>";
    echo "<p><strong>Total Paid:</strong> RM " . number_format($row['Total_Price'], 2) . "</p>";
    echo "<p><strong>Your Tracking Number:</strong> <span style='color:blue'>{$row['Tracking_Number']}</span></p>";
    echo "<p>Status: Your order is being prepared. You'll receive updates soon.</p>";
} 
else 
{
    echo "Order not found.";
}

?>
