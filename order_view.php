<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

require_once 'db.php';

$CustomerID = $_SESSION['customer_id'];

if (!isset($_GET['order_id'])) 
{
    echo "Invalid request.";
    exit();
}

$order_id = intval($_GET['order_id']);

$order_query = "
SELECT *, Customer_Review_Status FROM `07_order`
WHERE OrderID = ? AND CustomerID = ?
";

$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $CustomerID);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) 
{
    echo "Order not found or access denied.";
    exit();
}

$order_items = []; 
if ($order) 
{ 
    $order_id_for_items = $order['OrderID']; 

    $stmt_items = $conn->prepare
    (
        "SELECT p.ProductID, p.ProductName, p.Product_Image, od.Order_Quantity, od.Order_Subtotal, p.Product_Price
         FROM `08_order_details` od
         JOIN `05_product` p ON od.ProductID = p.ProductID
         WHERE od.OrderID = ?"
    );

    if ($stmt_items) 
    {
        $stmt_items->bind_param("i", $order_id_for_items);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();

        while ($item_row = $result_items->fetch_assoc()) 
        {
            $order_items[] = $item_row;
        }
        $stmt_items->close();
    } 
    else 
    {
        error_log("Failed to prepare statement for order items: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Details</title>
    <link href="custom_styles.css" rel="stylesheet"> 

    <style>
    body 
    {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #1a1a1a; 
        color: #e0e0e0; 
        margin: 0;
        padding: 0;
        line-height: 1.6;
    }

    .page-container 
    {
        max-width: 900px;
        margin: 60px auto; 
        padding: 0 25px;
    }

    .order-section 
    {
        background-color: #282828; 
        padding: 35px; 
        border-radius: 12px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.4);
        display: flex; 
        flex-direction: column; 
        gap: 30px;
    }

    .order-header-info 
    {
        padding-bottom: 25px;
        border-bottom: 1px solid #444; 
        display: flex;
        flex-direction: column;
        align-items: flex-start; 
    }

    .order-section h2 
    {
        color: #4CAF50; 
        font-size: 2.8em; 
        margin-bottom: 20px; 
        padding-bottom: 10px;
    }

    .order-header-info p 
    {
        margin-bottom: 8px; 
        font-size: 1.05em;
    }

    .order-header-info p strong 
    {
        color: #B0BEC5; 
    }

    .shipping-info-title,
    .order-items-title 
    {
        color: #64B5F6; 
        font-size: 1.6em;
        margin-top: 0; 
        margin-bottom: 15px; 
        position: relative;
        padding-left: 10px;
    }

    .shipping-info-title::before,
    .order-items-title::before 
    {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        height: 60%;
        width: 4px; 
        background-color: #64B5F6;
        border-radius: 2px;
    }

    .shipping-address-block 
    {
        padding-bottom: 25px;
        border-bottom: 1px solid #444; 
        text-align: left; 
    }

    .shipping-address-block p 
    {
        margin-bottom: 5px;
    }


    .order-items-section 
    {
        padding-bottom: 25px;
    }

    .order-items-list 
    {
        margin-top: 20px;
    }

    .order-item-card 
    {
        background-color: #333333;
        border: 1px solid #444444;
        border-radius: 8px;
        margin-bottom: 15px;
        padding: 18px 22px;
        display: flex;
        align-items: center;
        gap: 25px;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    .order-item-card:hover 
    {
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
    }

    .product-thumbnail 
    {
        width: 90px;
        height: 90px;
        object-fit: contain;
        background-color: #f0f0f0;
        border-radius: 6px;
        padding: 5px;
        border: 1px solid #555;
        flex-shrink: 0;
    }

    .item-details 
    {
        flex-grow: 1;
    }

    .product-name 
    {
        color: #FFD700;
        font-size: 1.3em;
        margin-top: 0;
        margin-bottom: 8px;
        font-weight: bold;
    }

    .item-quantity,
    .item-subtotal 
    {
        font-size: 0.95em;
        color: #c0c0c0;
        margin-bottom: 5px;
    }

    .item-subtotal 
    {
        font-weight: 600;
        color: #FFEB3B;
    }

    .no-items-message 
    {
        color: #9E9E9E;
        text-align: center;
        padding: 30px;
        background-color: #333333;
        border-radius: 8px;
        margin-top: 20px;
    }

    .back-button 
    {
        display: inline-block;
        background-color: #4CAF50;
        color: #ffffff;
        border: none;
        padding: 12px 25px;
        border-radius: 6px;
        text-decoration: none;
        margin-top: 20px;
        align-self: center;
        font-size: 1.1em;
        font-weight: bold;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .back-button:hover 
    {
        background-color: #43A047;
        transform: translateY(-2px);
    }

    .back-button:active 
    {
        transform: translateY(0);
    }

    a 
    {
        color: #64B5F6;
        text-decoration: none;
    }

    a:hover 
    {
        text-decoration: underline;
    }

    .review-section 
    {
        background-color: #333333;
        border: 1px solid #444444; 
        border-radius: 8px;
        padding: 20px;
        text-align: center; 
        margin-top: 20px;
    }

    .review-prompt 
    {
        font-size: 1.1em;
        margin-bottom: 15px;
        color: #e0e0e0;
    }

    .review-button 
    {
        display: inline-block;
        background-color: #007bff;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: bold;
        transition: background-color 0.3s ease;
    }

    .review-button:hover {
        background-color: #0056b3;
    }
    </style>
</head>
<body>
<div class="page-container">
    <div class="order-section">

        <div class="order-header-info">
            <h2>Order #<?= $order['OrderID'] ?></h2>
            <p><strong>Date:</strong> <?= $order['OrderDate'] ?></p>

            <?php
            $display_status = '';
            if (isset($order['Admin_Payment_Confirmation']) && $order['Admin_Payment_Confirmation'] === 'Confirmed') 
            {
                $display_status = 'Shipped'; 
            } 
            else 
            {
                $display_status = $order['OrderStatus'] ?? 'Unknown'; 
            }
            ?>
            <p><strong>Status:</strong> <?= htmlspecialchars($display_status) ?></p>
            <p><strong>Total:</strong> RM<?= number_format($order['Total_Price'], 2) ?></p>
        </div> 

        <h4 class="shipping-info-title">Shipping Information</h4>
        <div class="shipping-address-block">
            <p><?= htmlspecialchars($order['Shipping_Name']) ?></p>
            <p><?= htmlspecialchars($order['Shipping_Address']) ?></p>
            <p><?= htmlspecialchars($order['Shipping_Postcode'] . ' ' . $order['Shipping_City']) ?></p>
            <p><?= htmlspecialchars($order['Shipping_State']) ?></p>
            <p>Phone : <?= htmlspecialchars($order['Shipping_Phone']) ?></p>
        </div> 

        <div class="order-items-section">
            <h4 class="order-items-title">Items in this Order:</h4> 
            <?php if (!empty($order_items)): ?>
                <div class="order-items-list">
                    <?php foreach ($order_items as $item): ?>
                        <div class="order-item-card">
                            <div class="order-item-content">
                                <img src="<?= htmlspecialchars($item['Product_Image']); ?>" 
                                    alt="<?= htmlspecialchars($item['ProductName']); ?>" 
                                    class="product-thumbnail">
                                <div class="item-details">
                                    <h5 class="product-name"><?= htmlspecialchars($item['ProductName']); ?></h5>
                                    <p class="item-quantity">Quantity: <?= htmlspecialchars($item['Order_Quantity']); ?></p>
                                    <?php 
                                        $item_total_price = $item['Order_Subtotal'] ?? ($item['Product_Price'] * $item['Order_Quantity']);
                                    ?>
                                    <p class="item-subtotal">Subtotal for item: RM <?= number_format($item_total_price, 2); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-items-message">No items found for this order or details are currently unavailable.</p> 
            <?php endif; ?>
        </div> 

        <?php if ($display_status === 'Shipped'):?>
            <?php if ($order['Customer_Review_Status'] === 'Eligible'): ?>
                <div class="review-section">
                    <p class="review-prompt">Your order has been shipped! We'd love to hear your feedback!</p>
                    <a href="submit_review.php?order_id=<?= $order['OrderID'] ?>" class="review-button">Submit Review</a>
                </div>
            <?php elseif ($order['Customer_Review_Status'] === 'Reviewed'): ?>
                <div class="review-section">
                    <p class="review-prompt">Thank you for your review! You can view it <a href="Submit_Review.php?order_id=<?= $order['OrderID'] ?>">here</a>.</p>
                </div>  
            <?php endif; ?>
        <?php endif; ?>

        <a href="customer_profile.php" class="back-button">← Back to Dashboard</a>
    </div>
</div>
</body>
</html>