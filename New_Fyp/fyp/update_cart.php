<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) 
{
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)($_POST['quantity'] ?? 1);

    // 确保数量合法
    if ($quantity < 0) 
    {
        $quantity = 0;
    }

    if ($quantity > 10) 
    {
        // 如果数量超过 10，返回错误消息
        echo json_encode([
            'success' => false,
            'message' => 'Each product can only be bought up to 10.'
        ]);
        exit();
    }

    $customerID = $_SESSION['customer_id'] ?? null;

    if ($customerID) {
        // 获取CartID
        $stmt = $conn->prepare("SELECT CartID FROM 11_cart WHERE CustomerID = ?");
        $stmt->bind_param("i", $customerID);
        $stmt->execute();
        $result = $stmt->get_result();
        $cart = $result->fetch_assoc();

        if ($cart) {
            $cartID = $cart['CartID'];

            // 如果quantity是0，表示要删除商品
            if ($quantity === 0) {
                // 删除购物车中的商品
                $stmt = $conn->prepare("DELETE FROM 12_cart_item WHERE CartID = ? AND ProductID = ?");
                $stmt->bind_param("ii", $cartID, $product_id);
                $stmt->execute();
            } else {
                // 更新购物车商品的数量
                $stmt = $conn->prepare("SELECT Quantity FROM 12_cart_item WHERE CartID = ? AND ProductID = ?");
                $stmt->bind_param("ii", $cartID, $product_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    // 如果商品已经存在，更新数量
                    $stmt = $conn->prepare("UPDATE 12_cart_item SET Quantity = ? WHERE CartID = ? AND ProductID = ?");
                    $stmt->bind_param("iii", $quantity, $cartID, $product_id);
                    $stmt->execute();
                } else {
                    // 如果商品不存在，添加到购物车
                    $stmt = $conn->prepare("INSERT INTO 12_cart_item (CartID, ProductID, Quantity) VALUES (?, ?, ?)");
                    $stmt->bind_param("iii", $cartID, $product_id, $quantity);
                    $stmt->execute();
                }
            }

            // 查询单件商品的小计（如果商品还在购物车中）
            $item_subtotal = 0;
            if ($quantity > 0) {
                $stmt = $conn->prepare("
                    SELECT (ci.Quantity * p.Product_Price) AS item_subtotal
                    FROM 12_cart_item ci
                    JOIN 05_product p ON ci.ProductID = p.ProductID
                    WHERE ci.CartID = ? AND ci.ProductID = ?
                ");
                $stmt->bind_param("ii", $cartID, $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $itemSubtotalRow = $result->fetch_assoc();
                $item_subtotal = $itemSubtotalRow['item_subtotal'] ?? 0;
            }

            // 查询整个购物车的总金额
            $stmt = $conn->prepare("
                SELECT SUM(ci.Quantity * p.Product_Price) AS total
                FROM 12_cart_item ci
                JOIN 05_product p ON ci.ProductID = p.ProductID
                WHERE ci.CartID = ?
            ");
            $stmt->bind_param("i", $cartID);
            $stmt->execute();
            $result = $stmt->get_result();
            $totalRow = $result->fetch_assoc();
            $total_amount = $totalRow['total'] ?? 0;

            // 查询购物车商品总数量
            $stmt = $conn->prepare("
                SELECT SUM(Quantity) AS total_items
                FROM 12_cart_item
                WHERE CartID = ?
            ");
            $stmt->bind_param("i", $cartID);
            $stmt->execute();
            $result = $stmt->get_result();
            $totalItemsRow = $result->fetch_assoc();
            $total_items = $totalItemsRow['total_items'] ?? 0;

            // 返回响应数据
            echo json_encode([
                'success' => true,
                'new_quantity' => $quantity,
                'item_subtotal' => $item_subtotal,
                'total_amount' => $total_amount,
                'total_items' => $total_items
            ]);
            exit();
        }
    }
}

// 如果出现错误，返回失败响应
echo json_encode([
    'success' => false
]);
exit();
?>

