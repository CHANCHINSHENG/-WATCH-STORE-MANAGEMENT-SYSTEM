<?php
session_start();
include 'db.php';

$cart_items = [];
$total_amount = 0;
$item_count = 0;
$error_message = "";

$customerID = $_SESSION['customer_id'] ?? null;

if ($customerID) 
{
    $sql_cart = "SELECT CartID FROM `11_cart` WHERE CustomerID = ?";
    $stmt_cart = $conn->prepare($sql_cart);
    $stmt_cart->bind_param("i", $customerID);
    $stmt_cart->execute();
    $result_cart = $stmt_cart->get_result();

    if ($result_cart->num_rows > 0) 
    {
        $cart_row = $result_cart->fetch_assoc();
        $cartID = $cart_row['CartID'];

    $sql_items = "
SELECT 
    p.ProductID, 
    p.ProductName, 
    p.Product_Price, 
    (SELECT ImagePath FROM 06_product_images WHERE ProductID = p.ProductID AND IsPrimary = 1 LIMIT 1) AS Product_Image,
    p.Product_Stock_Quantity, 
    ci.Quantity 
FROM `12_cart_item` ci
JOIN `05_product` p ON ci.ProductID = p.ProductID
WHERE ci.CartID = ?
";


        $stmt_items = $conn->prepare($sql_items);
        $stmt_items->bind_param("i", $cartID);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();

        while ($row = $result_items->fetch_assoc()) 
        {
            if ($row['Quantity'] > 10) 
            {
                $error_message = "Quantity exceeds 10 for one or more products. Please adjust your cart.";
                break;
            }

            $subtotal = $row['Product_Price'] * $row['Quantity'];

            $cart_items[] = 
            [
                'ProductID'      => $row['ProductID'],
                'ProductName'    => $row['ProductName'],
                'Product_Price'  => $row['Product_Price'],
                'Order_Quantity' => $row['Quantity'],
                'Order_Subtotal' => $subtotal,
                'Product_Image'  => $row['Product_Image'],
                'Product_Stock_Quantity' => $row['Product_Stock_Quantity'] 
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

<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="Title main page">
    <div class="container"><a class="navbar-brand d-inline-flex" href="customermainpage.php"><img src="assets/img/Screenshot 2025-03-20 113245.png"></a>

    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item px-2">
            <a class="nav-link fw-bold <?= $current_page == 'customer_products.php' ? 'active' : '' ?>" href="customer_products.php">WATCHES</a>
        </li>
        <li class="nav-item px-2">
            <a class="nav-link fw-bold <?= $current_page == 'contact.php' ? 'active' : '' ?>" href="#contact">CONTACT</a>
        </li>
        <li class="nav-item px-2">
            <a class="nav-link fw-bold <?= $current_page == 'cart.php' ? 'active' : '' ?>" href="cart.php"><img src="img/Cart_icon.png" alt="Cart" style="width:24px; height:24px;"></a>
        </li>
        <li class="nav-item px-2">
            <a class="nav-link fw-bold <?= $current_page == 'customer_profile.php' ? 'active' : '' ?>" href="customer_profile.php"><img src="img/user_icon.png" alt="login" style="width:24px; height:24px;"></a>
        </li>
    </ul>
</div>

    <div class="cart-container">
        <h1>Your Shopping Cart (<span id="cart-item-count"><?= $item_count ?></span>)</h1>

        <?php if (!empty($error_message)): ?>
            <p class="error-message" style="color: red; text-align: center;"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="empty-cart"><h2>Your cart is empty.</h2><a href="customer_products.php" class="continue-shopping-btn">Continue Shopping</a></div>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item" data-product-id="<?= $item['ProductID'] ?>" data-stock="<?= $item['Product_Stock_Quantity'] ?>">
                        <img src="admin_addproduct_include/<?= htmlspecialchars($item['Product_Image']) ?>" class="cart-item-image" alt="<?= htmlspecialchars($item['ProductName']) ?>">

                        <div class="cart-item-details">
                            <h3><?= htmlspecialchars($item['ProductName']) ?></h3>
                            <p>Price: RM <?= number_format($item['Product_Price'], 2) ?></p>
                        
                            <div class="quantity-control">
                                <button class="decrease-btn">-</button>
                                <input type="text" class="quantity-input" value="<?= $item['Order_Quantity'] ?>">
                                <button class="increase-btn">+</button>
                            </div>

                            <button class="remove-btn">Remove</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="order-summary">
                <h2>Order Summary</h2>
                <p>Items Total: RM <span id="total-amount"><?= number_format($total_amount, 2) ?></span></p>
                <form action="Checkout.php" method="post"><button type="submit" class="checkout-btn" id="checkout-btn">Proceed to Checkout</button></form>
            </div>
        <?php endif; ?>
    </div>

<script>
$(document).ready(function() 
{
    function updateQuantity(parent, newQuantity, action) 
    {
        var productId = parent.data('product-id');
        var currentQuantityInput = parent.find('.quantity-input'); 

        $.ajax({
            url: 'update_cart.php',
            method: 'POST',
            data: { product_id: productId, quantity: newQuantity, action: action },
            success: function(response) {
                try 
                {
                    var data = JSON.parse(response);
                    currentQuantityInput.val(data.new_quantity); 

                    if (data.success) 
                    {
                        $('#total-amount').text(data.total_amount.toFixed(2));
                        $('#cart-item-count').text(data.total_items);
                        
                        if (data.message) 
                        {
                            console.log("Server message:", data.message); 
                        }

                        if (data.new_quantity === 0) 
                        {
                            parent.fadeOut(300, function() 
                            {
                                $(this).remove();
                                if ($('.cart-item').length === 0) 
                                {
                                    $('.cart-container').html('<div class="empty-cart"><h2>Your cart is empty.</h2><a href="customer_products.php" class="continue-shopping-btn">Continue Shopping</a></div>');
                                }
                            });
                        }
                    } 
                    else 
                    {
                        alert(data.message || 'Could not update cart.');
                        if(data.new_quantity !== undefined) 
                        {
                           currentQuantityInput.val(data.new_quantity); 
                        }
                    }
                } 
                catch (e) 
                {
                    console.error("Failed to parse JSON response:", response, e);
                }
            },
            error: function(xhr, status, error) 
            {
                console.error("AJAX request failed:", status, error, xhr.responseText);
            }
        });
    }


     $('.increase-btn').click(function() 
     {
        var parent = $(this).closest('.cart-item');
        var quantityInput = parent.find('.quantity-input');
        var newQuantity = parseInt(quantityInput.val()) + 1;

        var stock = parseInt(parent.data('stock'));
        var limit = 10;
        var effectiveLimit = Math.min(stock, limit); 

        if (newQuantity > effectiveLimit) 
        {
            if (stock < limit) 
            {
                alert("Stock is running low! 🚨 We only have " + stock + " of this watch left. You can't add any more!");
            } 
            else 
            {
                 alert("Oops! 🖐️ This watch is limited to 10 pieces per customer.");
            }
            return; 
        }
        
        updateQuantity(parent, newQuantity, 'increase');
    });

    $('.decrease-btn').click(function() 
    {
        var parent = $(this).closest('.cart-item');
        var quantityInput = parent.find('.quantity-input');
        var quantity = parseInt(quantityInput.val()) - 1;

        if (quantity >= 1) 
        {
            updateQuantity(parent, quantity, 'decrease');
        }
    });

    $('.quantity-input').on('change', function() 
    {
        var parent = $(this).closest('.cart-item');
        var newQuantity = parseInt($(this).val()); 

        if (isNaN(newQuantity) || newQuantity < 1) 
        {
            newQuantity = 1; 
        }

        var stock = parseInt(parent.data('stock'));
        var limit = 10;
        var effectiveLimit = Math.min(stock, limit);

        if (newQuantity > effectiveLimit) 
        {
            if (stock < limit) 
            {
                alert("Stock is running low! 🚨 We only have " + stock + " of this watch left.");
            } 
            else 
            {
                alert("Oops! 🖐️ This watch is limited to 10 pieces per customer.");
            }
            newQuantity = effectiveLimit; 
        }
        
        $(this).val(newQuantity); 
        updateQuantity(parent, newQuantity, 'update'); 
    });


    $('.remove-btn').click(function() 
    {
        var parent = $(this).closest('.cart-item');
        var productId = parent.data('product-id');

        $.ajax({
            url: 'update_cart.php',
            method: 'POST',
            data: { product_id: productId, quantity: 0, action: 'remove' },
            success: function(response) 
            {
                try 
                {
                    var data = JSON.parse(response);
                    if (data.success) 
                    {
                        parent.fadeOut(300, function() 
                        {
                            $(this).remove(); 
                            if ($('.cart-item').length === 0) 
                            {
                                $('.cart-container').html('<div class="empty-cart"><h2>Your cart is empty.</h2><a href="customer_products.php" class="continue-shopping-btn">Continue Shopping</a></div>');
                            }
                        });
                        $('#total-amount').text(data.total_amount.toFixed(2));
                        $('#cart-item-count').text(data.total_items);
                        
                        if (data.message) 
                        { 
                            console.log("Server message for removal:", data.message); 
                        }
                    } 
                    else 
                    {
                        console.error('Error removing item:', data.message || 'An unknown error occurred during removal.');
                    }
                } 
                catch (e) 
                {
                    console.error("Failed to parse JSON response during removal:", response, e);
                }
            },
            error: function(xhr, status, error) 
            {
                console.error("AJAX removal request failed:", status, error, xhr.responseText);
            }
        });
    });
});
</script>

</body>
</html>
