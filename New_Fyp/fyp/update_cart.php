<?php
session_start();
include 'db.php';

$response = ['success' => false, 'message' => '', 'new_quantity' => 0, 'total_amount' => 0, 'total_items' => 0];

// 检查用户是否登录
$customerID = $_SESSION['customer_id'] ?? null;
if ($customerID) {
    // 获取传入的商品ID、数量和action
    $product_id = $_POST['product_id'] ?? null;
    $quantity = $_POST['quantity'] ?? 0;
    $action = $_POST['action'] ?? null;

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

            // 获取商品库存
            $sql_product = "SELECT Product_Stock_Quantity FROM `05_product` WHERE ProductID = ?";
            $stmt_product = $conn->prepare($sql_product);
            $stmt_product->bind_param("i", $product_id);
            $stmt_product->execute();
            $result_product = $stmt_product->get_result();

            if ($result_product->num_rows > 0) {
                $product = $result_product->fetch_assoc();
                $current_stock = $product['Product_Stock_Quantity'];

                // 检查库存是否足够
                if ($quantity > $current_stock) {
                    $response['message'] = "Not enough stock available.";
                    echo json_encode($response);
                    exit;
                }

                // 处理增加、减少商品数量，或者删除商品
                if ($quantity > 0) {
                    // 更新购物车中商品的数量
                    $sql_update = "UPDATE `12_cart_item` SET Quantity = ? WHERE CartID = ? AND ProductID = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->bind_param("iii", $quantity, $cartID, $product_id);
                    $stmt_update->execute();

                    // 根据 action 更新库存
                    if ($action == 'increase') {
                        // 商品数量增加，库存减少
                        updateStock($product_id, 1, 'decrease');
                    } elseif ($action == 'decrease') {
                        // 商品数量减少，库存增加
                        updateStock($product_id, 1, 'increase');
                    }

                    $response['success'] = true;
                    $response['new_quantity'] = $quantity;

                    // 计算新的购物车总金额和商品数量
                    calculateCartTotal($cartID, $response);
                } elseif ($quantity == 0 && $action == 'remove') {
                    // 删除商品时，恢复库存
                    // 先获取该商品的原始数量（删除前的数量）
                    $sql_item = "SELECT Quantity FROM `12_cart_item` WHERE CartID = ? AND ProductID = ?";
                    $stmt_item = $conn->prepare($sql_item);
                    $stmt_item->bind_param("ii", $cartID, $product_id);
                    $stmt_item->execute();
                    $result_item = $stmt_item->get_result();
                    
                    if ($result_item->num_rows > 0) {
                        $item = $result_item->fetch_assoc();
                        $deleted_quantity = $item['Quantity']; // 获取删除前的数量

                        // 删除商品
                        $sql_delete = "DELETE FROM `12_cart_item` WHERE CartID = ? AND ProductID = ?";
                        $stmt_delete = $conn->prepare($sql_delete);
                        $stmt_delete->bind_param("ii", $cartID, $product_id);
                        $stmt_delete->execute();

                        // 恢复库存
                        updateStock($product_id, $deleted_quantity, 'restore');

                        $response['success'] = true;
                        $response['new_quantity'] = 0;

                        // 计算新的购物车总金额和商品数量
                        calculateCartTotal($cartID, $response);
                    }
                }
            }
        }
    }
}

echo json_encode($response);

// 更新库存的函数
function updateStock($product_id, $quantity, $action) {
    global $conn;

    // 获取商品信息
    $sql_product = "SELECT Product_Stock_Quantity FROM `05_product` WHERE ProductID = ?";
    $stmt_product = $conn->prepare($sql_product);
    $stmt_product->bind_param("i", $product_id);
    $stmt_product->execute();
    $result_product = $stmt_product->get_result();

    if ($result_product->num_rows > 0) {
        $product = $result_product->fetch_assoc();
        $current_stock = $product['Product_Stock_Quantity'];

        if ($action == 'decrease') {
            // 商品数量增加，库存减少
            $new_stock_quantity = $current_stock - $quantity;
        } elseif ($action == 'increase') {
            // 商品数量减少，库存增加
            $new_stock_quantity = $current_stock + $quantity;
        } elseif ($action == 'restore') {
            // 恢复库存数量
            $new_stock_quantity = $current_stock + $quantity;
        }

        // 确保库存不会为负数
        if ($new_stock_quantity < 0) {
            return false;
        }

        // 更新库存数量
        $sql_update_stock = "UPDATE `05_product` SET Product_Stock_Quantity = ? WHERE ProductID = ?";
        $stmt_update_stock = $conn->prepare($sql_update_stock);
        $stmt_update_stock->bind_param("ii", $new_stock_quantity, $product_id);
        $stmt_update_stock->execute();
    }
}


// 计算总金额和总商品数量的通用函数
function calculateCartTotal($cartID, &$response) {
    global $conn;

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
?>
