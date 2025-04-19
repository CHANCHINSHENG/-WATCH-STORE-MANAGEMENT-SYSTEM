<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $customerID = $_SESSION['customer_id'] ?? null;

    if ($customerID) {
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
                $row = $result_check->fetch_assoc();
                $quantity = $row['Quantity'];

                if (isset($_POST['quantity'])) {
                    $quantity = (int)$_POST['quantity'];
                    if ($quantity <= 0) {
                        $sql_delete = "DELETE FROM `12_cart_item` WHERE CartID = ? AND ProductID = ?";
                        $stmt_delete = $conn->prepare($sql_delete);
                        $stmt_delete->bind_param("ii", $cartID, $product_id);
                        $stmt_delete->execute();
                    } else {
                        $sql_update = "UPDATE `12_cart_item` SET Quantity = ? WHERE CartID = ? AND ProductID = ?";
                        $stmt_update = $conn->prepare($sql_update);
                        $stmt_update->bind_param("iii", $quantity, $cartID, $product_id);
                        $stmt_update->execute();
                    }
                } elseif (isset($_POST['action'])) {
                    switch ($_POST['action']) {
                        case 'increase':
                            $quantity++;
                            break;
                        case 'decrease':
                            $quantity--;
                            if ($quantity <= 0) {
                                $sql_delete = "DELETE FROM `12_cart_item` WHERE CartID = ? AND ProductID = ?";
                                $stmt_delete = $conn->prepare($sql_delete);
                                $stmt_delete->bind_param("ii", $cartID, $product_id);
                                $stmt_delete->execute();
                                $quantity = 0;
                            }
                            break;
                        case 'remove':
                            $sql_delete = "DELETE FROM `12_cart_item` WHERE CartID = ? AND ProductID = ?";
                            $stmt_delete = $conn->prepare($sql_delete);
                            $stmt_delete->bind_param("ii", $cartID, $product_id);
                            $stmt_delete->execute();
                            $quantity = 0;
                            break;
                    }

                    if ($quantity > 0) {
                        $sql_update = "UPDATE `12_cart_item` SET Quantity = ? WHERE CartID = ? AND ProductID = ?";
                        $stmt_update = $conn->prepare($sql_update);
                        $stmt_update->bind_param("iii", $quantity, $cartID, $product_id);
                        $stmt_update->execute();
                    }
                }
            }
        }
    }
}

header("Location: cart.php");
exit();
