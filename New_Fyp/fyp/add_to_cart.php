<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $customerID = $_SESSION['customer_id'] ?? null;

    if ($customerID) {
        // 检查 CustomerID 是否存在于 02_customer 表中
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

header("Location: cart.php");
exit();
