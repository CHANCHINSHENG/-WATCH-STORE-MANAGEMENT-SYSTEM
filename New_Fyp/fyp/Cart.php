<?php
session_start();
include 'db.php';

$cart_items = [];
$total_amount = 0;
$item_count = 0;

$customerID = $_SESSION['customer_id'] ?? null;

if ($customerID) {
    // 检查用户是否有购物车
    $sql_cart = "SELECT CartID FROM `11_cart` WHERE CustomerID = ?";
    $stmt_cart = $conn->prepare($sql_cart);
    $stmt_cart->bind_param("i", $customerID);
    $stmt_cart->execute();
    $result_cart = $stmt_cart->get_result();

    if ($result_cart->num_rows > 0) {
        // 如果已有购物车，获取 CartID
        $cart_row = $result_cart->fetch_assoc();
        $cartID = $cart_row['CartID'];
    } else {
        // 如果没有购物车，创建一个新的购物车
        $sql_create_cart = "INSERT INTO `11_cart` (CustomerID) VALUES (?)";
        $stmt_create_cart = $conn->prepare($sql_create_cart);
        $stmt_create_cart->bind_param("i", $customerID);
        $stmt_create_cart->execute();
        $cartID = $stmt_create_cart->insert_id;  // 获取新插入购物车的ID
    }

    // 查询购物车中的商品
    $sql_items = "
        SELECT p.ProductID, p.ProductName, p.Product_Price, p.Product_Image, ci.Quantity 
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
            'ProductID' => $row['ProductID'],
            'ProductName' => $row['ProductName'],
            'Product_Price' => $row['Product_Price'],
            'Order_Quantity' => $row['Quantity'],
            'Order_Subtotal' => $subtotal,
            'Product_Image' => $row['Product_Image']
        ];
        $total_amount += $subtotal;
        $item_count += $row['Quantity'];
    }
}

// 添加商品到购物车
if (isset($_POST['add_to_cart'])) {
    $productID = $_POST['product_id'];  // 商品ID
    $quantity = $_POST['quantity'];  // 商品数量

    // 检查商品是否已在购物车中
    $sql_check_item = "SELECT * FROM `12_cart_item` WHERE CartID = ? AND ProductID = ?";
    $stmt_check_item = $conn->prepare($sql_check_item);
    $stmt_check_item->bind_param("ii", $cartID, $productID);
    $stmt_check_item->execute();
    $result_check_item = $stmt_check_item->get_result();

    if ($result_check_item->num_rows > 0) {
        // 商品已在购物车中，更新数量
        $sql_update_quantity = "UPDATE `12_cart_item` SET Quantity = Quantity + ? WHERE CartID = ? AND ProductID = ?";
        $stmt_update_quantity = $conn->prepare($sql_update_quantity);
        $stmt_update_quantity->bind_param("iii", $quantity, $cartID, $productID);
        $stmt_update_quantity->execute();
    } else {
        // 商品不在购物车中，插入新记录
        $sql_add_item = "INSERT INTO `12_cart_item` (CartID, ProductID, Quantity) VALUES (?, ?, ?)";
        $stmt_add_item = $conn->prepare($sql_add_item);
        $stmt_add_item->bind_param("iii", $cartID, $productID, $quantity);
        $stmt_add_item->execute();
    }

    // 刷新购物车内容
    header("Location: cart.php");
    exit();
}

// 更新商品数量
if (isset($_POST['update_quantity'])) {
    $productID = $_POST['product_id'];
    $new_quantity = $_POST['new_quantity'];

    $sql_update_quantity = "UPDATE `12_cart_item` SET Quantity = ? WHERE CartID = ? AND ProductID = ?";
    $stmt_update_quantity = $conn->prepare($sql_update_quantity);
    $stmt_update_quantity->bind_param("iii", $new_quantity, $cartID, $productID);
    $stmt_update_quantity->execute();

    // 刷新购物车内容
    header("Location: cart.php");
    exit();
}

// 删除购物车商品
if (isset($_POST['remove_item'])) {
    $productID = $_POST['product_id'];

    $sql_remove_item = "DELETE FROM `12_cart_item` WHERE CartID = ? AND ProductID = ?";
    $stmt_remove_item = $conn->prepare($sql_remove_item);
    $stmt_remove_item->bind_param("ii", $cartID, $productID);
    $stmt_remove_item->execute();

    // 刷新购物车内容
    header("Location: cart.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="CART.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="cart-container">
    <h1>Your Shopping Cart (<span id="cart-item-count"><?= $item_count ?></span>)</h1>
    <a href="customermainpage.php" class="continue-shopping-btn">Home</a>

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

                        <form method="POST" action="cart.php">
                            <div class="quantity-control">
                                <button type="submit" name="update_quantity" class="decrease-btn" value="<?= $item['ProductID'] ?>">-</button>
                                <input type="number" name="new_quantity" class="quantity-input" value="<?= $item['Order_Quantity'] ?>" min="1">
                                <button type="submit" name="update_quantity" class="increase-btn" value="<?= $item['ProductID'] ?>">+</button>
                            </div>
                        </form>

                        <form method="POST" action="cart.php">
                            <button type="submit" name="remove_item" class="remove-btn" value="<?= $item['ProductID'] ?>">Remove</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="order-summary">
            <h2>Order Summary</h2>
            <p>Items Total: RM <span id="total-amount"><?= number_format($total_amount, 2) ?></span></p>
            <form action="Checkout.php" method="post">
                <button type="submit" class="checkout-btn">Proceed to Checkout</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    $('.increase-btn').click(function() {
        var parent = $(this).closest('.cart-item');
        var quantityInput = parent.find('.quantity-input');
        var quantity = parseInt(quantityInput.val()) + 1;
        quantityInput.val(quantity);
        parent.find('form').submit();
    });

    $('.decrease-btn').click(function() {
        var parent = $(this).closest('.cart-item');
        var quantityInput = parent.find('.quantity-input');
        var quantity = parseInt(quantityInput.val());
        if (quantity > 1) {
            quantity -= 1;
            quantityInput.val(quantity);
            parent.find('form').submit();
        }
    });
});
</script>

</body>
</html>
