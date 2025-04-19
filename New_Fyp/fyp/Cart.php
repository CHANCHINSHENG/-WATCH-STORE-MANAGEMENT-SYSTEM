<?php
session_start();
include 'db.php'; // 确保数据库连接正常

$cart_items = [];
$total_amount = 0;
$item_count = 0;

$customerID = $_SESSION['customer_id'] ?? null;

if ($customerID) {
    $sql_cart = "SELECT CartID FROM `11_cart` WHERE CustomerID = ?";
    $stmt_cart = $conn->prepare($sql_cart);
    $stmt_cart->bind_param("i", $customerID);
    $stmt_cart->execute();
    $result_cart = $stmt_cart->get_result();

    if ($result_cart->num_rows > 0) {
        $cart_row = $result_cart->fetch_assoc();
        $cartID = $cart_row['CartID'];

        $sql_items = "
            SELECT 
                p.ProductID, 
                p.ProductName, 
                p.Product_Price, 
                p.Product_Image, 
                ci.Quantity 
            FROM `12_cart_item` ci
            JOIN `05_product` p ON ci.ProductID = p.ProductID
            WHERE ci.CartID = ?
        ";
        $stmt_items = $conn->prepare($sql_items);
        $stmt_items->bind_param("i", $cartID);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();

        while ($row = $result_items->fetch_assoc()) {
            $subtotal = $row['Product_Price'] * $row['Quantity'];
            $cart_items[] = [
                'ProductID'      => $row['ProductID'],
                'ProductName'    => $row['ProductName'],
                'Product_Price'  => $row['Product_Price'],
                'Order_Quantity' => $row['Quantity'],
                'Order_Subtotal' => $subtotal,
                'Product_Image'  => $row['Product_Image']
            ];
            $total_amount += $subtotal;
            $item_count += $row['Quantity'];
        }
    }
}

// 运费计算（满100免邮）
$shipping_fee = ($total_amount >= 100) ? 0 : 10;
$grand_total = $total_amount + $shipping_fee;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="Cart.css"> 
</head>
<body>
    <div class="cart-container">
        <h1>Your Shopping Cart</h1>

        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <h2>Your cart is empty</h2>
                <a href="customer_products.php">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <img src="<?= htmlspecialchars($item['Product_Image']) ?>" 
                             alt="<?= htmlspecialchars($item['ProductName']) ?>" 
                             class="cart-item-image">
                        
                        <div class="cart-item-details">
                            <h3><?= htmlspecialchars($item['ProductName']) ?></h3>
                            <p>Price: RM <?= number_format($item['Product_Price'], 2) ?></p>

                            <!-- User can update quantity -->
                            <form action="update_cart.php" method="post" style="display:inline;">
                                <input type="hidden" name="product_id" value="<?= $item['ProductID'] ?>">
                                <input type="number" name="quantity" value="<?= $item['Order_Quantity'] ?>" min="1" style="width: 50px;">
                                <button type="submit">Update Quantity</button>
                            </form>

                            <!-- Remove item from cart -->
                            <form action="update_cart.php" method="post" style="display:inline;">
                                <input type="hidden" name="product_id" value="<?= $item['ProductID'] ?>">
                                <input type="hidden" name="action" value="remove">
                                <button type="submit" class="remove-btn">Remove</button>
                            </form>
                        </div>
                        
                        <div class="item-subtotal">
                            RM <?= number_format($item['Order_Subtotal'], 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="order-summary">
                <h2>Order Summary</h2>
                <p>Subtotal (<?= $item_count ?> items): RM <?= number_format($total_amount, 2) ?></p>
                <p>Shipping: <?= $shipping_fee == 0 ? 'FREE' : 'RM ' . number_format($shipping_fee, 2) ?></p>
                <p><strong>Total: RM <?= number_format($grand_total, 2) ?></strong></p>
                <form action="Checkout.php" method="post">
                    <button type="submit" class="checkout-btn">Proceed to Checkout</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
