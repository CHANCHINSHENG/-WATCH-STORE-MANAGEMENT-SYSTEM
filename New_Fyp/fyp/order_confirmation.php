<?php
include 'db.php';

$orderID = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($orderID <= 0) {
    echo "Invalid order ID.";
    exit();
}

$stmt = $conn->prepare("
    SELECT o.OrderID, o.Total_Price, t.Tracking_Number, t.Delivery_State
    FROM `07_order` o
    JOIN `06_tracking` t ON o.TrackingID = t.TrackingID
    WHERE o.OrderID = ?
");

$stmt->bind_param("i", $orderID);
$stmt->execute();
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    echo "Order not found.";
    exit();
}

$csvFile = fopen('delivery_date.csv', 'r');
$deliveryDays = 3; 
$customerState = $row['Delivery_State'] ?? '';

if ($csvFile !== FALSE) {
    fgetcsv($csvFile);
    while (($data = fgetcsv($csvFile)) !== FALSE) {
        if (strtolower(trim($data[0])) === strtolower(trim($customerState))) {
            $deliveryDays = intval($data[1]);
            break;
        }
    }
    fclose($csvFile);
}

$estimatedDelivery = date('d M Y', strtotime("+$deliveryDays days"));

?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmation</title>
    <link rel="stylesheet" href="style/order_confirmation.css">
</head>
<body>
    <div class="order-container">
        <h1>🎉 Order Confirmation</h1>
        <p><strong>Order ID:</strong> <?php echo htmlspecialchars($row['OrderID']); ?></p>
        <p><strong>Total Paid:</strong> RM <?php echo number_format($row['Total_Price'], 2); ?></p>
        <p><strong>Your Tracking Number:</strong> <span class="highlight"><?php echo htmlspecialchars($row['Tracking_Number']); ?></span></p>
        <p><strong>Estimated Delivery:</strong> <span class="highlight">📅 <?php echo $estimatedDelivery; ?></span></p>
        <p>Status: Your order is being prepared. You'll receive updates soon. 🛍️📦</p>
        <a href="customermainpage.php" class="back-button">🏠 Back to Home</a>
    </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
