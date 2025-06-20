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

$malaysian_locations = 
[

    'Johor' => 
    [
        'Batu Pahat',
        'Johor Bahru',
        'Kluang',
        'Kota Tinggi',
        'Kulai',
        'Mersing',
        'Muar',
        'Pasir Gudang',
        'Pontian',
        'Segamat',
        'Skudai',
        'Tangkak'
    ],

    'Kedah' => 
    [
        'Alor Setar',
        'Baling',
        'Jitra',
        'Kulim',
        'Kuala Nerang',
        'Langkawi',
        'Pendang',
        'Sungai Petani'
    ],

    'Kelantan' => 
    [
        'Bachok',
        'Gua Musang',
        'Jeli',
        'Kota Bharu',
        'Kuala Krai',
        'Machang',
        'Pasir Mas',
        'Pasir Puteh',
        'Tanah Merah',
        'Tumpat'
    ],

    'Kuala Lumpur' => 
    [
        'Kuala Lumpur'
    ],

    'Labuan' => 
    [
        'Labuan'
    ],

    'Melaka' => 
    [
        'Alor Gajah',
        'Jasin',
        'Melaka City'
    ],

    'Negeri Sembilan' => 
    [
        'Jelebu',
        'Jempol',
        'Kuala Pilah',
        'Nilai',
        'Port Dickson',
        'Rembau',
        'Seremban',
        'Tampin'
    ],

    'Pahang' => 
    [
        'Bentong',
        'Bera',
        'Cameron Highlands',
        'Genting Highlands',
        'Jerantut',
        'Kuantan',
        'Kuala Lipis',
        'Maran',
        'Pekan',
        'Raub',
        'Rompin',
        'Temerloh'
    ],

    'Penang' => 
    [
        'Bayan Lepas',
        'Bukit Mertajam',
        'Butterworth',
        'George Town',
        'Seberang Perai'
    ],

    'Perak' => 
    [
        'Bagan Serai',
        'Batu Gajah',
        'Ipoh',
        'Kampar',
        'Kuala Kangsar',
        'Lumut',
        'Sitiawan',
        'Taiping',
        'Tanjung Malim',
        'Teluk Intan'
    ],

    'Perlis' =>
    [
        'Arau',
        'Kangar',
        'Kuala Perlis',
        'Padang Besar'
    ],

    'Putrajaya' =>
    [
        'Putrajaya'
    ],

    'Sabah' => 
    [
        'Beaufort',
        'Keningau',
        'Kota Belud',
        'Kota Kinabalu',
        'Kudat',
        'Lahad Datu',
        'Papar',
        'Penampang',
        'Ranau',
        'Sandakan',
        'Semporna',
        'Tawau',
        'Tenom'
    ],

    'Sarawak' => 
    [
        'Betong',
        'Bintulu',
        'Kapit',
        'Kuching',
        'Limbang',
        'Miri',
        'Mukah',
        'Samarahan',
        'Serian',
        'Sibu',
        'Sri Aman'
    ],

    'Selangor' => 
    [
        'Ampang Jaya',
        'Bangi',
        'Banting',
        'Cyberjaya',
        'Kajang',
        'Klang',
        'Kuala Selangor',
        'Petaling Jaya',
        'Puchong',
        'Rawang',
        'Sabak Bernam',
        'Sekinchan',
        'Selayang',
        'Sepang',
        'Shah Alam',
        'Subang Jaya'
    ],

    'Terengganu' => 
    [
        'Dungun',
        'Hulu Terengganu',
        'Kemaman',
        'Kuala Besut',
        'Kuala Terengganu',
        'Marang'
    ]
];

$registered_address = null;
$stmt_customer_details = $conn->prepare("SELECT Cust_First_Name, Cust_Last_Name, Cust_Address, Cust_City, Cust_Postcode, Cust_State, Cust_PhoneNumber FROM `02_customer` WHERE CustomerID = ?");
$stmt_customer_details->bind_param("i", $customerID);
$stmt_customer_details->execute();
$result_customer_details = $stmt_customer_details->get_result();

if ($row_customer = $result_customer_details->fetch_assoc()) 
{
    $registered_address = $row_customer;
}
$stmt_customer_details->close();

$shipping_rules = [];
if (($handle = fopen("shipping_rules.csv", "r")) !== false) 
{
    fgetcsv($handle); 
    while (($data = fgetcsv($handle, 1000, ",")) !== false) 
    {
        $shipping_rules[] = 
        [
            'start' => (int)trim($data[0]),
            'end' => (int)trim($data[1]),
            'state' => trim($data[2]),
            'fee' => (float)trim($data[3])
        ];
    }
    fclose($handle);
}

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
{$stmt_cart_items = $conn->prepare("
    SELECT 
        p.ProductID, 
        p.ProductName, 
        (SELECT ImagePath FROM 06_product_images WHERE ProductID = p.ProductID AND IsPrimary = 1 LIMIT 1) AS Product_Image, 
        p.Product_Price, 
        ci.Quantity 
    FROM 12_cart_item ci 
    JOIN 05_product p ON ci.ProductID = p.ProductID 
    WHERE ci.CartID = ?
");
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

$saved_data = []; 

if (isset($_SESSION['temp_checkout_data']) && isset($_SESSION['temp_checkout_data']['saved_for_customer_id']) && $_SESSION['temp_checkout_data']['saved_for_customer_id'] == $customerID) 
{
    $saved_data = $_SESSION['temp_checkout_data'];
} 
else 
{
    unset($_SESSION['temp_checkout_data']);
}

$name = $_POST['name'] ?? $saved_data['shipping_name'] ?? '';
$address = $_POST['address'] ?? $saved_data['shipping_address'] ?? '';
$city = $_POST['city'] ?? $saved_data['shipping_city'] ?? '';
$postcode = $_POST['postcode'] ?? $saved_data['shipping_postcode'] ?? '';
$State = $_POST['state'] ?? $saved_data['shipping_state'] ?? ''; 
$phone = $_POST['phone'] ?? $saved_data['shipping_phone'] ?? '';

$payment_method = $_POST['payment_method'] ?? '';
$card_number = $_POST['card_number'] ?? '';
$card_bank = $_POST['card_bank'] ?? '';
$card_expiry_month = $_POST['card_expiry_month'] ?? '';
$card_expiry_year = $_POST['card_expiry_year'] ?? '';
$card_cvv = $_POST['card_cvv'] ?? '';
$cardholder_name = $_POST['cardholder_name'] ?? '';
$fpx_bank_selection = $_POST['fpx_bank_selection'] ?? '';

if (!empty($postcode) && empty($error) && !empty($cart_items)) 
{
    $customer_postcode_int_display = (int)$postcode;
    foreach ($shipping_rules as $rule) 
    {
        if ($customer_postcode_int_display >= $rule['start'] && $customer_postcode_int_display <= $rule['end']) 
        {
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
    
    if (empty($error)) 
    {
        if (empty($name) || empty($address) || empty($city) || empty($postcode) || empty($State) || empty($phone) || empty($payment_method)) 
        {
            $error = "Please fill in all required shipping and payment information.";
        }
    }

    if (empty($error) && !preg_match('/^\d{5}$/', $postcode)) 
    {
        $error = "Invalid Postcode. Please enter 5 digits."; 
    }

    if (empty($error)) 
    {
        $selected_state = $_POST['state'];
        $selected_city = $_POST['city'];

        if (!array_key_exists($selected_state, $malaysian_locations) || !in_array($selected_city, $malaysian_locations[$selected_state])) 
        {
            $error = "Invalid state or city selected. Please refresh and try again.";
        }
    }

    $order_shipping_fee = 0; 
    if (!empty($postcode) && empty($error)) 
    { 
        $customer_postcode_int_order = (int)$postcode;
        foreach ($shipping_rules as $rule) 
        {
            if ($customer_postcode_int_order >= $rule['start'] && $customer_postcode_int_order <= $rule['end']) 
            {
                $order_shipping_fee = $rule['fee'];
                break;
            }
        }
    }
    $order_total_price = $subtotal + $order_shipping_fee;

    if (empty($error)) 
    {
        if ($payment_method === 'Visa') {
            $sql_correct_card = "SELECT * FROM `16_visa_card` LIMIT 1";
            $result_correct_card = $conn->query($sql_correct_card);
            
            if ($result_correct_card && $result_correct_card->num_rows > 0) 
            {
                $correct_card_details = $result_correct_card->fetch_assoc();

                if (trim($card_number) !== $correct_card_details['card_number']) { $error = "Incorrect card number. Please try again."; } 
                elseif (strcasecmp(trim($card_bank), $correct_card_details['bank_name']) !== 0) { $error = "Incorrect bank name. Please try again."; } 
                elseif (trim($card_expiry_month) !== $correct_card_details['expiry_month']) { $error = "Incorrect expiry month. Please try again."; } 
                elseif (trim($card_expiry_year) !== $correct_card_details['expiry_year']) { $error = "Incorrect expiry year. Please try again."; } 
                elseif (trim($card_cvv) !== $correct_card_details['cvv']) { $error = "Incorrect CVV. Please try again."; } 
                elseif (strcasecmp(trim($cardholder_name), $correct_card_details['cardholder_name']) !== 0) { $error = "Incorrect cardholder name. Please try again."; }
            } 
            else 
            {
                $error = "System error: Correct card details not found. Please contact administrator.";
            }
        } 
    }
    
    if (empty($error)) 
    {
        if ($payment_method === 'Bank Payment') 
        {
            $target_bank_redirect_file = ''; 
            if (!empty($fpx_bank_selection)) 
            {
                $target_bank_redirect_file = 'FPX_payment.php';
            }
            
            if (!empty($target_bank_redirect_file) && empty($error)) 
            { 
                $_SESSION['temp_checkout_data'] = [
                    'saved_for_customer_id' => $customerID,
                    'total_amount'          => $order_total_price,
                    'fpx_bank_selection'    => $fpx_bank_selection,
                    'customer_id'           => $customerID,
                    'shipping_name'         => $name,
                    'shipping_address'      => $address,
                    'shipping_city'         => $city,
                    'shipping_postcode'     => $postcode,
                    'shipping_state'        => $State,
                    'shipping_phone'        => $phone,
                    'shipping_fee'          => $order_shipping_fee,
                    'cart_items'            => $cart_items, 
                ];
                header("Location: Payment_Method_Bank/" . $target_bank_redirect_file);
                exit();
            }
        } 
        else 
        { 
            $conn->begin_transaction();
            try 
            {
                $tracking_number = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 11);
                $tracking_query = "INSERT INTO 07_tracking (Tracking_Number, Delivery_Status, Delivery_Address, Delivery_City, Delivery_Postcode, Delivery_State, Shipping_Fee) VALUES (?, 'pending', ?, ?, ?, ?, ?)";
                $stmt_tracking = $conn->prepare($tracking_query);
                $stmt_tracking->bind_param("sssssi", $tracking_number, $address, $city, $postcode, $State, $order_shipping_fee);
                $stmt_tracking->execute();
                $trackingID = $conn->insert_id;
                if (!$trackingID) throw new Exception("Failed to create tracking record.");
                $stmt_tracking->close();

                $order_status_for_db = 'Processing'; 
                $order_query = "INSERT INTO 08_order (CustomerID, TrackingID, OrderDate, OrderStatus, Shipping_Method, Shipping_Name, Shipping_Address, Shipping_City, Shipping_Postcode, Shipping_State, Shipping_Phone, Total_Price) VALUES (?, ?, NOW(), ?, 'Standard Delivery (Malaysia)', ?, ?, ?, ?, ?, ?, ?)";
                $stmt_order = $conn->prepare($order_query);
                $stmt_order->bind_param("iisssssssd", $customerID, $trackingID, $order_status_for_db, $name, $address, $city, $postcode, $State, $phone, $order_total_price);
                $stmt_order->execute();
                $orderID = $conn->insert_id;
                if (!$orderID) throw new Exception("Failed to create order record.");
                $stmt_order->close();

                foreach ($cart_items as $item) 
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
                    if ($stmt_decrease_stock->affected_rows === 0) throw new Exception("Stock update failed for ProductID: " . $item['ProductID']);
                    $stmt_decrease_stock->close();
                }

                $payment_status_for_db = 'Success'; 
                $payment_query = "INSERT INTO 10_payment (OrderID, Payment_Type, Amount, Payment_Date, Payment_Status) VALUES (?, ?, ?, NOW(), ?)";
                $stmt_payment = $conn->prepare($payment_query);
                $stmt_payment->bind_param("isds", $orderID, $payment_method, $order_total_price, $payment_status_for_db);
                $stmt_payment->execute();
                $paymentID = $stmt_payment->insert_id;
                if (!$paymentID) throw new Exception("Failed to create payment record.");
                $stmt_payment->close();
                
                if($cartID) 
                {
                    $clear_cart_query = "DELETE FROM 12_cart_item WHERE CartID = ?";
                    $stmt_clear_cart = $conn->prepare($clear_cart_query);
                    $stmt_clear_cart->bind_param("i", $cartID);
                    $stmt_clear_cart->execute();
                    $stmt_clear_cart->close();
                }

                $conn->commit(); 
                unset($_SESSION['temp_checkout_data']); 
                header("Location: order_confirmation.php?id=$orderID&success=1");
                exit();
            } 
            catch (Exception $e) 
            {
                $conn->rollback(); 
                $error = "Order processing failed. Please try again. Details: " . $e->getMessage();
            }
        }
    }
} 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
    <link rel="stylesheet" href="Checkout.css">
    <style>
        .error-message { 
            color: #ff5252; 
            font-size: 0.9em; 
            padding-left: 5px; 
            margin-top: -10px;
            margin-bottom: 10px;
            display: none; 
            min-height: 1em; 
        }
        .card-expiry-cvv { display: flex; gap: 15px; }
        .card-expiry-cvv > div { flex: 1; }
        .address-choice {
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            padding: 10px;
            background-color: #2a2a2a;
            border-radius: 8px;
        }
        .address-choice label {
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.3s ease;
            color: #ccc;
        }
        .address-choice input[type="radio"] { display: none; }
        .address-choice input[type="radio"]:checked + label {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <div class="checkout-left">
            <h1>Checkout</h1>

            <?php if (!empty($error)): ?>
                <p class="error-php" style="color: red; font-weight: bold; margin-bottom: 10px;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            
            <?php if (!empty($cart_items)): ?>
            <form method="post" class="checkout-form" onsubmit="return validateForm()">
                <h3>Shipping Information</h3>
                
                <div class="address-choice">
                    <input type="radio" id="new_address_radio" name="address_option" value="new" checked>
                    <label for="new_address_radio">Use New Address</label>
                    
                    <?php if ($registered_address): ?>
                    <input type="radio" id="registered_address_radio" name="address_option" value="registered">
                    <label for="registered_address_radio">Use My Registered Address</label>
                    <?php endif; ?>
                </div>
                
                <div id="shipping_fields_container">
                    <input type="text" id="shipping_name" name="name" placeholder="Full Name" value="<?= htmlspecialchars($name) ?>" required>
                    <div id="name_error" class="error-message"></div>

                    <input type="text" id="shipping_address" name="address" placeholder="Address" value="<?= htmlspecialchars($address) ?>" required>
                    
                    <select id="shipping_state" name="state" required>
                        <option value="">-- Select a State --</option>
                        <?php 
                            $selected_state = htmlspecialchars($State);
                            foreach (array_keys($malaysian_locations) as $state_option) 
                            {
                                $selected = ($state_option === $selected_state) ? 'selected' : '';
                                echo "<option value=\"$state_option\" $selected>$state_option</option>";
                            }
                        ?>
                    </select>
                    <div id="state_error" class="error-message"></div>

                    <select id="shipping_city" name="city" required>
                        <option value="">-- Select a City --</option>
                        <?php
                            if (!empty($selected_state) && isset($malaysian_locations[$selected_state])) 
                            {
                                $selected_city = htmlspecialchars($city);
                                foreach ($malaysian_locations[$selected_state] as $city_option) 
                                {
                                    $selected = ($city_option === $selected_city) ? 'selected' : '';
                                    echo "<option value=\"$city_option\" $selected>$city_option</option>";
                                }
                            }
                        ?>
                    </select>
                    <div id="city_error" class="error-message"></div>

                    <input type="text" id="postcode_input" name="postcode" placeholder="Postcode" value="<?= htmlspecialchars($postcode) ?>" required>
                    <div id="postcode_error" class="error-message"></div>

                    <input type="text" id="shipping_phone" name="phone" placeholder="Phone Number (10-12 digits)" value="<?= htmlspecialchars($phone) ?>" required>
                    <div id="phone_error" class="error-message"></div>
                </div>

                <h3>Payment Method</h3>
                <select name="payment_method" id="payment_method_select" required>
                    <option value="">Select a payment method</option>
                    <option value="Visa" <?= ($payment_method == 'Visa') ? 'selected' : '' ?>>Visa</option>
                    <option value="Bank Payment" <?= ($payment_method == 'Bank Payment') ? 'selected' : '' ?>>Bank Payment (FPX)</option>
                </select>

                <div id="credit_card_fields" style="display: none;">
                    <input type="text" name="card_number" id="card_number_input" placeholder="Card Number (16 digits)" value="<?= htmlspecialchars($card_number) ?>">
                    <div id="card_number_error" class="error-message"></div>
                    <div class="card-expiry-cvv">
                        <div>
                            <input type="text" name="card_expiry_month" id="card_expiry_month_input" placeholder="MM" maxlength="2" value="<?= htmlspecialchars($card_expiry_month) ?>">
                            <div id="card_month_error" class="error-message"></div>
                        </div>
                        <div>
                             <input type="text" name="card_expiry_year" id="card_expiry_year_input" placeholder="YY" maxlength="2" value="<?= htmlspecialchars($card_expiry_year) ?>">
                             <div id="card_year_error" class="error-message"></div>
                        </div>
                    </div>
                    <div id="card_date_error" class="error-message"></div>
                    <input type="text" name="card_cvv" id="card_cvv_input" placeholder="CVV (3-4 digits)" maxlength="4" value="<?= htmlspecialchars($card_cvv) ?>">
                    <div id="card_cvv_error" class="error-message"></div>
                    <input type="text" name="cardholder_name" id="cardholder_name_input" placeholder="Cardholder Name" value="<?= htmlspecialchars($cardholder_name) ?>">
                    <input type="text" name="card_bank" id="card_bank_input" placeholder="Bank Name" value="<?= htmlspecialchars($card_bank) ?>">
                </div>
                
                <div id="bank_payment_fields" style="display: none;">
                    <input type="hidden" name="fpx_bank_selection" value="FPX"> 
                    <p style="padding: 15px; margin-top:10px; background-color: #2a2a2a; border-radius: 8px; color: #ccc; text-align: center;">
                        You will be redirected to the secure FPX payment gateway to complete your purchase.
                    </p>
                </div>

                <button type="submit" name="place_order" class="place-order-button">Place Order</button>
            </form>

            <?php else: ?>
                <p>Your cart is empty. Please add items to your cart before checking out.</p>
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
        const malaysianLocations = <?php echo json_encode($malaysian_locations); ?>;
        const registeredAddress = <?php echo json_encode($registered_address); ?>;
        const shippingRules = <?php echo json_encode($shipping_rules); ?>;

        const newAddressRadio = document.getElementById('new_address_radio');
        const registeredAddressRadio = document.getElementById('registered_address_radio');
        
        const nameInput = document.getElementById('shipping_name');
        const addressInput = document.getElementById('shipping_address');
        const stateSelect = document.getElementById('shipping_state'); 
        const citySelect = document.getElementById('shipping_city');   
        const postcodeInput = document.getElementById('postcode_input');
        const phoneInput = document.getElementById('shipping_phone');

        const nameError = document.getElementById('name_error');
        const postcodeError = document.getElementById('postcode_error');
        const phoneError = document.getElementById('phone_error');
        
        const paymentMethodSelect = document.getElementById('payment_method_select');
        const creditCardFields = document.getElementById('credit_card_fields');
        const bankPaymentFields = document.getElementById('bank_payment_fields');

        function updateCityDropdown() {
            const selectedState = stateSelect.value;
            citySelect.innerHTML = '<option value="">-- Select a City --</option>'; 

            if (selectedState && malaysianLocations[selectedState]) 
            {
                citySelect.disabled = false;
                const cities = malaysianLocations[selectedState];

                cities.forEach(city => 
                {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    citySelect.appendChild(option);
                });
            } 
            else 
            {
                citySelect.disabled = true; 
            }
        }

        stateSelect.addEventListener('change', updateCityDropdown);

        function handleAddressChoice() 
        {
            const allAddressInputs = [nameInput, addressInput, citySelect, stateSelect, postcodeInput, phoneInput];
            if (registeredAddressRadio && registeredAddressRadio.checked) 
            {
                if (registeredAddress) 
                {
                    nameInput.value = (registeredAddress.Cust_First_Name + ' ' + registeredAddress.Cust_Last_Name).trim();
                    addressInput.value = registeredAddress.Cust_Address;
                    stateSelect.value = registeredAddress.Cust_State;

                    updateCityDropdown();
                    citySelect.value = registeredAddress.Cust_City;

                    postcodeInput.value = registeredAddress.Cust_Postcode;
                    phoneInput.value = registeredAddress.Cust_PhoneNumber;

                    allAddressInputs.forEach(input => input.readOnly = true);
                    stateSelect.disabled = true;
                    citySelect.disabled  = true;
                    updateShippingAndTotalDisplay(postcodeInput.value);
                }
            } 
            else 
            {
                allAddressInputs.forEach(input => 
                {
                    input.readOnly = false;
                    input.disabled = false; 
                });

                nameInput.value = "<?= htmlspecialchars($saved_data['shipping_name'] ?? '') ?>";
                addressInput.value = "<?= htmlspecialchars($saved_data['shipping_address'] ?? '') ?>";
                stateSelect.value = "<?= htmlspecialchars($saved_data['shipping_state'] ?? '') ?>"; 
                updateCityDropdown(); 
                citySelect.value = "<?= htmlspecialchars($saved_data['shipping_city'] ?? '') ?>";  
                postcodeInput.value = "<?= htmlspecialchars($saved_data['shipping_postcode'] ?? '') ?>";
                phoneInput.value = "<?= htmlspecialchars($saved_data['shipping_phone'] ?? '') ?>";
                updateShippingAndTotalDisplay(postcodeInput.value);
            }
        }
        
        function togglePaymentFields() 
        {
             const selectedMethod = paymentMethodSelect.value;
            creditCardFields.style.display = selectedMethod === 'Visa' ? 'block' : 'none';
            bankPaymentFields.style.display = selectedMethod === 'Bank Payment' ? 'block' : 'none';
        }

        function updateShippingAndTotalDisplay(postcodeValue) 
        {
            const shippingFeeDisplay = document.querySelector(".shipping-fee");
            const totalDisplay = document.querySelector(".total-price");
            const subtotalElement = document.querySelector(".subtotal");

            if (!subtotalElement) return;

            const subtotal = parseFloat(subtotalElement.textContent.replace(/,/g, ''));
            let calculatedShippingFee = 0;

            if (postcodeValue && postcodeValue.length >= 4) 
            {
                const customerPostcodeInt = parseInt(postcodeValue, 10);
                for (let i = 0; i < shippingRules.length; i++) 
                {
                    if (customerPostcodeInt >= shippingRules[i].start && customerPostcodeInt <= shippingRules[i].end) 
                    {
                        calculatedShippingFee = shippingRules[i].fee;
                        break;
                    }
                }
            }
            shippingFeeDisplay.textContent = calculatedShippingFee.toFixed(2);
            totalDisplay.textContent = "RM " + (subtotal + calculatedShippingFee).toFixed(2);
        }
        
        function forceLettersOnly(input, errorDiv) 
        {
            const originalValue = input.value;
            const cleanedValue = originalValue.replace(/[^a-zA-Z\s]/g, '');
            
            if (originalValue !== cleanedValue) 
            {
                input.value = cleanedValue;
                errorDiv.textContent = 'Only letters and spaces are allowed.';
                errorDiv.style.display = 'block';
            } 
            else 
            {
                errorDiv.style.display = 'none';
            }
        }

        function forceNumericOnly(input, errorDiv) 
        {
            const originalValue = input.value;
            const numericValue = originalValue.replace(/\D/g, '');

            if (originalValue !== numericValue) 
            {
                input.value = numericValue;
                errorDiv.textContent = 'Only numbers are allowed.';
                errorDiv.style.display = 'block';
            } 
            else 
            {
                errorDiv.style.display = 'none';
            }
            return numericValue;
        }

        function validatePhone() 
        {
            const value = forceNumericOnly(phoneInput, phoneError);

            if (value.length > 0 && (value.length < 10 || value.length > 12)) 
            {
                phoneError.textContent = 'Phone number must be 10-12 digits.';
                phoneError.style.display = 'block';
            } 
            else if (phoneError.textContent === 'Phone number must be 10-12 digits.') 
            {
                phoneError.style.display = 'none';
            }
        }

        function forceNumeric(inputElement) 
        {
            let value = inputElement.value;
            let numericValue = value.replace(/\D/g, ''); 

            if (value !== numericValue) { inputElement.value = numericValue; }
            return numericValue;
        }

        function validateCardNumber() 
        {
            const input = document.getElementById('card_number_input');
            const errorSpan = document.getElementById('card_number_error');
            const value = forceNumeric(input);

            if (value === '' || value.length === 16) 
            { 
                errorSpan.style.display = 'none'; 
            } 
            else 
            {
                errorSpan.textContent = 'Card number must be 16 digits.'; errorSpan.style.display = 'block';
            }
        }

        function validateExpiryMonth() 
        {
            const input = document.getElementById('card_expiry_month_input');
            const errorSpan = document.getElementById('card_month_error');
            const value = forceNumeric(input);

            if (value === '') 
            { 
                errorSpan.style.display = 'none'; 
            } 
            else if (parseInt(value) > 12 || parseInt(value) < 1 || value.length > 2 || (value.length === 2 && parseInt(value) === 0)) 
            {
                errorSpan.textContent = 'Invalid month (01-12).'; errorSpan.style.display = 'block';
            } 
            else 
            { 
                errorSpan.style.display = 'none'; 
            }
            validateFullExpiryDate();
        }

        function validateExpiryYear() 
        {
            const input = document.getElementById('card_expiry_year_input');
            const errorSpan = document.getElementById('card_year_error');
            const value = forceNumeric(input);

            if (value === '') 
            { 
                errorSpan.style.display = 'none'; 
            } 
            else if (value.length !== 2) 
            { 
                errorSpan.textContent = 'Year must be 2 digits (YY).'; errorSpan.style.display = 'block';
            } 
            else 
            { 
                errorSpan.style.display = 'none'; 
            }
            validateFullExpiryDate();
        }

        function validateCvv() 
        {
            const input = document.getElementById('card_cvv_input');
            const errorSpan = document.getElementById('card_cvv_error');
            const value = forceNumeric(input);

            if (value === '' || (value.length >= 3 && value.length <= 4)) 
            { 
                errorSpan.style.display = 'none';
            } 
            else 
            { 
                errorSpan.textContent = 'CVV must be 3 or 4 digits.'; errorSpan.style.display = 'block'; 
            }
        }

        function validateFullExpiryDate() 
        {
            const monthInput = document.getElementById('card_expiry_month_input');
            const yearInput = document.getElementById('card_expiry_year_input');
            const monthError = document.getElementById('card_month_error');
            const yearError = document.getElementById('card_year_error');
            const dateError = document.getElementById('card_date_error');

            if (monthError.style.display === 'none' && yearError.style.display === 'none' && monthInput.value.length === 2 && yearInput.value.length === 2) 
            {
                const currentYear = new Date().getFullYear() % 100;
                const currentMonth = new Date().getMonth() + 1;
                const inputYear = parseInt(yearInput.value, 10);
                const inputMonth = parseInt(monthInput.value, 10);

                if (inputYear < currentYear || (inputYear === currentYear && inputMonth < currentMonth)) 
                {
                    dateError.textContent = 'This card has expired.'; dateError.style.display = 'block';
                } 
                else 
                { 
                    dateError.style.display = 'none'; 
                }
            } 
            else 
            { 
                dateError.style.display = 'none'; 
            }
        }

        if (newAddressRadio) newAddressRadio.addEventListener('change', handleAddressChoice);
        if (registeredAddressRadio) registeredAddressRadio.addEventListener('change', handleAddressChoice);
        
        paymentMethodSelect.addEventListener('change', togglePaymentFields);

        nameInput.addEventListener('input', () => forceLettersOnly(nameInput, nameError));
        
        function validatePostcode() 
        {
            const value = forceNumericOnly(postcodeInput, postcodeError);
            updateShippingAndTotalDisplay(value);

            if (value.length > 0 && value.length !== 5) 
            {
                postcodeError.textContent = 'Postcode must be 5 digits.'; 
                postcodeError.style.display = 'block';
            } 
            else if (postcodeError.textContent === 'Postcode must be 5 digits.') 
            {
                postcodeError.style.display = 'none';
            }
        }

        postcodeInput.addEventListener('input', validatePostcode);
        
        phoneInput.addEventListener('input', validatePhone);
        
        document.getElementById('card_number_input').addEventListener('input', validateCardNumber);
        document.getElementById('card_expiry_month_input').addEventListener('input', validateExpiryMonth);
        document.getElementById('card_expiry_year_input').addEventListener('input', validateExpiryYear);
        document.getElementById('card_cvv_input').addEventListener('input', validateCvv);
        
        document.addEventListener('DOMContentLoaded', () => 
        {
            handleAddressChoice(); 
            togglePaymentFields();
        });
    </script>
</body>
</html>