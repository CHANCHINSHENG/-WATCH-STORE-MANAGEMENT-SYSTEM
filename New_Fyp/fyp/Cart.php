<?php
session_start();
include 'db.php';

$cart_items = [];
$total_amount = 0;
$item_count = 0;
$error_message = "";  // 用于保存错误消息

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
            if ($row['Quantity'] > 10) {
                $error_message = "Quantity exceeds 10 for one or more products. Please adjust your cart."; // Error message if quantity > 10
                break; // Exit if any item exceeds the limit
            }
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="Cart.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
</head>
<body>

<div class="Title main page">
    <div class="container"><a class="navbar-brand d-inline-flex" href="customermainpage.php"><img src="assets/img/Screenshot 2025-03-20 113245.png"></a>
    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item px-2">
            <a class="nav-link fw-bold <?= $current_page == 'customer_products.php' ? 'active' : '' ?>" href="customer_products.php">WATCHES</a>
        </li>
        <li class="nav-item px-2">
            <a class="nav-link fw-bold <?= $current_page == 'customer_products.php' ? 'active' : '' ?>" href="customer_products.php">STORE</a>
        </li>
        <li class="nav-item px-2">
            <a class="nav-link fw-bold <?= $current_page == 'contact.php' ? 'active' : '' ?>" href="#contact">CONTACT</a>
        </li>
        <li class="nav-item px-2">
            <a class="nav-link fw-bold <?= $current_page == 'cart.php' ? 'active' : '' ?>" href="cart.php"><img src="img/Cart_icon.png" alt="Cart" style="width:24px; height:24px;"></a>
        </li>
    </ul>
    </div>

    <div class="cart-container">
        <h1>Your Shopping Cart (<span id="cart-item-count"><?= $item_count ?></span>)</h1>

        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <h2>Your cart is empty.</h2>
                <a href="customer_products.php" class="continue-shopping-btn">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item" data-product-id="<?= $item['ProductID'] ?>">
                        <img src="<?= htmlspecialchars($item['Product_Image']) ?>" class="cart-item-image" alt="<?= htmlspecialchars($item['ProductName']) ?>">

                        <div class="cart-item-details">
                            <h3><?= htmlspecialchars($item['ProductName']) ?></h3>
                            <p>Price: RM <?= number_format($item['Product_Price'], 2) ?></p>

                            <div class="quantity-control">
                                <button class="decrease-btn">-</button>
                                <input type="text" class="quantity-input" value="<?= $item['Order_Quantity'] ?>">
                                <button class="increase-btn">+</button>
                            </div>

                            <button class="remove-btn">Remove</button> <!-- Remove按钮 -->
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="order-summary">
                <h2>Order Summary</h2>
                <p>Items Total: RM <span id="total-amount"><?= number_format($total_amount, 2) ?></span></p>
                <form action="Checkout.php" method="post">
                    <button type="submit" class="checkout-btn" id="checkout-btn">Proceed to Checkout</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
    $(document).ready(function() {

        // Handle increase button click
        $('.increase-btn').click(function() {
            var parent = $(this).closest('.cart-item');
            var quantityInput = parent.find('.quantity-input');
            var quantity = parseInt(quantityInput.val()) + 1;

            if (quantity > 10) {
                alert("You cannot add more than 10 of this product.");
                return;
            }

            // AJAX call to update cart quantity
            updateQuantity(parent, quantity);
        });

        // Handle decrease button click
        $('.decrease-btn').click(function() {
            var parent = $(this).closest('.cart-item');
            var quantityInput = parent.find('.quantity-input');
            var quantity = parseInt(quantityInput.val()) - 1;

            if (quantity >= 1) {
                updateQuantity(parent, quantity);
            }
        });

        // Handle quantity input change
        $('.quantity-input').on('change', function() {
            var parent = $(this).closest('.cart-item');
            var quantity = parseInt($(this).val());
            if (isNaN(quantity) || quantity < 1) {
                quantity = 1;
            }
            if (quantity > 10) {
                alert("You cannot buy more than 10 of this product.");
                $(this).val(10);
                quantity = 10;
            }
            updateQuantity(parent, quantity);
        });

        // Handle remove button click
        $('.remove-btn').click(function() {
            var parent = $(this).closest('.cart-item');
            var productId = parent.data('product-id');

            $.ajax({
                url: 'update_cart.php',
                method: 'POST',
                data: { product_id: productId, quantity: 0 },
                success: function(response) {
                    var data = JSON.parse(response);
                    if (data.success) {
                        parent.fadeOut(300, function() {
                            $(this).remove();
                        });
                        $('#total-amount').text(data.total_amount.toFixed(2));
                        $('#cart-item-count').text(data.total_items);
                    }
                }
            });
        });

        function updateQuantity(parent, newQuantity) {
            var productId = parent.data('product-id');

            $.ajax({
                url: 'update_cart.php',
                method: 'POST',
                data: { product_id: productId, quantity: newQuantity },
                success: function(response) {
                    var data = JSON.parse(response);
                    if (data.success) {
                        parent.find('.quantity-input').val(data.new_quantity);
                        $('#total-amount').text(data.total_amount.toFixed(2));
                        $('#cart-item-count').text(data.total_items);
                    }
                }
            });
        }
    });
    </script>

</body>
</html>
