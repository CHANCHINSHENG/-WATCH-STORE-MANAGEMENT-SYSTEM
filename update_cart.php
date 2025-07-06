<?php
session_start();
include 'db.php';

$response = ['success' => false, 'message' => '', 'new_quantity' => 0, 'total_amount' => 0, 'total_items' => 0];

$customerID = $_SESSION['customer_id'] ?? null;
if ($customerID) {
    $product_id = $_POST['product_id'] ?? null;
    $new_quantity = (int)($_POST['quantity'] ?? 0); 
    $action = $_POST['action'] ?? null; 

    if ($product_id && $new_quantity >= 0) 
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

            $sql_get_current_cart_quantity = "SELECT Quantity FROM `12_cart_item` WHERE CartID = ? AND ProductID = ?";
            $stmt_get_current_cart_quantity = $conn->prepare($sql_get_current_cart_quantity);
            $stmt_get_current_cart_quantity->bind_param("ii", $cartID, $product_id);
            $stmt_get_current_cart_quantity->execute();
            $result_current_cart_quantity = $stmt_get_current_cart_quantity->get_result();
            $current_cart_quantity = 0;
            if ($result_current_cart_quantity->num_rows > 0) {
                $row_current_cart_quantity = $result_current_cart_quantity->fetch_assoc();
                $current_cart_quantity = (int)$row_current_cart_quantity['Quantity'];
            }

            $sql_product = "SELECT Product_Stock_Quantity FROM `05_product` WHERE ProductID = ?";
            $stmt_product = $conn->prepare($sql_product);
            $stmt_product->bind_param("i", $product_id);
            $stmt_product->execute();
            $result_product = $stmt_product->get_result();

            if ($result_product->num_rows > 0) {
                $product = $result_product->fetch_assoc();
                $current_stock = $product['Product_Stock_Quantity'];

                $stock_change_needed = $new_quantity - $current_cart_quantity;

                if ($stock_change_needed > 0 && $current_stock < $stock_change_needed) {
                    $response['message'] = "Not enough stock available for this increase.";
                    $response['new_quantity'] = $current_cart_quantity; 
                    calculateCartTotal($cartID, $response);
                    echo json_encode($response);
                    exit;
                }

                if ($new_quantity == 0) {
                    $deleted_quantity = $current_cart_quantity; 

                    $sql_delete = "DELETE FROM `12_cart_item` WHERE CartID = ? AND ProductID = ?";
                    $stmt_delete = $conn->prepare($sql_delete);
                    $stmt_delete->bind_param("ii", $cartID, $product_id);
                    $stmt_delete->execute();

                    $update_stock_sql = "UPDATE `05_product` SET Product_Stock_Quantity = Product_Stock_Quantity + ? WHERE ProductID = ?";
                    $stmt_update_stock = $conn->prepare($update_stock_sql);
                    $stmt_update_stock->bind_param("ii", $deleted_quantity, $product_id);
                    $stmt_update_stock->execute();

                    $response['success'] = true;
                    $response['new_quantity'] = 0;
                    calculateCartTotal($cartID, $response);

                } elseif ($new_quantity > 0) { 
                    $sql_update = "UPDATE `12_cart_item` SET Quantity = ? WHERE CartID = ? AND ProductID = ?";
                    $stmt_update = $conn->prepare($sql_update);
                    $stmt_update->bind_param("iii", $new_quantity, $cartID, $product_id);
                    $stmt_update->execute();

                    $update_stock_sql = "UPDATE `05_product` SET Product_Stock_Quantity = Product_Stock_Quantity - ? WHERE ProductID = ?";
                    $stmt_update_stock = $conn->prepare($update_stock_sql);
                    $stmt_update_stock->bind_param("ii", $stock_change_needed, $product_id);
                    $stmt_update_stock->execute();

                    $response['success'] = true;
                    $response['new_quantity'] = $new_quantity;
                    calculateCartTotal($cartID, $response);
                }
            } else {
                $response['message'] = "Product not found.";
            }
        } else {
            $response['message'] = "Cart not found for customer.";
        }
    } else {
        $response['message'] = "Invalid product ID or quantity.";
    }
} else {
    $response['message'] = "Customer not logged in.";
}

echo json_encode($response);

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
        $response['total_amount'] = $total['total_amount'] ?? 0;
        $response['total_items'] = $total['total_items'] ?? 0;
    }
}
?>