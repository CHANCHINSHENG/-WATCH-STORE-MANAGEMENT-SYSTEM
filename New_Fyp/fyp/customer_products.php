<?php
session_start();
include 'db.php';

// 处理添加到购物车
$product_added = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $customerID = $_SESSION['customer_id'] ?? null;

    if ($customerID) {
        // 检查 CustomerID 是否存在
        $sql_check_customer = "SELECT CustomerID FROM `02_customer` WHERE CustomerID = ?";
        $stmt_check_customer = $conn->prepare($sql_check_customer);
        $stmt_check_customer->bind_param("i", $customerID);
        $stmt_check_customer->execute();
        $result_check_customer = $stmt_check_customer->get_result();

        if ($result_check_customer->num_rows > 0) {
            // 获取 CartID
            $sql_cart = "SELECT CartID FROM `11_cart` WHERE CustomerID = ?";
            $stmt_cart = $conn->prepare($sql_cart);
            $stmt_cart->bind_param("i", $customerID);
            $stmt_cart->execute();
            $result_cart = $stmt_cart->get_result();

            if ($result_cart->num_rows > 0) {
                $cart_row = $result_cart->fetch_assoc();
                $cartID = $cart_row['CartID'];

                // 检查商品是否在购物车中
                $sql_check = "SELECT Quantity FROM `12_cart_item` WHERE CartID = ? AND ProductID = ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("ii", $cartID, $product_id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();

                if ($result_check->num_rows > 0) {
                    // 商品已存在，更新数量
                    $row = $result_check->fetch_assoc();
                    $quantity = $row['Quantity'] + 1;  // 增加数量

                    // 更新数量
                    $sql_update = "UPDATE `12_cart_item` SET Quantity = ? WHERE CartID = ? AND ProductID = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->bind_param("iii", $quantity, $cartID, $product_id);
                    $stmt_update->execute();
                } else {
                    // 商品不存在，插入新记录
                    $sql_insert = "INSERT INTO `12_cart_item` (CartID, ProductID, Quantity) VALUES (?, ?, ?)";
                    $stmt_insert = $conn->prepare($sql_insert);
                    $quantity = 1;  // 初始数量为 1
                    $stmt_insert->bind_param("iii", $cartID, $product_id, $quantity);
                    $stmt_insert->execute();
                }
            } else {
                // 如果购物车不存在，为该用户创建一个新的购物车
                $sql_create_cart = "INSERT INTO `11_cart` (CustomerID) VALUES (?)";
                $stmt_create_cart = $conn->prepare($sql_create_cart);
                $stmt_create_cart->bind_param("i", $customerID);
                $stmt_create_cart->execute();
                $cartID = $stmt_create_cart->insert_id;

                // 插入商品到新购物车
                $sql_insert = "INSERT INTO `12_cart_item` (CartID, ProductID, Quantity) VALUES (?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $quantity = 1;  // 初始数量为 1
                $stmt_insert->bind_param("iii", $cartID, $product_id, $quantity);
                $stmt_insert->execute();
            }

            // 获取商品详细信息以显示在购物车确认页面
            $sql_product = "SELECT ProductName, Product_Price, Product_Image FROM `05_product` WHERE ProductID = ?";
            $stmt_product = $conn->prepare($sql_product);
            $stmt_product->bind_param("i", $product_id);
            $stmt_product->execute();
            $result_product = $stmt_product->get_result();

            if ($result_product->num_rows > 0) {
                $product = $result_product->fetch_assoc();
                // 将商品信息存储到会话中，以便在页面中使用
                $_SESSION['product_added'] = $product;
                $product_added = $product;
            }
        } else {
            // 如果 CustomerID 不存在，则引导用户登录
            header("Location: customer_login.php");
            exit();
        }
    } else {
        // 如果没有登录的用户 ID，跳转到登录页面
        header("Location: customer_login.php");
        exit();
    }
}

// 获取所有可用的商品
$result = $conn->query("SELECT * FROM 05_product WHERE Product_Status = 'Available'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shop - Available Products</title>
    <link rel="stylesheet" href="customer_products.css">
    <link rel="stylesheet" href="add_to_cart.css"> 
</head>
<body>
    <div class="page-wrapper">
        <div class="product-page-container">
            <div class="product-page-header">
                <h1>TOP PICKS</h1>
                <li class="nav-item px-2"><a class="nav-link fw-bold" href="cart.php"><img src="img/Cart icon.png" alt="Cart" style="width:24px; height:24px;"></a></li>
                <h3>Discover our latest collection of premium watches</h3>
            </div>

            <div class="product-grid">
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <div class="product-card">
                        <a href="product_details.php?id=<?= $row['ProductID']; ?>" class="product-link">
                            <?php if (!empty($row['Product_Image']) && file_exists($row['Product_Image'])) { ?>
                                <img src="<?= htmlspecialchars($row['Product_Image']); ?>" 
                                     alt="<?= htmlspecialchars($row['ProductName']); ?>" 
                                     style="width: 100%; height: auto; max-height: 200px; border-radius: 12px; object-fit: cover; margin-bottom: 1rem;">
                            <?php } else { ?>
                                <div style="width: 100%; height: 200px; background: #f0f0f0; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #888; margin-bottom: 1rem;">
                                    No Image
                                </div>
                            <?php } ?>

                            <h3><?= htmlspecialchars($row['ProductName']); ?></h3>
                            <p><?= htmlspecialchars($row['Product_Description']); ?></p>
                            <p class="product-price">Price: RM <?= number_format($row['Product_Price'], 2); ?></p>
                            <p>Stock: <?= $row['Product_Stock_Quantity']; ?></p>
                        </a>

                        <form action="" method="post">
                            <input type="hidden" name="product_id" value="<?= $row['ProductID']; ?>">
                            <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                        </form>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- The Modal -->
    <?php if (isset($product_added)): ?>
        <div id="myModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>🎉 Product Added to Cart! 🎉</h2>
                <div class="product-info">
                    <img src="<?= htmlspecialchars($product_added['Product_Image']); ?>" alt="Product Image">
                    <p><strong>Product Name:</strong> <?= htmlspecialchars($product_added['ProductName']); ?></p>
                    <p><strong>Price:</strong> MYR <?= number_format($product_added['Product_Price'], 2); ?></p>
                    <div class="total-section">
                        <span>Total: RM <?= number_format($product_added['Product_Price'] - 43.57, 2); ?></span>
                    </div>
                </div>
                <div class="button-container">
                    <button onclick="window.location.href='customer_products.php'">Continue Shopping 🛍️</button>
                    <button onclick="window.location.href='cart.php'">View Cart 🛒</button>
                </div>
            </div>
        </div>

        <script>
            var modal = document.getElementById("myModal");
            var span = document.getElementsByClassName("close")[0];
            modal.style.display = "block";
            span.onclick = function() {
                modal.style.display = "none";
            }
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
        </script>
    <?php endif; ?>
</body>
</html>
