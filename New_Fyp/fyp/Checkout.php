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

// Load shipping rules from CSV
$shipping_rules = [];
if (($handle = fopen("shipping_rules.csv", "r")) !== false) {
    fgetcsv($handle); // skip header
    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
        $shipping_rules[] = [
            'start' => (int)trim($data[0]),
            'end' => (int)trim($data[1]),
            'state' => trim($data[2]),
            'fee' => (float)trim($data[3])
        ];
    }
    fclose($handle);
}

// Get CartID
$stmt = $conn->prepare("SELECT CartID FROM 11_cart WHERE CustomerID = ?");
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
        SELECT p.ProductID, p.ProductName, p.Product_Image, p.Product_Price, ci.Quantity
        FROM 12_cart_item ci
        JOIN 05_product p ON ci.ProductID = p.ProductID
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
        // Calculate shipping fee based on postcode
        $customer_postcode = (int)$postcode;
        $shipping_fee = 15; // Default shipping fee

        foreach ($shipping_rules as $rule) {
            if ($customer_postcode >= $rule['start'] && $customer_postcode <= $rule['end']) {
                $shipping_fee = $rule['fee'];
                break;
            }
        }

        $total = $subtotal + $shipping_fee;

        try {
            $conn->begin_transaction();

            // Generate Tracking Number
            $tracking_number = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 11);

            // Insert tracking record
            $tracking_query = "
                INSERT INTO 06_tracking 
                (Tracking_Number, Delivery_Status, Delivery_Address, Delivery_City, Delivery_Postcode, Delivery_State)
                VALUES (?, '准备中', ?, ?, ?, ?)
            ";
            $stmt = $conn->prepare($tracking_query);
            $stmt->bind_param("sssis", $tracking_number, $address, $city, $postcode, $state);
            $stmt->execute();
            $trackingID = $conn->insert_id;

            // Insert order
            $order_query = "
                INSERT INTO 07_order 
                (CustomerID, TrackingID, OrderDate, OrderStatus, Shipping_Method, Shipping_Name, Shipping_Address, Shipping_City, Shipping_Postcode, Shipping_State, Shipping_Phone, Total_Price)
                VALUES (?, ?, NOW(), 'pending', 'Standard Delivery (Malaysia)', ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $conn->prepare($order_query);
            $stmt->bind_param("iissssisd", $customerID, $trackingID, $name, $address, $city, $postcode, $state, $phone, $total);
            $stmt->execute();
            $orderID = $stmt->insert_id;

            // Insert order details
            foreach ($cart_items as $item) {
                $item_query = "
                    INSERT INTO 08_order_details 
                    (OrderID, ProductID, Order_Quantity, Order_Subtotal)
                    VALUES (?, ?, ?, ?)
                ";
                $stmt = $conn->prepare($item_query);
                $stmt->bind_param("iiid", $orderID, $item['ProductID'], $item['Quantity'], $item['Subtotal']);
                $stmt->execute();
            }

            // Insert payment info
            $payment_query = "
                INSERT INTO 09_payment 
                (OrderID, Payment_Card_Type, Payment_Card_Number, Payment_Card_Bank)
                VALUES (?, ?, ?, ?)
            ";
            $stmt = $conn->prepare($payment_query);
            $stmt->bind_param("isss", $orderID, $payment_method, $card_number, $card_bank);
            $stmt->execute();

            // Clear cart
            $clear_cart_query = "DELETE FROM 12_cart_item WHERE CartID = ?";
            $stmt = $conn->prepare($clear_cart_query);
            $stmt->bind_param("i", $cartID);
            $stmt->execute();

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
    <div class="checkout-container">
        <div class="checkout-left">
            <h1>Checkout</h1>

            <?php if (!empty($error)): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <?php if (!empty($cart_items)): ?>
            <form method="post" class="checkout-form">
                <h3>Shipping Information</h3>
                <input type="text" name="name" placeholder="Full Name" required>
                <input type="text" name="address" placeholder="Address" required>
                <input type="text" name="city" placeholder="City" required>
                <input type="text" name="postcode" placeholder="Postcode" required>
                <input type="text" name="state" placeholder="State" required>
                <input type="text" name="phone" placeholder="Phone Number" required>

                <h3>Payment Method</h3>
                <select name="payment_method" required>
                    <option value="Visa">Visa</option>
                    <option value="Mastercard">Mastercard</option>
                    <option value="FPX">FPX</option>
                </select>
                <input type="text" name="card_number" placeholder="Card Number">
                <input type="text" name="card_bank" placeholder="Card Issuer Bank">

                <button type="submit" name="place_order" class="place-order-button">Place Order</button>
            </form>
            <?php else: ?>
                <p>Your cart is empty.</p>
            <?php endif; ?>
        </div>

        <div class="checkout-right">
            <h2>Cart Summary</h2>
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <img src="<?= htmlspecialchars($item['Product_Image']) ?>" alt="<?= htmlspecialchars($item['ProductName']) ?>">
                        <div class="cart-item-details">
                            <h4><?= htmlspecialchars($item['ProductName']) ?></h4>
                            <p>Quantity: <?= htmlspecialchars($item['Quantity']) ?></p>
                            <p>Subtotal: RM <?= number_format($item['Subtotal'], 2) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="cart-summary">
                <p><strong>Subtotal:</strong> RM <?= number_format($subtotal, 2) ?></p>
                <p><strong>Shipping Fee:</strong> RM <?= number_format($shipping_fee, 2) ?></p>
                <p><strong>Total:</strong> <span class="total-price">RM <?= number_format($subtotal + $shipping_fee, 2) ?></span></p>
            </div>
        </div>
    </div>
</body>
</html>
