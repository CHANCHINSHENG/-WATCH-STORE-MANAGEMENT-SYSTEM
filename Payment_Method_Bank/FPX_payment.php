<?php
session_start();
require_once __DIR__ . '/../db.php'; 

if (!isset($_SESSION['temp_checkout_data'])) 
{
    header("Location: ../checkout.php"); 
    exit();
}

$temp_data = $_SESSION['temp_checkout_data']; 
$customerID = $temp_data['customer_id'] ?? null;
$shippingName = $temp_data['shipping_name'] ?? null;
$shippingAddress = $temp_data['shipping_address'] ?? null;
$shippingCity = $temp_data['shipping_city'] ?? null;
$shippingPostcode = $temp_data['shipping_postcode'] ?? null;
$shippingState = $temp_data['shipping_state'] ?? null;
$shippingPhone = $temp_data['shipping_phone'] ?? null;
$total = $temp_data['total_amount'] ?? null;
$shippingFee = $temp_data['shipping_fee'] ?? 0; 
$cartItems = $temp_data['cart_items'] ?? []; 
$error = "";

$supported_banks = [];
$result_banks = $conn->query("SELECT bank_name FROM `17_bank_details`");
if($result_banks) 
{
    while($row = $result_banks->fetch_assoc()) 
    {
        $supported_banks[] = $row['bank_name'];
    }
}

if (empty($customerID) || $total === null || empty($shippingAddress) || empty($cartItems)) 
{
    $error = "Checkout information is missing. Please return to the checkout page and try again."; 
    unset($_SESSION['temp_checkout_data']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment']) && empty($error)) 
{
    $selected_bank_input = trim($_POST['bank_selection'] ?? '');
    $account_number_input = trim($_POST['account_number'] ?? '');
    $password_input = trim($_POST['password'] ?? '');

    if (empty($selected_bank_input) || empty($account_number_input) || empty($password_input)) 
    {
        $error = "Please select a bank, and enter account number and password.";
    }

    if(empty($error)) 
    {
        $stmt_check = $conn->prepare("SELECT account_number, password FROM `17_bank_details` WHERE bank_name = ?");
        $stmt_check->bind_param("s", $selected_bank_input);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) 
        {
            $correct_details = $result_check->fetch_assoc();

            if ($account_number_input !== $correct_details['account_number'] || $password_input !== $correct_details['password']) 
            {
                $error = "Invalid Account Number or Password for " . htmlspecialchars($selected_bank_input) . ". Please try again.";
            }
        } 
        else 
        {
            $error = "System error: Bank details for " . htmlspecialchars($selected_bank_input) . " not configured.";
        }
        $stmt_check->close();
    }

    if (empty($error)) 
    {
        try 
        {
            $conn->begin_transaction();

            $tracking_number = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 11);
            $tracking_query = "INSERT INTO 07_tracking (Tracking_Number, Delivery_Status, Delivery_Address, Delivery_City, Delivery_Postcode, Delivery_State, Shipping_Fee) VALUES (?, 'pending', ?, ?, ?, ?, ?)";
            $stmt_tracking = $conn->prepare($tracking_query);
            $stmt_tracking->bind_param("sssssi", $tracking_number, $shippingAddress, $shippingCity, $shippingPostcode, $shippingState, $shippingFee);
            $stmt_tracking->execute();
            $trackingID = $conn->insert_id;
            if (!$trackingID) throw new Exception("Failed to create tracking record.");
            $stmt_tracking->close();

            $order_status_for_db = 'pending';
            $order_query = "INSERT INTO 08_order (CustomerID, TrackingID, OrderDate, OrderStatus, Shipping_Method, Shipping_Name, Shipping_Address, Shipping_City, Shipping_Postcode, Shipping_State, Shipping_Phone, Total_Price) VALUES (?, ?, NOW(), ?, 'Standard Delivery (Malaysia)', ?, ?, ?, ?, ?, ?, ?)";
            $stmt_order = $conn->prepare($order_query);
            $stmt_order->bind_param("iisssssssd", $customerID, $trackingID, $order_status_for_db, $shippingName, $shippingAddress, $shippingCity, $shippingPostcode, $shippingState, $shippingPhone, $total);
            $stmt_order->execute();
            $orderID = $conn->insert_id; 
            if (!$orderID) throw new Exception("Failed to create order.");
            $stmt_order->close();

            foreach ($cartItems as $item) 
            { 
                $item_query = "INSERT INTO 09_order_details (OrderID, ProductID, Order_Quantity, Order_Subtotal) VALUES (?, ?, ?, ?)";
                $stmt_item = $conn->prepare($item_query);
                $stmt_item->bind_param("iiid", $orderID, $item['ProductID'], $item['Quantity'], $item['Subtotal']);
                $stmt_item->execute();
                if ($stmt_item->affected_rows === 0) throw new Exception("Failed to insert order detail for ProductID: " . $item['ProductID']);
                $stmt_item->close();

                $sql_decrease_stock = "UPDATE 05_product SET Product_Stock_Quantity = Product_Stock_Quantity - ? WHERE ProductID = ? AND Product_Stock_Quantity >= ?";
                $stmt_decrease_stock = $conn->prepare($sql_decrease_stock);
                $stmt_decrease_stock->bind_param("iii", $item['Quantity'], $item['ProductID'], $item['Quantity']);
                $stmt_decrease_stock->execute();

                if ($stmt_decrease_stock->affected_rows === 0) 
                {
                    throw new Exception("Stock update failed for ProductID: " . $item['ProductID']);
                }
                $stmt_decrease_stock->close();
            }
            
            $payment_status_for_db = 'Success'; 
            $payment_type_for_db = 'Bank Payment'; 
            $payment_query = "INSERT INTO 10_payment (OrderID, Payment_Type, Amount, Payment_Date, Payment_Status) VALUES (?, ?, ?, NOW(), ?)";
            $stmt_payment = $conn->prepare($payment_query);
            $stmt_payment->bind_param("isds", $orderID, $payment_type_for_db, $total, $payment_status_for_db);
            $stmt_payment->execute();
            $paymentID = $stmt_payment->insert_id;
            if (!$paymentID) throw new Exception("Failed to create payment record.");
            $stmt_payment->close();

            $stmt_get_cartID = $conn->prepare("SELECT CartID FROM 11_cart WHERE CustomerID = ?");
            $stmt_get_cartID->bind_param("i", $customerID);
            $stmt_get_cartID->execute();
            $result_cartID = $stmt_get_cartID->get_result();
            if ($row_cartID = $result_cartID->fetch_assoc()) 
            {
                $cartID_to_clear = $row_cartID['CartID'];
                $clear_cart_items_query = "DELETE FROM 12_cart_item WHERE CartID = ?";
                $stmt_clear_items = $conn->prepare($clear_cart_items_query);
                $stmt_clear_items->bind_param("i", $cartID_to_clear);
                $stmt_clear_items->execute();
                $stmt_clear_items->close();
            }
            $stmt_get_cartID->close();

            $conn->commit(); 

            unset($_SESSION['temp_checkout_data']); 
            
            header("Location: ../order_confirmation.php?id=$orderID&success=1");
            exit();

        } 
        catch (Exception $e)
        {
            $conn->rollback(); 
            $error = "Payment processing failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FPX Online Payment</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 1rem; }
        .payment-container { background-color: #ffffff; padding: 2rem; border-radius: 1rem; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); text-align: center; max-width: 480px; width: 100%; }
        .payment-header { font-size: 1.75rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem; }
        .payment-subheader { font-size: 1rem; color: #6b7280; margin-bottom: 1.5rem; }
        .payment-details p { font-size: 1rem; color: #4b5563; margin-bottom: 0.75rem; }
        .payment-details strong { color: #111827; }
        .bank-info { background-color: #f9fafb; border: 1px solid #e5e7eb; padding: 1rem; border-radius: 0.75rem; margin-top: 1.5rem; margin-bottom: 2rem; }
        .input-group { margin-bottom: 1.5rem; text-align: left; }
        .input-group label { display: block; font-size: 0.875rem; color: #374151; margin-bottom: 0.5rem; font-weight: 500; }
        .input-group input, .input-group select { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 1rem; box-sizing: border-box; }
        .confirm-button { background-color: #1f2937; color: white; font-weight: 600; padding: 0.75rem 1.5rem; border-radius: 0.75rem; border: none; cursor: pointer; font-size: 1rem; transition: all 0.3s ease; width: 100%; margin-top: 1.5rem; }
        .confirm-button:hover { background-color: #374151; }
        .error-message { color: #ef4444; font-size: 0.875rem; margin-bottom: 1rem; background-color: #fee2e2; padding: 0.75rem; border-radius: 0.5rem; }
        .back-button { background-color: #6c757d; color: white; padding: 0.75rem 1.5rem; border-radius: 0.75rem; cursor: pointer; margin-top: 1rem; width: 100%; border:none; font-size: 1rem; }
    </style>
</head>
<body>
    <div class="payment-container">
        <h1 class="payment-header">FPX Online Banking</h1>
        
        <?php if (!empty($error)): ?>
            <p class="error-message"><?= htmlspecialchars($error) ?></p>
            <button class="back-button" onclick="window.location = '../checkout.php';">Return to Checkout</button>
        <?php else: ?>
            <div class="payment-details">
                <p>Total Amount: <strong>RM <?= number_format($total ?? 0, 2)?></strong></p>
            </div>

            <div class="bank-info">
                <p>Please select your bank and enter account details to confirm.</p>
            </div>
            
            <form method="post">
                <div class="input-group">
                    <label for="bank_selection">Select Your Bank</label>
                    <select id="bank_selection" name="bank_selection" required>
                        <option value="">-- Please choose a bank --</option>
                        <?php foreach($supported_banks as $b_name): ?>
                            <option value="<?= htmlspecialchars($b_name) ?>"><?= htmlspecialchars($b_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-group">
                    <label for="account_number">Account Number</label>
                    <input type="text" id="account_number" name="account_number" placeholder="Enter account number" required oninput="forceNumeric(this)">
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" name="confirm_payment" class="confirm-button">Confirm & Pay Now</button>
            </form>
            <button class="back-button" onclick="window.location = '../checkout.php';">Cancel and Return to Checkout</button>
        <?php endif; ?>
    </div>

    <script>
        function forceNumeric(inputElement) 
        {
            let value = inputElement.value;
            let numericValue = value.replace(/\D/g, ''); 
            
            if (value !== numericValue) 
            {
                inputElement.value = numericValue;
            }
        }
    </script>
</body>
</html>
