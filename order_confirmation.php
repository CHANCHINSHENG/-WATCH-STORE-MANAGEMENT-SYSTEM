<?php
include 'db.php'; 

$orderID = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orderID <= 0) 
{
    echo "Invalid order ID.";
    exit();
}

$stmt_order = $conn->prepare
("
    SELECT o.OrderID, o.OrderDate, o.Total_Price, t.Tracking_Number, t.Delivery_State, t.TrackingID
    FROM `08_order` o
    JOIN `07_tracking` t ON o.TrackingID = t.TrackingID
    WHERE o.OrderID = ?
");

if ($stmt_order === false) 
{
    error_log("Failed to prepare order statement: " . $conn->error); 
    echo "An error occurred while fetching your order details. Please try again later."; 
    exit();
}

$stmt_order->bind_param("i", $orderID);
$stmt_order->execute();
$result_order = $stmt_order->get_result();

if (!$order_row = $result_order->fetch_assoc()) 
{
    echo "Order not found.";
    $stmt_order->close(); 
    $conn->close();      
    exit();
}

$products = [];
$stmt_products = $conn->prepare
("
    SELECT p.ProductName, p.Product_Image, od.Order_Quantity
    FROM `09_order_details` od
    JOIN `05_product` p ON od.ProductID = p.ProductID
    WHERE od.OrderID = ?
");

if ($stmt_products === false) 
{
    error_log("Failed to prepare product statement: " . $conn->error);
} 
else 
{
    $stmt_products->bind_param("i", $orderID);
    $stmt_products->execute();
    $result_products = $stmt_products->get_result();

    while ($product_item = $result_products->fetch_assoc()) 
    {
        $products[] = $product_item; 
    }
    $stmt_products->close(); 
}


$csvFilePath = 'delivery_date.csv'; 
$defaultDeliveryDays = 3;       
$deliveryDays = $defaultDeliveryDays;  
$customerState = $order_row['Delivery_State'] ?? ''; 

if (file_exists($csvFilePath)) 
{
    if (($csvFile = fopen($csvFilePath, 'r')) !== FALSE) 
    {
        fgetcsv($csvFile); 

        while (($data = fgetcsv($csvFile)) !== FALSE) 
        {
            if (isset($data[0]) && isset($data[1])) 
            {
                if (strtolower(trim($data[0])) === strtolower(trim($customerState))) 
                {
                    $parsedDays = intval($data[1]);

                    if ($parsedDays > 0) 
                    {
                        $deliveryDays = $parsedDays;
                    }
                    break; 
                }
            }
        }
        fclose($csvFile);
    } 
    else
    {
        error_log("Could not open CSV file: " . $csvFilePath);
    }
} 
else 
{
    error_log("CSV file not found: " . $csvFilePath);
}

$orderDate = $order_row['OrderDate'] ?? 'now'; 
$estimatedDelivery = date('Y-m-d', strtotime($orderDate . " +$deliveryDays days"));

$trackingID = $order_row['TrackingID'];

if ($trackingID > 0) 
{
    $update_tracking_query = "UPDATE `07_tracking` SET `EstimatedDeliveryDate` = ? WHERE `TrackingID` = ?";
    $stmt_update_tracking = $conn->prepare($update_tracking_query);

    if ($stmt_update_tracking === false) 
    {
        error_log("Failed to prepare update tracking statement: " . $conn->error);
    } 
    else 
    {
        $stmt_update_tracking->bind_param("si", $estimatedDelivery, $trackingID); 
        if (!$stmt_update_tracking->execute()) 
        {
            error_log("Failed to execute update tracking statement: " . $stmt_update_tracking->error);
        }
        $stmt_update_tracking->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmation</title>
    <link rel="stylesheet" href="order_confirmation.css">
</head>

<body>
    <div class="confirmation-page-container"> <div class="order-container"> <h1 class="confirmation-header">Order Confirmation</h1> <p class="thank-you-message">Thank you for your purchase!</p>

            <div class="order-details-section">
                <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order_row['OrderID']); ?></p>
                <p><strong>Total Paid:</strong> RM <?php echo number_format($order_row['Total_Price'], 2); ?></p>
            </div>

            <?php if (!empty($products)): ?>
                <div class="products-list">
                    <h2>Items Purchased:</h2>
                    <?php foreach ($products as $product): ?>
                        <div class="product-item">
                            <img src="admin_addproduct_include/<?php echo htmlspecialchars($product['Product_Image']); ?>" alt="<?php echo htmlspecialchars($product['ProductName']); ?>" class="product-image-confirm"> <div class="product-item-details">
                                <h4><?php echo htmlspecialchars($product['ProductName']); ?></h4>
                                <p>Quantity: <?php echo htmlspecialchars($product['Order_Quantity']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($stmt_products !== false): ?>
                <p>No items found for this order.</p>
            <?php else: ?>
                <p>Details of items purchased are currently unavailable due to an error.</p>
            <?php endif; ?>

            <div class="tracking-delivery-section">
                <p><strong>Your Tracking Number:</strong> <span class="highlight-gold"><?php echo htmlspecialchars($order_row['Tracking_Number']); ?></span></p> 
                <p><strong>Estimated Delivery:</strong> <span class="highlight-gold">📅 <?php echo date('d M Y', strtotime($estimatedDelivery)); ?></span></p>            
            </div>

            <p class="status-message">Status: Your order is being prepared. You'll receive updates soon. 🛍️📦</p>
            <a href="customermainpage.php" class="action-button back-to-home-button">🏠 Back to Home</a> </div>
    </div>
</body>
</html>

<?php
$stmt_order->close(); 
$conn->close(); 
?>