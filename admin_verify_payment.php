<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (!isset($_GET['order_id'])) {
    echo "Missing order ID.";
    exit();
}

$orderID = $_GET['order_id'];

// Fetch basic order details
$orderStmt = $pdo->prepare("SELECT o.OrderID, o.OrderDate, o.Total_Price, o.Shipping_State,
                            c.Cust_Username, c.Cust_First_Name, c.Cust_Last_Name,
                            pm.Payment_Method_Type
                            FROM 07_order o
                            JOIN 02_customer c ON o.CustomerID = c.CustomerID
                            LEFT JOIN 14_order_payment_method pm ON o.OrderID = pm.OrderID
                            WHERE o.OrderID = ?");
$orderStmt->execute([$orderID]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "Order not found.";
    exit();
}

$paymentData = [];
$method = $order['Payment_Method_Type'];
$foundMatch = false;
$customerFullName = strtolower(trim($order['Cust_First_Name'] . ' ' . $order['Cust_Last_Name']));

if ($method === 'Visa') {
    $stmt = $pdo->prepare("SELECT * FROM 09_payment WHERE OrderID = ?");
    $stmt->execute([$orderID]);
    $paymentData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($paymentData) {
        $paymentName = strtolower(trim($paymentData['Card_Holder_Name']));
        if (
            $paymentName === $customerFullName &&
            abs($paymentData['Amount'] - $order['Total_Price']) < 0.01 &&
            strtotime($paymentData['Payment_Date']) >= strtotime($order['OrderDate'])
        ) {
            $foundMatch = true;
        }
    }

} elseif ($method === 'Bank') {
    $stmt = $pdo->prepare("SELECT * FROM 13_bank_payment WHERE order_id = ?");
    $stmt->execute([$orderID]);
    $paymentData = $stmt->fetch(PDO::FETCH_ASSOC);


   $stmt = $pdo->prepare("SELECT * FROM 13_bank_payment WHERE order_id = ?");
    $stmt->execute([$orderID]);
    $paymentData = $stmt->fetch(PDO::FETCH_ASSOC);

    // 只有 Bank 需要驗證 payment_status
    if ($paymentData && strtolower($paymentData['payment_status']) === 'success') {
        $foundMatch = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Payment</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; padding: 30px; }
        .container { background: #fff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 700px; margin: auto; }
        h2 { color: #333; margin-bottom: 10px; }
        p { margin: 8px 0; font-size: 16px; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn.green { background-color: #4CAF50; color: white; }
        .btn.grey { background-color: #ccc; color: black; text-decoration: none; display: inline-block; }
        .status-success { color: green; font-weight: bold; }
        .status-warning { color: orange; font-weight: bold; }
        .status-error { color: red; font-weight: bold; }
        form { margin-top: 15px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Verify Payment Information</h2>
    <p><strong>Order ID:</strong> <?= htmlspecialchars($order['OrderID']) ?></p>
    <p><strong>Username:</strong> <?= htmlspecialchars($order['Cust_Username']) ?></p>
    <p><strong>Payment Method:</strong> <?= htmlspecialchars($method) ?></p>
    <p><strong>Order Amount:</strong> RM<?= number_format($order['Total_Price'], 2) ?></p>
    <p><strong>Order Date:</strong> <?= htmlspecialchars($order['OrderDate']) ?></p>
    <hr>
    <h3>Match Result:</h3>

    <?php if ($paymentData): ?>
        <?php if ($method === 'Visa'): ?>
            <p><strong>Card Holder Name:</strong> <?= htmlspecialchars($paymentData['Card_Holder_Name']) ?></p>
            <p><strong>Payment Amount:</strong> RM<?= number_format($paymentData['Amount'], 2) ?></p>
            <p><strong>Payment Time:</strong> <?= htmlspecialchars($paymentData['Payment_Date']) ?></p>
            <p><strong>Payment Status:</strong> <?= htmlspecialchars($paymentData['Payment_Status']) ?></p>
        <?php elseif ($method === 'Bank'): ?>
            <p><strong>Bank Name:</strong> <?= htmlspecialchars($paymentData['bank_name']) ?></p>
            <p><strong>Payment Amount:</strong> RM<?= number_format($order['Total_Price'], 2) ?> (from order)</p>
            <p><strong>Payment Time:</strong> <?= htmlspecialchars($paymentData['payment_time']) ?></p>
            <p><strong>Payment Status:</strong> <?= htmlspecialchars($paymentData['payment_status']) ?></p>
        <?php endif; ?>

        <?php if ($foundMatch): ?>
            <p class="status-success">✅ Payment information matches. You may confirm the payment.</p>
            <form method="POST" action="admin_confirm_payment.php" onsubmit="return confirm('Confirm this payment?');">
                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['OrderID']) ?>">
                <button type="submit" class="btn green">Confirm Payment</button>
            </form>
        <?php else: ?>
            <p class="status-error">⚠️ Data mismatch. Please re-check or contact the customer.</p>
        <?php endif; ?>

    <?php else: ?>
        <p class="status-warning">⚠️ No payment record found.</p>
    <?php endif; ?>

    <br>
    <a href="admin_layout.php?page=admin_view_allorder" class="btn grey">Back to Orders</a>
</div>
</body>
</html>
