<?php
session_start();
include 'db.php'; 

if (!isset($_SESSION['customer_id'])) 
{
    header("Location: ../customer_login.php"); 
    exit();
}

$temp_data = $_SESSION['temp_checkout_data'] ?? []; 
$customerID = $temp_data['customer_id'] ?? null;
$shippingName = $temp_data['shipping_name'] ?? null;
$shippingAddress = $temp_data['shipping_address'] ?? null;
$shippingCity = $temp_data['shipping_city'] ?? null;
$shippingPostcode = $temp_data['shipping_postcode'] ?? null;
$shippingState = $temp_data['shipping_state'] ?? null;
$shippingPhone = $temp_data['shipping_phone'] ?? null;
$total = $temp_data['total_amount'] ?? null;
$shippingFee = $temp_data['shipping_fee'] ?? 0; 
$bankName = $temp_data['fpx_bank_selection'] ?? null;
$cartItems = $temp_data['cart_items'] ?? []; 
$orderID = null; 
$error = "";
$success_message = "";

if (empty($customerID) || $total === null || empty($shippingAddress) || empty($bankName) || empty($cartItems)) 
{
    $error = "Checkout information is missing. Please return to the checkout page and try again."; 
    unset($_SESSION['temp_checkout_data']);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment']) && empty($error)) 
{
    $password = trim($_POST['password'] ?? '');
    $account_number = trim($_POST['account_number'] ?? '');

    if (empty($password)) 
    {
        $error = "Please enter your password."; 
    } 
    elseif (empty($account_number)) 
    {
        $error = "Please enter your Account Number."; 
    }
    elseif (strlen($account_number) != 10) {
        $error = "CIMB/Public Bank account number must be 10 digits.";
    }

    if (empty($error)) 
    { 
        try 
        {
            $conn->begin_transaction();

            $tracking_number = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 11);
            $tracking_query = "INSERT INTO 06_tracking (Tracking_Number, Delivery_Status, Delivery_Address, Delivery_City, Delivery_Postcode, Delivery_State, Shipping_Fee) VALUES (?, 'pending', ?, ?, ?, ?, ?)";
            $stmt_tracking = $conn->prepare($tracking_query);

            if (!$stmt_tracking) throw new Exception("Tracking statement preparation failed: " . $conn->error);
            $stmt_tracking->bind_param("sssssi", $tracking_number, $shippingAddress, $shippingCity, $shippingPostcode, $shippingState, $shippingFee);
            $stmt_tracking->execute();
            $trackingID = $conn->insert_id;

            if (!$trackingID) 
            {
                throw new Exception("Failed to create tracking information." . " SQL Error: " . $stmt_tracking->error); 
            }
            $stmt_tracking->close();

            $order_status_for_db = 'Processing';
            $order_query = "INSERT INTO 07_order (CustomerID, TrackingID, OrderDate, OrderStatus, Shipping_Method, Shipping_Name, Shipping_Address, Shipping_City, Shipping_Postcode, Shipping_State, Shipping_Phone, Total_Price) VALUES (?, ?, NOW(), ?, 'Standard Delivery (Malaysia)', ?, ?, ?, ?, ?, ?, ?)";
            $stmt_order = $conn->prepare($order_query);

            if (!$stmt_order) throw new Exception("Order statement preparation failed: " . $conn->error);
            $stmt_order->bind_param("iisssssssd", $customerID, $trackingID, $order_status_for_db, $shippingName, $shippingAddress, $shippingCity, $shippingPostcode, $shippingState, $shippingPhone, $total);
            $stmt_order->execute();
            $orderID = $conn->insert_id; 

            if (!$orderID) 
            {
                throw new Exception("Failed to create order." . " SQL Error: " . $stmt_order->error); 
            }
            $stmt_order->close();

            foreach ($cartItems as $item) 
            { 
                $productID = $item['ProductID'];
                $quantity = $item['Quantity'];
                
                if (!isset($item['Product_Price']) || !isset($item['Subtotal'])) 
                {
                    throw new Exception("Product price or subtotal information is missing (Product price or subtotal information missing for ProductID: " . $productID . ").");
                }
                $subtotal_item = $item['Subtotal']; 

                $check_stock_query = "SELECT Product_Stock_Quantity, ProductName FROM 05_product WHERE ProductID = ?";
                $stmt_check_stock = $conn->prepare($check_stock_query);

                if (!$stmt_check_stock) throw new Exception("Stock check statement preparation failed: " . $conn->error);
                $stmt_check_stock->bind_param("i", $productID);
                $stmt_check_stock->execute();
                $stock_result_row = $stmt_check_stock->get_result()->fetch_assoc();
                $productNameForItem = $stock_result_row['ProductName'] ?? $item['ProductName'] ?? ('ID ' . $productID); 

                if (!$stock_result_row || $stock_result_row['Product_Stock_Quantity'] < $quantity) 
                {
                    throw new Exception("Product " . htmlspecialchars($productNameForItem) . " Out of stock. Please return to checkout to adjust your cart."); 
                }
                $stmt_check_stock->close();

                $item_query = "INSERT INTO 08_order_details (OrderID, ProductID, Order_Quantity, Order_Subtotal) VALUES (?, ?, ?, ?)";
                $stmt_item = $conn->prepare($item_query);

                if (!$stmt_item) throw new Exception("Order detail statement preparation failed for ProductID " . $productID . ": " . $conn->error);
                $stmt_item->bind_param("iiid", $orderID, $productID, $quantity, $subtotal_item);
                $stmt_item->execute();

                if ($stmt_item->affected_rows === 0) 
                {
                    throw new Exception("Unable to insert order details (Failed to insert order detail for ProductID: " . $productID . "). SQL Error: " . $stmt_item->error);
                }
                $stmt_item->close();

                $sql_decrease_stock = "UPDATE 05_product SET Product_Stock_Quantity = Product_Stock_Quantity - ? WHERE ProductID = ? AND Product_Stock_Quantity >= ?";
                $stmt_decrease_stock = $conn->prepare($sql_decrease_stock);

                if (!$stmt_decrease_stock) throw new Exception("Stock update statement preparation failed for ProductID " . $productID . ": " . $conn->error);
                $stmt_decrease_stock->bind_param("iii", $quantity, $productID, $quantity);
                $stmt_decrease_stock->execute();

                if ($stmt_decrease_stock->affected_rows === 0) 
                {
                    throw new Exception("Failed to update product inventory (Stock update failed for ProductID: " . $productID . "). Requested: ".$quantity.". SQL Error: " . $stmt_decrease_stock->error);
                }
                $stmt_decrease_stock->close();
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_bank_payment_query = "
                INSERT INTO 13_bank_payment
                (order_id, bank_account_number, password_hash, payment_status, payment_time, bank_name)
                VALUES (?, ?, ?, 'Success', NOW(), ?)
            ";
            $stmt_bank_payment = $conn->prepare($insert_bank_payment_query);

            if (!$stmt_bank_payment) throw new Exception("Bank payment detail statement preparation failed: " . $conn->error);
            $stmt_bank_payment->bind_param("isss", $orderID, $account_number, $hashed_password, $bankName);
            $stmt_bank_payment->execute();
            $bankPaymentSpecificRecordID = $conn->insert_id; 

            if (!$bankPaymentSpecificRecordID) 
            { 
                throw new Exception("Unable to insert bank payment details (13_bank_payment) or get its ID." . " SQL Error: " . $stmt_bank_payment->error); 
            }
            $stmt_bank_payment->close();


            $order_payment_method_query = "INSERT INTO 14_order_payment_method (OrderID, Payment_Method_Type, Payment_Table_Type, Payment_Record_ID) VALUES (?, ?, ?, ?)";
            $stmt_opm = $conn->prepare($order_payment_method_query);
            if (!$stmt_opm) throw new Exception("Order payment method link statement preparation failed: " . $conn->error);

            $payment_method_type_for_opm = 'Bank Payment'; 
            $payment_table_type_for_opm = '13_bank_payment'; 
            
            $stmt_opm->bind_param("issi", $orderID, $payment_method_type_for_opm, $payment_table_type_for_opm, $bankPaymentSpecificRecordID);
            $stmt_opm->execute();

            if ($stmt_opm->affected_rows === 0) 
            {
                throw new Exception("Unable to create order payment method record (14_order_payment_method)." . " SQL Error: " . $stmt_opm->error); 
            }
            $stmt_opm->close();

            $stmt_get_cartID = $conn->prepare("SELECT CartID FROM 11_cart WHERE CustomerID = ?");
            if (!$stmt_get_cartID) throw new Exception("Get CartID statement preparation failed: " . $conn->error);

            $stmt_get_cartID->bind_param("i", $customerID);
            $stmt_get_cartID->execute();
            $result_cartID = $stmt_get_cartID->get_result();
            $cartID_to_clear = null;

            if ($row_cartID = $result_cartID->fetch_assoc()) 
            {
                $cartID_to_clear = $row_cartID['CartID'];
            }
            $stmt_get_cartID->close();

            if ($cartID_to_clear) 
            {
                $clear_cart_items_query = "DELETE FROM 12_cart_item WHERE CartID = ?";
                $stmt_clear_items = $conn->prepare($clear_cart_items_query);

                if (!$stmt_clear_items) 
                {
                    error_log("Cart clearing statement preparation failed: " . $conn->error);
                } 
                else 
                {
                    $stmt_clear_items->bind_param("i", $cartID_to_clear);
                    $stmt_clear_items->execute();
                    $stmt_clear_items->close();
                    error_log("Shopping cart items cleared (Cart items cleared)，CartID: " . $cartID_to_clear . ", Order ID after bank payment is successful (Order ID after successful bank payment): " . $orderID);
                }
            }

            $conn->commit(); 

            $success_message = "Payment successful! Your order has been confirmed."; 
            unset($_SESSION['temp_checkout_data']); 
            header("Location: ../order_confirmation.php?id=$orderID&success=1");
            exit();

        } 
        catch (Exception $e) 
        {
            $conn->rollback(); 
            $error = "Payment processing failed (Payment processing failed): " . $e->getMessage();
            error_log("Bank payment confirmation abnormality (Bank payment confirmation exception): " . $e->getMessage() . " - SQL State: " . $conn->sqlstate . " - Trace: " . $e->getTraceAsString());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Banking Payment - <?= htmlspecialchars($bankName ?? 'Selected Bank') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
    body 
    {
        font-family: 'Inter', sans-serif;
        background-color: #fbeeee;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
        padding: 1rem;
    }

    .payment-container 
    {
        background-color: #ffffff;
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        text-align: center;
        max-width: 480px;
        width: 100%;
    }

    .payment-header 
    {
        font-size: 1.75rem;
        font-weight: 700;
        color: #DA291C; 
        margin-bottom: 1.5rem;
    }

    .payment-details 
    {
        margin-bottom: 1.5rem;
    }

    .payment-details p 
    {
        font-size: 1rem;
        color: #4b5563;
        margin-bottom: 0.75rem;
    }

    .payment-details strong 
    {
        color: #111827;
    }

    .bank-info 
    {
        background-color: #ffeeed; 
        border: 1px solid #f5c5c2;  
        color: #a71e14;     
        padding: 1rem;
        border-radius: 0.75rem;
        margin-top: 1.5rem;
        margin-bottom: 2rem;
    }

    .bank-info p 
    {
        font-size: 0.95rem;
        color: #a71e14; 
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .bank-info p:last-child 
    {
        margin-bottom: 0;
    }

    .bank-info strong 
    {
        font-weight: 700;
        color: #8c1710;
    }

    .input-group 
    {
        margin-bottom: 1.5rem;
        text-align: left;
    }

    .input-group label 
    {
        display: block;
        font-size: 0.875rem;
        color: #374151;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    .input-group input 
    {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 1rem;
        box-sizing: border-box;
    }

    .input-group input:focus 
    {
        outline: none;
        border-color: #DA291C; 
        box-shadow: 0 0 0 3px rgba(218, 41, 28, 0.25); 
    }

    .confirm-button 
    {
        background: #DA291C; 
        color: white;
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        border-radius: 0.75rem;
        border: none;
        cursor: pointer;
        font-size: 1rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 10px rgba(218, 41, 28, 0.3);
        width: 100%;
        margin-top: 1.5rem;
    }

    .confirm-button:hover 
    {
        background: #b52013; 
        box-shadow: 0 6px 15px rgba(181, 32, 19, 0.4);
        transform: translateY(-2px);
    }

    .error-message 
    { 
        color: #ef4444;
        font-size: 0.875rem;
        margin-top: 1rem;
        margin-bottom: 1rem;
        background-color: #fee2e2;
        padding: 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid #fca5a5;
    }

    .success-message 
    { 
        color: #10b981;
        font-size: 0.875rem;
        margin-top: 1rem;
        background-color: #d1fae5;
        padding: 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid #6ee7b7;
    }

    .back-button 
    { 
        background-color: #6c757d; 
        color: white;
        font-weight: 500;
        padding: 0.75rem 1.5rem;
        border-radius: 0.75rem;
        border: none;
        cursor: pointer;
        font-size: 0.95rem;
        margin-top: 1rem;
        transition: background-color 0.3s ease;
        width: 100%;
    }

    .back-button:hover 
    {
        background-color: #5a6268;
    }
    </style>
</head>

<body>   
    <div class="payment-container">
        <img src="CIMB_Bank_Logo.png" alt="<?= htmlspecialchars($bankName ?? 'CIMB Bank') ?> Logo" style="display: block; width: 330px; max-width: 100%; height: auto; object-fit: contain; margin: 1rem auto 1.5rem auto;">
        <h1 class="payment-header">Online Banking Payment </h1>

        <?php if (!empty($error)): ?>
            <p class="error-message"><?= htmlspecialchars($error) ?></p>
            <button class="back-button" onclick="window.location = '../checkout.php';">Return to Checkout Page </button>
        <?php elseif (!empty($success_message)): ?>
            <p class="success-message"><?= htmlspecialchars($success_message) ?></p>
            <p style="margin-top: 1rem;">You will be redirected shortly... </p>
            <?php else: ?>
            <div class="payment-details">
                <p>Order ID: <strong><?= htmlspecialchars($orderID ?? 'N/A')?></strong></p>
                <p>Total Amount: <strong>RM <?= number_format($total ?? 0, 2)?></strong></p>
            </div>

            <div class="bank-info">
                <p>Payment for <strong><?= htmlspecialchars($bankName ?? 'Your Bank')?></strong>.</p>
                <p>Please enter the details below to payment confirmation. </p>
            </div>

            <form method="post">
                <div class="input-group">
                    <label for="account_number">Account Number: </label>
                    <input type="text" id="account_number" name="account_number" placeholder="e.g., 1234567890" required minlength="10" maxlength="10" pattern="\d{10}" title="Please enter a 10-digit for CIMB bank account number.">
                </div>
                <div class="input-group">
                    <label for="password">Password: </label>
                    <input type="password" id="password" name="password" placeholder="e.g., password123" required>
                </div>
                <button type="submit" name="confirm_payment" class="confirm-button">Confirm Payment </button>
            </form>
            <button class="back-button" onclick="window.location = '../checkout.php';">Cancel and Return to Checkout </button>
        <?php endif; ?>
    </div>
</body>
</html>