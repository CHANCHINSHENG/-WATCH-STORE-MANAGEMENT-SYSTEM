<?php
session_start();
include 'db.php';

$response = ['success' => false, 'message' => '', 'new_quantity' => 0, 'total_amount' => 0, 'total_items' => 0];

// 检查用户是否登录
$customerID = $_SESSION['customer_id'] ?? null;
if ($customerID) {
    // 获取传入的商品ID和数量
    $product_id = $_POST['product_id'] ?? null;
    $quantity = $_POST['quantity'] ?? 0;

    if ($product_id && $quantity >= 0) {
        // 获取购物车ID
        $sql_cart = "SELECT CartID FROM `11_cart` WHERE CustomerID = ?";
        $stmt_cart = $conn->prepare($sql_cart);
        $stmt_cart->bind_param("i", $customerID);
        $stmt_cart->execute();
        $result_cart = $stmt_cart->get_result();

        if ($result_cart->num_rows > 0) {
            $cart_row = $result_cart->fetch_assoc();
            $cartID = $cart_row['CartID'];

            // 处理增加、减少商品数量，或者删除商品
            if ($quantity > 0) {
                // 更新购物车中商品的数量
                $sql_update = "UPDATE `12_cart_item` SET Quantity = ? WHERE CartID = ? AND ProductID = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("iii", $quantity, $cartID, $product_id);
                $stmt_update->execute();

                // 更新库存
                $sql_product = "SELECT Product_Stock_Quantity FROM `05_product` WHERE ProductID = ?";
                $stmt_product = $conn->prepare($sql_product);
                $stmt_product->bind_param("i", $product_id);
                $stmt_product->execute();
                $result_product = $stmt_product->get_result();

                if ($result_product->num_rows > 0) {
                    $product = $result_product->fetch_assoc();
                    $new_stock_quantity = $product['Product_Stock_Quantity'] - $quantity;  // 更新库存
                    $sql_update_stock = "UPDATE `05_product` SET Product_Stock_Quantity = ? WHERE ProductID = ?";
                    $stmt_update_stock = $conn->prepare($sql_update_stock);
                    $stmt_update_stock->bind_param("ii", $new_stock_quantity, $product_id);
                    $stmt_update_stock->execute();
                }

                // 返回更新后的信息
                $response['success'] = true;
                $response['new_quantity'] = $quantity;

                // 计算新的购物车总金额和商品数量
                $sql_total = "
                    SELECT SUM(p.Product_Price * ci.Quantity) AS total_amount, SUM(ci.Quantity) AS total_items
                    FROM `12_cart_item` ci
                    JOIN `05_product` p ON ci.ProductID = p.ProductID
                    WHERE ci.CartID = ?
                ";
                $stmt_total = $conn->prepare($sql_total);
                $stmt_total->bind_param("i", $cartID);
                $stmt_total->execute();
                $result_total = $stmt_total->get_result();
                if ($result_total->num_rows > 0) {
                    $total = $result_total->fetch_assoc();
                    $response['total_amount'] = $total['total_amount'];
                    $response['total_items'] = $total['total_items'];
                }
            } elseif ($quantity == 0) {
                // 删除商品
                $sql_delete = "DELETE FROM `12_cart_item` WHERE CartID = ? AND ProductID = ?";
                $stmt_delete = $conn->prepare($sql_delete);
                $stmt_delete->bind_param("ii", $cartID, $product_id);
                $stmt_delete->execute();

                // 恢复库存
                $sql_product = "SELECT Product_Stock_Quantity FROM `05_product` WHERE ProductID = ?";
                $stmt_product = $conn->prepare($sql_product);
                $stmt_product->bind_param("i", $product_id);
                $stmt_product->execute();
                $result_product = $stmt_product->get_result();

                if ($result_product->num_rows > 0) {
                    $product = $result_product->fetch_assoc();
                    $new_stock_quantity = $product['Product_Stock_Quantity'] + 1;  // 恢复库存
                    $sql_update_stock = "UPDATE `05_product` SET Product_Stock_Quantity = ? WHERE ProductID = ?";
                    $stmt_update_stock = $conn->prepare($sql_update_stock);
                    $stmt_update_stock->bind_param("ii", $new_stock_quantity, $product_id);
                    $stmt_update_stock->execute();
                }

                // 返回更新后的信息
                $response['success'] = true;
                $response['new_quantity'] = 0;

                // 计算新的购物车总金额和商品数量
                $sql_total = "
                    SELECT SUM(p.Product_Price * ci.Quantity) AS total_amount, SUM(ci.Quantity) AS total_items
                    FROM `12_cart_item` ci
                    JOIN `05_product` p ON ci.ProductID = p.ProductID
                    WHERE ci.CartID = ?
                ";
                $stmt_total = $conn->prepare($sql_total);
                $stmt_total->bind_param("i", $cartID);
                $stmt_total->execute();
                $result_total = $stmt_total->get_result();
                if ($result_total->num_rows > 0) {
                    $total = $result_total->fetch_assoc();
                    $response['total_amount'] = $total['total_amount'];
                    $response['total_items'] = $total['total_items'];
                }
            }
        }
    }
}

// 返回 JSON 格式的响应
echo json_encode($response);
?>
