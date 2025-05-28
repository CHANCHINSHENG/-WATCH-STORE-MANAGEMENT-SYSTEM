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
            // 获取或创建购物车
            $sql_cart = "SELECT CartID FROM `11_cart` WHERE CustomerID = ?";
            $stmt_cart = $conn->prepare($sql_cart);
            $stmt_cart->bind_param("i", $customerID);
            $stmt_cart->execute();
            $result_cart = $stmt_cart->get_result();

            if ($result_cart->num_rows > 0) {
                // 如果购物车存在
                $cart_row = $result_cart->fetch_assoc();
                $cartID = $cart_row['CartID'];
            } else {
                // 如果购物车不存在，创建新购物车
                $sql_create_cart = "INSERT INTO `11_cart` (CustomerID) VALUES (?)";
                $stmt_create_cart = $conn->prepare($sql_create_cart);
                $stmt_create_cart->bind_param("i", $customerID);
                $stmt_create_cart->execute();
                $cartID = $stmt_create_cart->insert_id;
            }

            // 检查商品是否在购物车中
            $sql_check = "SELECT Quantity FROM `12_cart_item` WHERE CartID = ? AND ProductID = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("ii", $cartID, $product_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            // 商品已存在则更新数量，否则插入新记录
            if ($result_check->num_rows > 0) {
                $row = $result_check->fetch_assoc();
                $quantity = $row['Quantity'] + 1; // 增加数量
                $sql_update = "UPDATE `12_cart_item` SET Quantity = ? WHERE CartID = ? AND ProductID = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("iii", $quantity, $cartID, $product_id);
                $stmt_update->execute();
            } else {
                $quantity = 1; // 初始数量为 1
                $sql_insert = "INSERT INTO `12_cart_item` (CartID, ProductID, Quantity) VALUES (?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("iii", $cartID, $product_id, $quantity);
                $stmt_insert->execute();
            }

            // 减少库存
            $sql_product = "SELECT Product_Stock_Quantity FROM `05_product` WHERE ProductID = ?";
            $stmt_product = $conn->prepare($sql_product);
            $stmt_product->bind_param("i", $product_id);
            $stmt_product->execute();
            $result_product = $stmt_product->get_result();

            if ($result_product->num_rows > 0) {
                $product = $result_product->fetch_assoc();
                $new_stock_quantity = $product['Product_Stock_Quantity'] - 1;

                if ($new_stock_quantity >= 0) {
                    // 更新库存数量
                    $sql_update_stock = "UPDATE `05_product` SET Product_Stock_Quantity = ? WHERE ProductID = ?";
                    $stmt_update_stock = $conn->prepare($sql_update_stock);
                    $stmt_update_stock->bind_param("ii", $new_stock_quantity, $product_id);
                    $stmt_update_stock->execute();
                } else {
                    // 库存不足，提示用户
                    echo "Out of stock, Please choose another product.";
                    exit();
                }
            }

            // 获取商品详细信息并存储到会话中
            $sql_product = "SELECT ProductName, Product_Price, Product_Image FROM `05_product` WHERE ProductID = ?";
            $stmt_product = $conn->prepare($sql_product);
            $stmt_product->bind_param("i", $product_id);
            $stmt_product->execute();
            $result_product = $stmt_product->get_result();

            if ($result_product->num_rows > 0) {
                $product = $result_product->fetch_assoc();
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

// 计算总价
$total_price = 0;
if (isset($product_added)) {
    $total_price = $product_added['Product_Price']; // 单个商品的价格
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shop - Available Products</title>
    <link rel="stylesheet" href="Customer_products.css">
    <link rel="stylesheet" href="add_to_cart.css"> 
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

    <div class="page-wrapper">
        <div class="product-page-container">
            <div class="product-page-header">
                <h1>TOP PICKS</h1>
                <h3>Discover our latest collection of premium watches</h3>
            </div>

            <div class="product-grid">
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <div class="product-card">
                        <a href="product_details.php?id=<?= $row['ProductID']; ?>" class="product-link">
                            <img src="<?= htmlspecialchars($row['Product_Image']); ?>" 
                                 alt="<?= htmlspecialchars($row['ProductName']); ?>" 
                                 style="width: 100%; height: auto; max-height: 200px; border-radius: 12px; object-fit: cover; margin-bottom: 1rem;">
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

    <?php if (isset($product_added)): ?>
        <div id="myModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2> Product Added to Cart! </h2>
                <div class="product-info">
                    <img src="<?= htmlspecialchars($product_added['Product_Image']); ?>" alt="Product Image">
                    <p><strong>Product Name:</strong> <?= htmlspecialchars($product_added['ProductName']); ?></p>
                    <p><strong>Price:</strong> MYR <?= number_format($product_added['Product_Price'], 2); ?></p>
                    <div class="total-section">
                        <span>Total: RM <?= number_format($total_price, 2); ?></span> <!-- 显示总价 -->
                    </div>
                </div>
                <div class="button-container">
                    <button onclick="window.location.href='customer_products.php'">Continue Shopping</button>
                    <button onclick="window.location.href='cart.php'">View Cart</button>
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
