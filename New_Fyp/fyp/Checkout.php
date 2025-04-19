<?php
session_start();
include 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

$customerID = $_SESSION['customer_id'];
$cartID = null;
$cart_items = [];
$subtotal = 0;
$shipping_fee = 0;
$total = 0;
$error = "";

// Get CartID
$stmt = $conn->prepare("SELECT CartID FROM `11_cart` WHERE CustomerID = ?");
$stmt->bind_param("i", $customerID);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $cartID = $row['CartID'];
} else {
    $error = "Cart not found.";
}

// Get Cart Items
if ($cartID) {
    $stmt = $conn->prepare("
        SELECT p.ProductID, p.ProductName, p.Product_Price, ci.Quantity
        FROM `12_cart_item` ci
        JOIN `05_product` p ON ci.ProductID = p.ProductID
        WHERE ci.CartID = ?
    ");
    $stmt->bind_param("i", $cartID);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $row['Subtotal'] = $row['Product_Price'] * $row['Quantity'];
        $subtotal += $row['Subtotal'];
        $cart_items[] = $row;
    }

    $shipping_fee = ($subtotal >= 100) ? 0 : 10;
    $total = $subtotal + $shipping_fee;
}

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $name     = trim($_POST['name']);
    $address  = trim($_POST['address']);
    $city     = trim($_POST['city']);
    $postcode = trim($_POST['postcode']);
    $state    = trim($_POST['state']);
    $phone    = trim($_POST['phone']);
    $payment_method = trim($_POST['payment_method']);
    $card_number = trim($_POST['card_number'] ?? '');
    $card_bank = trim($_POST['card_bank'] ?? '');

    if (!$name || !$address || !$city || !$postcode || !$state || !$phone || !$payment_method) {
        $error = "Please fill in all required shipping and payment information.";
    } elseif (empty($cart_items)) {
        $error = "Your cart is empty. Cannot place order.";
    }

    if (empty($error)) {
        try {
            $conn->begin_transaction();

            // 自动生成 Tracking Number
            $tracking_number = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 11);

            // 插入 tracking 记录
            $tracking_query = "
                INSERT INTO `06_tracking` 
                (Tracking_Number, Delivery_Status, Delivery_Address, Delivery_City, Delivery_Postcode, Delivery_State)
                VALUES ('$tracking_number', '准备中', '$address', '$city', $postcode, '$state')
            ";
            $conn->query($tracking_query);
            $trackingID = $conn->insert_id;

            // 插入订单
            $order_query = "
                INSERT INTO `07_order` 
                (CustomerID, TrackingID, OrderDate, OrderStatus, Shipping_Method, Shipping_Name, Shipping_Address, Shipping_City, Shipping_Postcode, Shipping_State, Shipping_Phone, Total_Price)
                VALUES ($customerID, $trackingID, NOW(), 'pending', 'Standard Delivery (Malaysia)', '$name', '$address', '$city', '$postcode', '$state', '$phone', $total)
            ";
            $conn->query($order_query);
            $orderID = $conn->insert_id;

            // 插入订单详情
            foreach ($cart_items as $item) {
                $item_query = "
                    INSERT INTO `08_order_details` 
                    (OrderID, ProductID, Order_Quantity, Order_Subtotal)
                    VALUES ($orderID, {$item['ProductID']}, {$item['Quantity']}, {$item['Subtotal']})
                ";
                $conn->query($item_query);
            }

            // 插入付款信息
            $payment_query = "
                INSERT INTO `09_payment` 
                (OrderID, Payment_Card_Type, Payment_Card_Number, Payment_Card_Bank)
                VALUES ($orderID, '$payment_method', '$card_number', '$card_bank')
            ";
            $conn->query($payment_query);

            // 清空购物车
            $clear_cart_query = "DELETE FROM `12_cart_item` WHERE CartID = $cartID";
            $conn->query($clear_cart_query);

            $conn->commit();
            header("Location: order_confirmation.php?id=$orderID&success=1");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Order failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
    <link rel="stylesheet" href="Checkout.css">
</head>
<body>
    <div class="container">
        <h1>Checkout</h1>

        <?php if (!empty($error)): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>

        <?php if (!empty($cart_items)): ?>
        <form method="post">
            <h3>Shipping Information</h3>
            <input type="text" name="name" placeholder="Full Name" required><br>
            <input type="text" name="address" placeholder="Address" required><br>
            <input type="text" name="city" placeholder="City" required><br>
            <input type="text" name="postcode" placeholder="Postcode" required><br>
            <input type="text" name="state" placeholder="State" required><br>
            <input type="text" name="phone" placeholder="Phone Number" required><br>

            <h3>Payment Method</h3>
            <select name="payment_method" required>
                <option value="Visa">Visa</option>
                <option value="Mastercard">Mastercard</option>
                <option value="FPX">FPX</option>
            </select><br>
            <input type="text" name="card_number" placeholder="Card Number"><br>
            <input type="text" name="card_bank" placeholder="Card Issuer Bank"><br>

            <h3>Cart Summary</h3>
            <table>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
                <?php foreach ($cart_items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['ProductName']) ?></td>
                    <td><?= $item['Quantity'] ?></td>
                    <td>RM <?= number_format($item['Subtotal'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <p><strong>Subtotal:</strong> RM <?= number_format($subtotal, 2) ?></p>
            <p><strong>Shipping Fee:</strong> RM <?= number_format($shipping_fee, 2) ?></p>
            <p><strong>Total:</strong> RM <?= number_format($total, 2) ?></strong></p>

            <button type="submit" name="place_order">Place Order</button>
        </form>
        <?php else: ?>
            <p>Your cart is empty.</p>
        <?php endif; ?>
    </div>
</body>
</html>
