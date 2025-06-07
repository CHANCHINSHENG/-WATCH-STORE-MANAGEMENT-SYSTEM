<?php
session_start();
include 'db.php';

if (!isset($_SESSION['customer_id']))
{
    header("Location: customer_login.php");
    exit();
}

$customerID = $_SESSION['customer_id'];
$cartID = null;
$cart_items = [];
$subtotal = 0;
$shipping_fee = 0;
$total = 0;
$error = "";

$shipping_rules = [];
if (($handle = fopen("shipping_rules.csv", "r")) !== false)
{
    fgetcsv($handle); 
    while (($data = fgetcsv($handle, 1000, ",")) !== false)
    {
        $shipping_rules[] = [
            'start' => (int)trim($data[0]),
            'end' => (int)trim($data[1]),
            'state' => trim($data[2]),
            'fee' => (float)trim($data[3])
        ];
    }
    fclose($handle);
}

function getBankNames()
{
    $banks = [];
    if (file_exists("banks.csv") && ($handle = fopen("banks.csv", "r")) !== FALSE)
    {
        fgetcsv($handle); 
        while (($data = fgetcsv($handle)) !== FALSE)
        {
            if (isset($data[0]))
            {
                $banks[] = strtolower(trim($data[0]));
            }
        }
        fclose($handle);
    }
    return $banks;
}
$validBanks = getBankNames();

$stmt_cart = $conn->prepare("SELECT CartID FROM 11_cart WHERE CustomerID = ?");
$stmt_cart->bind_param("i", $customerID);
$stmt_cart->execute();
$result_cart = $stmt_cart->get_result();

if ($row_cart = $result_cart->fetch_assoc()) 
{
    $cartID = $row_cart['CartID'];
}
$stmt_cart->close();


if ($cartID) 
{
    $stmt_cart_items = $conn->prepare("SELECT p.ProductID, p.ProductName, p.Product_Image, p.Product_Price, ci.Quantity FROM 12_cart_item ci JOIN 05_product p ON ci.ProductID = p.ProductID WHERE ci.CartID = ?");
    $stmt_cart_items->bind_param("i", $cartID);
    $stmt_cart_items->execute();
    $result_cart_items = $stmt_cart_items->get_result();

    if ($result_cart_items->num_rows === 0) 
    {
        $cart_items = []; 
    } 
    else 
    {
        while ($row_item = $result_cart_items->fetch_assoc()) 
        {
            if ($row_item['Quantity'] > 10) 
            {
                $error = "Each product can only be bought up to 10! Please adjust your cart.";
                $cart_items = [];
                $subtotal = 0;
                break;
            }
            $row_item['Subtotal'] = $row_item['Product_Price'] * $row_item['Quantity'];
            $subtotal += $row_item['Subtotal'];
            $cart_items[] = $row_item;
        }
    }
    $stmt_cart_items->close();
}
else 
{
    $cart_items = [];
}

$name = $_POST['name'] ?? '';
$address = $_POST['address'] ?? '';
$city = $_POST['city'] ?? '';
$postcode = $_POST['postcode'] ?? '';
$State = $_POST['state'] ?? ''; 
$phone = $_POST['phone'] ?? '';
$payment_method = $_POST['payment_method'] ?? '';
$card_number = $_POST['card_number'] ?? '';
$card_bank = $_POST['card_bank'] ?? '';
$card_expiry_month = $_POST['card_expiry_month'] ?? '';
$card_expiry_year = $_POST['card_expiry_year'] ?? '';
$card_cvv = $_POST['card_cvv'] ?? '';
$cardholder_name = $_POST['cardholder_name'] ?? '';
$fpx_bank_selection = $_POST['fpx_bank_selection'] ?? '';


if (!empty($postcode) && empty($error) && !empty($cart_items)) {
    $customer_postcode_int_display = (int)$postcode;
    foreach ($shipping_rules as $rule) {
        if ($customer_postcode_int_display >= $rule['start'] && $customer_postcode_int_display <= $rule['end']) {
            $shipping_fee = $rule['fee'];
            break;
        }
    }
}
$total = (empty($cart_items) ? 0 : $subtotal) + $shipping_fee;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order']))
{
    if (empty($cart_items)) 
    { 
        $error = "Your cart is empty. Cannot place order.";
    } 
    else 
    { 
        foreach ($cart_items as $item_check) 
        {
            if ($item_check['Quantity'] > 10) {
                $error = "Each product can only be bought up to 10! Please adjust your cart before placing an order.";
                break; 
            }
        }
    }

    $name_submitted = trim($_POST['name']);
    $address_submitted = trim($_POST['address']);
    $city_submitted = trim($_POST['city']);
    $postcode_submitted = trim($_POST['postcode']);
    $state_submitted = trim($_POST['state']); 
    $phone_submitted = trim($_POST['phone']);
    $payment_method_submitted = trim($_POST['payment_method']);

    $card_number_submitted = trim($_POST['card_number'] ?? '');
    $card_bank_submitted = strtolower(trim($_POST['card_bank'] ?? ''));
    $card_expiry_month_submitted = trim($_POST['card_expiry_month'] ?? '');
    $card_expiry_year_submitted = trim($_POST['card_expiry_year'] ?? '');
    $card_cvv_submitted = trim($_POST['card_cvv'] ?? '');
    $cardholder_name_submitted = trim($_POST['cardholder_name'] ?? '');
    $fpx_bank_selection_submitted = trim($_POST['fpx_bank_selection'] ?? '');

    if (empty($error)) 
    {
        if (empty($name_submitted) || empty($address_submitted) || empty($city_submitted) || empty($postcode_submitted) || empty($state_submitted) || empty($phone_submitted) || empty($payment_method_submitted)) {
            $error = "Please fill in all required shipping and payment information.";
        }
    }

    $order_shipping_fee = 0; 
    if (!empty($postcode_submitted) && empty($error)) 
    { 
        $customer_postcode_int_order = (int)$postcode_submitted;
        foreach ($shipping_rules as $rule) 
        {
            if ($customer_postcode_int_order >= $rule['start'] && $customer_postcode_int_order <= $rule['end']) 
            {
                $order_shipping_fee = $rule['fee'];
                break;
            }
        }
    }

    $current_order_subtotal = 0;
    if (!empty($cart_items) && empty($error)) 
    { 
        foreach ($cart_items as $item_for_total) 
        {
            $item_subtotal_for_order = $item_for_total['Product_Price'] * $item_for_total['Quantity'];
            $current_order_subtotal += $item_subtotal_for_order;
        }
    } 
    else if (empty($error)) 
    {
        $error = "Your cart is empty. Cannot process the order.";
    }
    $order_total_price = $current_order_subtotal + $order_shipping_fee;


    $target_bank_redirect_file = ''; 

    if (empty($error)) 
    {
        if ($payment_method_submitted === 'Visa') 
        {
            $cleaned_card_number = str_replace(' ', '', $card_number_submitted);
            if (empty($cleaned_card_number) || strlen($cleaned_card_number) !== 16 || !ctype_digit($cleaned_card_number)) 
            {
                $error = "Please enter a valid 16-digit card number.";
            } 
            elseif (empty($card_bank_submitted) || !in_array($card_bank_submitted, $validBanks)) 
            {
                $error = "Please enter a valid issuing bank name.";
            } 
            elseif (empty($card_expiry_month_submitted) || empty($card_expiry_year_submitted) || empty($card_cvv_submitted) || empty($cardholder_name_submitted)) 
            {
                $error = "Please fill in complete card details (expiry date, CVV, cardholder name).";
            } 
            else 
            { 
                $currentYearLastTwoDigits = (int)date('y');
                $currentMonth = (int)date('m');
                $inputYearLastTwoDigits = (int)$card_expiry_year_submitted;
                $inputMonth = (int)$card_expiry_month_submitted;

                if (strlen($card_expiry_month_submitted) !== 2 || $inputMonth < 1 || $inputMonth > 12) 
                {
                     $error = "Card expiry month format is incorrect (MM).";
                } 
                elseif (strlen($card_expiry_year_submitted) !== 2) 
                { 
                    $error = "Card expiry year format is incorrect (YY).";
                } 
                elseif ($inputYearLastTwoDigits < $currentYearLastTwoDigits || ($inputYearLastTwoDigits === $currentYearLastTwoDigits && $inputMonth < $currentMonth)) 
                {
                    $error = "Bank card has expired.";
                }
            }
             if (empty($error) && (strlen($card_cvv_submitted) < 3 || strlen($card_cvv_submitted) > 4 || !ctype_digit($card_cvv_submitted) )) 
            { 
                $error = "CVV format is incorrect (3-4 digits).";
            }

        } 
        elseif ($payment_method_submitted === 'Bank Payment') 
        {
            if (empty($fpx_bank_selection_submitted)) 
            {
                $error = "Please select your online bank.";
            } 
            else 
            {
                switch ($fpx_bank_selection_submitted) 
                {
                    case 'Maybank': $target_bank_redirect_file = 'Maybank_payment.php'; break;
                    case 'CIMB Clicks': $target_bank_redirect_file = 'CIMB_payment.php'; break;
                    case 'Public Bank': $target_bank_redirect_file = 'Publicbank_payment.php'; break;
                    case 'RHB Bank': $target_bank_redirect_file = 'RHBbank_payment.php'; break;
                    case 'Bank Islam': $target_bank_redirect_file = 'Islambank_payment.php'; break;
                    case 'Hong Leong Bank': $target_bank_redirect_file = 'HongLeongbank_payment.php'; break;
                    case 'AmBank': $target_bank_redirect_file = 'AmBank_payment.php'; break;
                    case 'Alliance Bank': $target_bank_redirect_file = 'AllianceBank_payment.php'; break;
                    case 'BSN': $target_bank_redirect_file = 'BSN_payment.php'; break;
                    default:
                        $error = "Invalid bank selected or payment page not available for the selected bank.";
                        error_log("FPX Error: No redirect file defined for bank: " . htmlspecialchars($fpx_bank_selection_submitted));
                        break;
                }
                if (!empty($target_bank_redirect_file) && empty($error)) 
                { 
                    $target_bank_redirect_file = 'Payment_Method_Bank/' . $target_bank_redirect_file;
                } 
                elseif (empty($error) && empty($target_bank_redirect_file)) 
                { 
                     $error = "Payment page for the selected bank is not configured correctly.";
                }
            }
        }
    }


    if (empty($error))
    {
        if ($payment_method_submitted === 'Bank Payment') 
        {
            if (!empty($target_bank_redirect_file)) 
            {
                $_SESSION['temp_checkout_data'] = 
                [
                    'total_amount' => $order_total_price,
                    'fpx_bank_selection' => $fpx_bank_selection_submitted,
                    'customer_id' => $customerID,
                    'shipping_name' => $name_submitted,
                    'shipping_address' => $address_submitted,
                    'shipping_city' => $city_submitted,
                    'shipping_postcode' => $postcode_submitted,
                    'shipping_state' => $state_submitted,
                    'shipping_phone' => $phone_submitted,
                    'shipping_fee' => $order_shipping_fee,
                    'cart_items' => $cart_items, 
                ];
                header("Location: " . $target_bank_redirect_file);
                exit();
            } 
            else 
            {
                if(empty($error)) $error = "Could not determine bank page for redirection. Please contact support.";
                error_log("FPX Redirection Critical Error: target_bank_redirect_file was empty before redirection attempt for bank: " . htmlspecialchars($fpx_bank_selection_submitted));
            }
        } 
        else 
        { 
            $conn->begin_transaction();
            try 
            {
                $tracking_number = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 11);
                $tracking_query = "INSERT INTO 06_tracking (Tracking_Number, Delivery_Status, Delivery_Address, Delivery_City, Delivery_Postcode, Delivery_State, Shipping_Fee) VALUES (?, 'pending', ?, ?, ?, ?, ?)";
                $stmt_tracking = $conn->prepare($tracking_query);

                if (!$stmt_tracking) throw new Exception("Tracking statement preparation failed: " . $conn->error);
                $stmt_tracking->bind_param("sssssi", $tracking_number, $address_submitted, $city_submitted, $postcode_submitted, $state_submitted, $order_shipping_fee);
                $stmt_tracking->execute();
                $trackingID = $conn->insert_id;

                if (!$trackingID) throw new Exception("Failed to create tracking record. SQL Error: " . $stmt_tracking->error);
                $stmt_tracking->close();

                $order_status_for_db = 'pending'; 
                $order_query = "INSERT INTO 07_order (CustomerID, TrackingID, OrderDate, OrderStatus, Shipping_Method, Shipping_Name, Shipping_Address, Shipping_City, Shipping_Postcode, Shipping_State, Shipping_Phone, Total_Price) VALUES (?, ?, NOW(), ?, 'Standard Delivery (Malaysia)', ?, ?, ?, ?, ?, ?, ?)";
                $stmt_order = $conn->prepare($order_query);

                if (!$stmt_order) throw new Exception("Order statement preparation failed: " . $conn->error);
                $stmt_order->bind_param("iisssssssd", $customerID, $trackingID, $order_status_for_db, $name_submitted, $address_submitted, $city_submitted, $postcode_submitted, $state_submitted, $phone_submitted, $order_total_price);
                $stmt_order->execute();
                $orderID = $conn->insert_id;

                if (!$orderID) throw new Exception("Failed to create order record. SQL Error: " . $stmt_order->error);
                $stmt_order->close();

                foreach ($cart_items as $item) 
                {
                    $item_subtotal_for_db = $item['Product_Price'] * $item['Quantity']; // Recalculate for safety
                    $item_query = "INSERT INTO 08_order_details (OrderID, ProductID, Order_Quantity, Order_Subtotal) VALUES (?, ?, ?, ?)";
                    $stmt_item = $conn->prepare($item_query);

                    if (!$stmt_item) throw new Exception("Order detail statement preparation failed for ProductID " . $item['ProductID'] . ": " . $conn->error);
                    $stmt_item->bind_param("iiid", $orderID, $item['ProductID'], $item['Quantity'], $item_subtotal_for_db);
                    $stmt_item->execute();

                    if ($stmt_item->affected_rows === 0) throw new Exception("Failed to insert order detail for ProductID: " . $item['ProductID'] . ". SQL Error: " . $stmt_item->error);
                    $stmt_item->close();

                    $sql_decrease_stock = "UPDATE 05_product SET Product_Stock_Quantity = Product_Stock_Quantity - ? WHERE ProductID = ? AND Product_Stock_Quantity >= ?";
                    $stmt_decrease_stock = $conn->prepare($sql_decrease_stock);

                    if (!$stmt_decrease_stock) throw new Exception("Stock update statement preparation failed for ProductID " . $item['ProductID'] . ": " . $conn->error);
                    $stmt_decrease_stock->bind_param("iii", $item['Quantity'], $item['ProductID'], $item['Quantity']);
                    $stmt_decrease_stock->execute();

                    if ($stmt_decrease_stock->affected_rows === 0) 
                    {
                        $check_stock_stmt = $conn->prepare("SELECT Product_Stock_Quantity FROM 05_product WHERE ProductID = ?");
                        $check_stock_stmt->bind_param("i", $item['ProductID']);
                        $check_stock_stmt->execute();
                        $stock_result = $check_stock_stmt->get_result()->fetch_assoc();
                        $check_stock_stmt->close();
                        $current_stock = $stock_result ? $stock_result['Product_Stock_Quantity'] : 'unknown';
                        throw new Exception("Stock update failed for ProductID: " . $item['ProductID'] . " (Visa/COD). Requested: ".$item['Quantity'].", Available: ".$current_stock.". SQL Error: " . $stmt_decrease_stock->error);
                    }
                    $stmt_decrease_stock->close();
                }

                $payment_record_id_for_opm = null;
                $payment_table_type_for_opm = null;

                $payment_status_for_db = ($payment_method_submitted === 'Visa') ? 'Success' : 'Pending';
                $card_type_for_db = null;
                $card_number_masked_for_db = null;
                $card_expiry_month_for_db = null;
                $card_expiry_year_for_db = null;
                $card_holder_name_for_db = null;
                $bank_name_for_db = null; 

                if ($payment_method_submitted === 'Visa') 
                {
                    $card_type_for_db = 'Visa';
                    $cleaned_card_num_visa = str_replace(' ', '', $card_number_submitted);
                    $card_number_masked_for_db = substr($cleaned_card_num_visa, -4);
                    $card_expiry_month_for_db = $card_expiry_month_submitted;
                    $card_expiry_year_for_db = $card_expiry_year_submitted;
                    $card_holder_name_for_db = $cardholder_name_submitted;
                    $bank_name_for_db = $card_bank_submitted; 
                }

                $payment_query = "INSERT INTO 09_payment (OrderID, Payment_Type, TransactionID, Amount, Payment_Date, Payment_Status, Card_Type, Card_Number_Masked, Card_Expiry_Month, Card_Expiry_Year, Card_Holder_Name, Bank_Name) VALUES (?, ?, NULL, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)";
                $stmt_payment = $conn->prepare($payment_query);

                if (!$stmt_payment) throw new Exception("Payment statement preparation failed: " . $conn->error);
                $stmt_payment->bind_param("isdsssssss", $orderID, $payment_method_submitted, $order_total_price, $payment_status_for_db, $card_type_for_db, $card_number_masked_for_db, $card_expiry_month_for_db, $card_expiry_year_for_db, $card_holder_name_for_db, $bank_name_for_db);
                $stmt_payment->execute();
                $paymentID = $stmt_payment->insert_id;

                if (!$paymentID) throw new Exception("Failed to create payment record. SQL Error: " . $stmt_payment->error);
                $stmt_payment->close();

                $payment_record_id_for_opm = $paymentID;
                $payment_table_type_for_opm = '09_payment';

                $order_payment_method_query = "INSERT INTO 14_order_payment_method (OrderID, Payment_Method_Type, Payment_Table_Type, Payment_Record_ID) VALUES (?, ?, ?, ?)";
                $stmt_opm = $conn->prepare($order_payment_method_query);

                if (!$stmt_opm) throw new Exception("Order payment method link statement preparation failed: " . $conn->error);

                $payment_method_type_for_opm = 'Visa'; 
                $payment_table_type_for_opm = '09_payment'; 
                
                $stmt_opm->bind_param("isss", $orderID, $payment_method_submitted, $payment_table_type_for_opm, $payment_record_id_for_opm);
                $stmt_opm->execute();

                if ($stmt_opm->affected_rows === 0) throw new Exception("Failed to create order payment method link. SQL Error: " . $stmt_opm->error);
                $stmt_opm->close();

                if($cartID) 
                {
                    $clear_cart_query = "DELETE FROM 12_cart_item WHERE CartID = ?";
                    $stmt_clear_cart = $conn->prepare($clear_cart_query);

                    if (!$stmt_clear_cart) 
                    {
                        error_log("Cart clearing statement preparation failed: " . $conn->error); 
                    } 
                    else 
                    {
                        $stmt_clear_cart->bind_param("i", $cartID);
                        $stmt_clear_cart->execute();
                        $stmt_clear_cart->close();
                    }
                }

                $conn->commit(); 
                header("Location: order_confirmation.php?id=$orderID&success=1");
                exit();

            } 
            catch (Exception $e) 
            {
                $conn->rollback(); 
                $error = "Order processing failed. Please try again. Details: " . $e->getMessage();
                error_log("Order Processing Exception (Visa/COD): " . $e->getMessage() . " - SQL State: " . $conn->sqlstate . " - Trace: " . $e->getTraceAsString());
            }
        }
    } 
    else 
    {
        error_log("Checkout.php: Validation failed during POST. Error: " . htmlspecialchars($error));
    }
} 
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
    <link rel="stylesheet" href="Checkout.css">
</head>
<body>
    <div class="checkout-container">
        <div class="checkout-left">
            <h1>Checkout</h1>

            <?php if (!empty($error)): ?>
                <p class="error-php" style="color: red; font-weight: bold; margin-bottom: 10px;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <p class="error-js" style="color: red; font-weight: bold; margin-bottom: 10px; display: none;"></p>

            <?php if (!empty($cart_items)): ?>
            <form method="post" class="checkout-form">
                <h3>Shipping Information</h3>
                <input type="text" name="name" placeholder="Full Name" value="<?= htmlspecialchars($name ?? '') ?>" required>
                <input type="text" name="address" placeholder="Address" value="<?= htmlspecialchars($address ?? '') ?>" required>
                <input type="text" name="city" placeholder="City" value="<?= htmlspecialchars($city ?? '') ?>" required>
                <input type="text" name="postcode" id="postcode_input" placeholder="Postcode" value="<?= htmlspecialchars($postcode ?? '') ?>" required oninput="validatePostcode()">
                <span id="postcode_error" class="error-message" style="color: red;"></span>
                <input type="text" name="state" placeholder="State" value="<?= htmlspecialchars($State ?? '') ?>" required>
                <input type="text" name="phone" placeholder="Phone Number" value="<?= htmlspecialchars($phone ?? '') ?>" required>

                <h3>Payment Method</h3>
                <select name="payment_method" id="payment_method_select" required>
                    <option value="">Select a payment method</option>
                    <option value="Visa" <?= ($payment_method == 'Visa') ? 'selected' : '' ?>>Visa</option>
                    <option value="Bank Payment" <?= ($payment_method == 'Bank Payment') ? 'selected' : '' ?>>Bank Payment</option>
                    <option value="COD" <?= ($payment_method == 'COD') ? 'selected' : '' ?>>Cash On Delivery</option>
                </select>

                <div id="credit_card_fields" style="display: none;">
                    <input type="text" name="card_number" id="card_number_input" placeholder="Card Number (16 digits)" value="<?= htmlspecialchars($card_number ?? '') ?>" oninput="validateCardInfo()">
                    <span id="card_number_error" class="error-message" style="color: red;"></span>

                    <div class="card-expiry-cvv">
                        <input type="text" name="card_expiry_month" id="card_expiry_month_input" placeholder="MM" maxlength="2" value="<?= htmlspecialchars($card_expiry_month ?? '') ?>" oninput="validateCardInfo()">
                        <input type="text" name="card_expiry_year" id="card_expiry_year_input" placeholder="YY" maxlength="2" value="<?= htmlspecialchars($card_expiry_year ?? '') ?>" oninput="validateCardInfo()">
                    </div>
                    <span id="card_expiry_error" class="error-message" style="color: red;"></span>

                    <input type="text" name="card_cvv" id="card_cvv_input" placeholder="CVV (3-4 digits)" maxlength="4" value="<?= htmlspecialchars($card_cvv ?? '') ?>" onblur="validateCardInfo()">
                    <span id="card_cvv_error" class="error-message" style="color: red;"></span>

                    <input type="text" name="cardholder_name" id="cardholder_name_input" placeholder="Cardholder Name" value="<?= htmlspecialchars($cardholder_name ?? '') ?>" onblur="validateCardInfo()">
                    <span id="cardholder_name_error" class="error-message" style="color: red;"></span>

                    <input type="text" name="card_bank" id="card_bank_input" placeholder="Bank Name" value="<?= htmlspecialchars($card_bank ?? '') ?>" onblur="validateBankName()">
                    <span id="card_bank_error" class="error-message" style="color: red;"></span>
                </div>

                <div id="bank_payment_fields" style="display: none;">
                    <h4>Select Your Bank for Online Banking</h4>
                    <select name="fpx_bank_selection" id="fpx_bank_selection_input">
                        <option value="">Select Bank</option>
                        <option value="Maybank" <?= ($fpx_bank_selection == 'Maybank') ? 'selected' : '' ?>>Maybank</option>
                        <option value="CIMB Clicks" <?= ($fpx_bank_selection == 'CIMB Clicks') ? 'selected' : '' ?>>CIMB Clicks</option>
                        <option value="Public Bank" <?= ($fpx_bank_selection == 'Public Bank') ? 'selected' : '' ?>>Public Bank Berhad</option>
                        <option value="RHB Bank" <?= ($fpx_bank_selection == 'RHB Bank') ? 'selected' : '' ?>>RHB Bank</option>
                        <option value="Bank Islam" <?= ($fpx_bank_selection == 'Bank Islam') ? 'selected' : '' ?>>Bank Islam</option>
                        <option value="Hong Leong Bank" <?= ($fpx_bank_selection == 'Hong Leong Bank') ? 'selected' : '' ?>>Hong Leong Bank</option>
                        <option value="AmBank" <?= ($fpx_bank_selection == 'AmBank') ? 'selected' : '' ?>>AmBank</option>
                        <option value="Alliance Bank" <?= ($fpx_bank_selection == 'Alliance Bank') ? 'selected' : '' ?>>Alliance Bank</option>
                        <option value="BSN" <?= ($fpx_bank_selection == 'BSN') ? 'selected' : '' ?>>Bank Simpanan Nasional (BSN)</option>
                    </select>
                    <p class="payment-instruction">You will be redirected to your selected bank's website to complete your payment. Do not close this window.</p>
                </div>

                <button type="submit" name="place_order" class="place-order-button">Place Order</button>
            </form>

            <?php else: ?>
                <p>Your cart is empty. Please add items to your cart before checking out.</p>
                 <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const form = document.querySelector('.checkout-form');
                        if (form) {
                            Array.from(form.elements).forEach(el => el.disabled = true);
                        }
                        const placeOrderButton = document.querySelector('.place-order-button');
                        if(placeOrderButton) placeOrderButton.disabled = true;
                    });
                </script>
            <?php endif; ?>
        </div>

        <div class="checkout-right">
            <h2>Cart Summary</h2>
            <?php if (!empty($cart_items)): ?>
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <img src="admin_addproduct_include/<?= htmlspecialchars($item['Product_Image']) ?>" alt="<?= htmlspecialchars($item['ProductName']) ?>">
                        <div class="cart-item-details">
                            <h4><?= htmlspecialchars($item['ProductName']) ?></h4>
                            <p>Quantity: <?= htmlspecialchars($item['Quantity']) ?></p>
                            <p>Subtotal: RM <?= number_format($item['Subtotal'], 2) ?></p>
                        </div>
                    </div>
                <?php endforeach;?>
            </div>
            <div class="cart-summary">
                <p><strong>Subtotal     :</strong> RM <span class="subtotal"><?= number_format($subtotal, 2) ?></span></p>
                <p><strong>Shipping Fee :</strong> RM <span class="shipping-fee"><?= number_format($shipping_fee, 2) ?></span></p>
                <p><strong>Total        :</strong> <span class="total-price">RM <?= number_format($total, 2) ?></span></p>
            </div>
            <?php else: ?>
                <p>Your cart summary will appear here once you add items.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const displayMainError = (message) => {
            const errorParagraph = document.querySelector('.checkout-left .error-js');
            const phpErrorParagraph = document.querySelector('.checkout-left .error-php');

            if (errorParagraph) 
            {
                errorParagraph.textContent = message;
                errorParagraph.style.display = message ? 'block' : 'none';
                if (message) 
                {
                    errorParagraph.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    if (phpErrorParagraph) phpErrorParagraph.style.display = 'none';
                }
                else 
                {
                    if (phpErrorParagraph && phpErrorParagraph.textContent.trim() !== '') 
                    {
                        phpErrorParagraph.style.display = 'block';
                    }
                }
            }
        };

        const clearAllPaymentErrors = () => {
            if (cardNumberError) cardNumberError.textContent = '';
            if (expiryError) expiryError.textContent = '';
            if (cvvError) cvvError.textContent = '';
            if (cardholderNameError) cardholderNameError.textContent = '';
            if (cardBankError) cardBankError.textContent = '';
        };

        const paymentMethodSelect = document.getElementById('payment_method_select');
        const creditCardFields = document.getElementById('credit_card_fields');
        const bankPaymentFields = document.getElementById('bank_payment_fields');
        const fpxBankSelectionInput = document.getElementById('fpx_bank_selection_input');

        let initialSubtotal = 0;
        const subtotalElement = document.querySelector(".subtotal");

        if (subtotalElement) 
        {
            initialSubtotal = parseFloat(subtotalElement.textContent.replace('RM', '').trim().replace(',', '')) || 0;
        }

        const shippingRules = <?= json_encode($shipping_rules) ?>;
        const phpValidBankNames = <?= json_encode($validBanks) ?>;

        const postcodeInput = document.getElementById('postcode_input');
        const cardNumberInput = document.getElementById('card_number_input');
        const expiryMonthInput = document.getElementById('card_expiry_month_input');
        const expiryYearInput = document.getElementById('card_expiry_year_input');
        const cvvInput = document.getElementById('card_cvv_input');
        const cardholderNameInput = document.getElementById('cardholder_name_input');
        const cardBankInput = document.getElementById('card_bank_input');

        const postcodeError = document.getElementById('postcode_error');
        const cardNumberError = document.getElementById('card_number_error');
        const expiryError = document.getElementById('card_expiry_error');
        const cvvError = document.getElementById('card_cvv_error');
        const cardholderNameError = document.getElementById('cardholder_name_error');
        const cardBankError = document.getElementById('card_bank_error');

        function togglePaymentFields() 
        {
            const selectedMethod = paymentMethodSelect.value;
            creditCardFields.style.display = 'none';
            bankPaymentFields.style.display = 'none';
            displayMainError('');
            clearAllPaymentErrors();

            if (selectedMethod === 'Visa') 
            {
                creditCardFields.style.display = 'block';
            } 
            else if (selectedMethod === 'Bank Payment') 
            {
                bankPaymentFields.style.display = 'block';
            }
        }

        function updateShippingAndTotalDisplay(postcodeValue, isPostcodeFieldValid) 
        {
            const shippingFeeDisplay = document.querySelector(".shipping-fee");
            const totalDisplay = document.querySelector(".total-price");
            let calculatedShippingFee = 0;

            if (isPostcodeFieldValid && postcodeValue !== '') 
            {
                const customerPostcodeInt = parseInt(postcodeValue, 10);
                let foundRule = false;

                for (let i = 0; i < shippingRules.length; i++) 
                {
                    let rule = shippingRules[i];

                    if (customerPostcodeInt >= rule.start && customerPostcodeInt <= rule.end) 
                    {
                        calculatedShippingFee = rule.fee;
                        foundRule = true;
                        break;
                    }
                }
                if (!foundRule) calculatedShippingFee = 0;
            } 
            else 
            {
                calculatedShippingFee = 0;
            }

            if (shippingFeeDisplay) shippingFeeDisplay.textContent = calculatedShippingFee.toFixed(2);

            const currentTotal = initialSubtotal + calculatedShippingFee;
            if (totalDisplay) totalDisplay.textContent = "RM " + currentTotal.toFixed(2);
        }

        function validatePostcode() 
        {
            const postcodeValue = postcodeInput.value.trim();
            let isValid = true;
            if (postcodeError) postcodeError.textContent = '';

            if (postcodeValue === '') 
            {
                if (postcodeError) postcodeError.textContent = "Postcode is required.";
                isValid = false;
            } 
            else if (isNaN(postcodeValue) || !/^\d+$/.test(postcodeValue)) 
            {
                if (postcodeError) postcodeError.textContent = "Postcode must be numeric.";
                isValid = false;
            } 
            else if (postcodeValue.length > 6) 
            {
                if (postcodeError) postcodeError.textContent = "Postcode cannot exceed 6 digits.";
                isValid = false;
            } 
            else if (postcodeValue.length < 5) 
            {
                if (postcodeError) postcodeError.textContent = "Postcode must be at least 5 digits.";
                isValid = false;
            }
            updateShippingAndTotalDisplay(postcodeValue, isValid);
            return isValid;
        }

        function validateCardInfo() 
        {
            let isValid = true;
            const cardNumber = cardNumberInput.value.trim();
            const expiryMonth = expiryMonthInput.value.trim();
            const expiryYear = expiryYearInput.value.trim();
            const cvv = cvvInput.value.trim();
            const cardholderName = cardholderNameInput.value.trim();
            const cleanedCardNumber = cardNumber.replace(/\s/g, '');

            if (cardNumberError) cardNumberError.textContent = '';
            if (expiryError) expiryError.textContent = '';
            if (cvvError) cvvError.textContent = '';
            if (cardholderNameError) cardholderNameError.textContent = '';

            if (cleanedCardNumber === '') 
            {
                if (cardNumberError) cardNumberError.textContent = "Card number is required."; isValid = false;
            }
            else if (!/^\d{16}$/.test(cleanedCardNumber)) 
            {
                if (cardNumberError) cardNumberError.textContent = "Card number must be 16 digits."; isValid = false;
            }

            if (expiryMonth === '' || expiryYear === '') 
            {
                if (expiryError) expiryError.textContent = "Expiry date is required."; isValid = false;
            } 
            else if (!/^\d{2}$/.test(expiryMonth) || parseInt(expiryMonth) < 1 || parseInt(expiryMonth) > 12) 
            {
                if (expiryError) expiryError.textContent = "Invalid month (MM)."; isValid = false;
            } 
            else if (!/^\d{2}$/.test(expiryYear)) 
            {
                if (expiryError) expiryError.textContent = "Invalid year (YY)."; isValid = false;
            } 
            else 
            {
                const currentYearLastTwoDigits = new Date().getFullYear() % 100;
                const currentMonth = new Date().getMonth() + 1;
                const inputYearLastTwoDigits = parseInt(expiryYear);
                const inputMonth = parseInt(expiryMonth);

                if (inputYearLastTwoDigits < currentYearLastTwoDigits || (inputYearLastTwoDigits === currentYearLastTwoDigits && inputMonth < currentMonth)) 
                {
                    if (expiryError) expiryError.textContent = "Bank card has expired."; isValid = false;
                }
            }

            if (cvv === '') 
            {
                if (cvvError) cvvError.textContent = "CVV is required."; isValid = false;
            } 
            else if (!/^\d{3,4}$/.test(cvv)) 
            {
                if (cvvError) cvvError.textContent = "CVV must be 3 or 4 digits."; isValid = false;
            }

            if (cardholderName === '') 
            {
                if (cardholderNameError) cardholderNameError.textContent = "Cardholder name is required."; isValid = false;
            } 
            else if (!/^[a-zA-Z\s]+$/.test(cardholderName)) 
            {
                if (cardholderNameError) cardholderNameError.textContent = "Cardholder name can only contain letters and spaces."; isValid = false;
            }
            return isValid;
        }

        function validateBankName() 
        {
            let isValid = true;
            const bankName = cardBankInput.value.trim();
            if (cardBankError) cardBankError.textContent = '';

            if (bankName === '') 
            {
                if (cardBankError) cardBankError.textContent = "Bank name is required."; isValid = false;
            } 
            else if (!/^[a-zA-Z\s]+$/.test(bankName)) 
            {
                if (cardBankError) cardBankError.textContent = "Bank name can only contain letters and spaces."; isValid = false;
            } 
            else if (!phpValidBankNames.includes(bankName.toLowerCase())) 
            {
                if (cardBankError) cardBankError.textContent = "Invalid bank name. Please ensure it matches an accepted bank."; isValid = false;
            }
            return isValid;
        }

        if (paymentMethodSelect) paymentMethodSelect.addEventListener('change', togglePaymentFields);

        if (postcodeInput) 
        {
             postcodeInput.addEventListener('input', validatePostcode);
             postcodeInput.addEventListener('blur', validatePostcode);
        }

        if (cardNumberInput) cardNumberInput.addEventListener('input', validateCardInfo);
        if (expiryMonthInput) expiryMonthInput.addEventListener('input', validateCardInfo);
        if (expiryYearInput) expiryYearInput.addEventListener('input', validateCardInfo);
        if (cvvInput) cvvInput.addEventListener('blur', validateCardInfo);
        if (cardholderNameInput) cardholderNameInput.addEventListener('blur', validateCardInfo);
        if (cardBankInput) cardBankInput.addEventListener('blur', validateBankName);

        document.addEventListener('DOMContentLoaded', () => {
            const initialPaymentMethod = "<?= htmlspecialchars($payment_method ?? '') ?>";

            if (paymentMethodSelect) 
            {
                 paymentMethodSelect.value = initialPaymentMethod || '';
            }

            togglePaymentFields();
            validatePostcode();
            clearAllPaymentErrors();
        });

        const form = document.querySelector('.checkout-form');
        if (form) 
        {
            form.addEventListener('submit', function(event) 
            {
                let formIsValid = true;
                displayMainError('');
                clearAllPaymentErrors();

                const nameInput = document.querySelector("input[name='name']");
                const addressInput = document.querySelector("input[name='address']");
                const cityInput = document.querySelector("input[name='city']");
                const stateInput = document.querySelector("input[name='state']");
                const phoneInput = document.querySelector("input[name='phone']");

                if (!nameInput.value.trim()) 
                { 
                    displayMainError("Please enter your full name."); formIsValid = false; 
                }
                else if (!addressInput.value.trim()) 
                { 
                    displayMainError("Please enter your address."); formIsValid = false; 
                }
                else if (!cityInput.value.trim()) 
                { 
                    displayMainError("Please enter your city."); formIsValid = false; 
                }
                else if (!stateInput.value.trim()) 
                { 
                    displayMainError("Please enter your state/province."); formIsValid = false; 
                }
                else if (!phoneInput.value.trim()) 
                { 
                    displayMainError("Please enter your phone number."); formIsValid = false; 
                }

                if (formIsValid && !validatePostcode()) 
                {
                    formIsValid = false;
                }

                const selectedMethod = paymentMethodSelect.value;
                if (formIsValid && selectedMethod === '') 
                {
                    displayMainError("Please select a payment method.");
                    paymentMethodSelect.focus();
                    formIsValid = false;
                }

                if (formIsValid) 
                {
                    if (selectedMethod === 'Visa') 
                    {
                        const cardInfoValid = validateCardInfo();
                        const bankNameValid = validateBankName();

                        if (!cardInfoValid || !bankNameValid) 
                        {
                            formIsValid = false;
                            if (!cardInfoValid && !bankNameValid) displayMainError("Please correct your Visa card details and bank name.");
                            else if (!cardInfoValid) displayMainError("Please correct your Visa card details.");
                            else if (!bankNameValid) displayMainError("Please correct your bank name.");
                        }
                    } 
                    else if (selectedMethod === 'Bank Payment') 
                    {
                        if (fpx_bank_selection_input.value === "") 
                        {
                            displayMainError("Please select your online bank.");
                            fpx_bank_selection_input.focus();
                            formIsValid = false;
                        }
                    }
                }

                if (!formIsValid) 
                {
                    event.preventDefault();
                }
            });
        }
    </script>
</body>
</html>