<?php
session_start();
include 'db.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

$customerID = $_SESSION['customer_id'];
$payments = [];
$error = "";

// 获取用户的支付历史
$sql = "
    SELECT p.PaymentID, o.OrderID, o.OrderDate, o.OrderStatus, p.Payment_Card_Type, p.Payment_Card_Bank, p.Payment_Card_Number
    FROM 09_payment p
    JOIN 07_order o ON p.OrderID = o.OrderID
    WHERE o.CustomerID = ?
    ORDER BY o.OrderDate DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customerID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
} else {
    $error = "No payment history found.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment History</title>
    <link rel="stylesheet" href="payment_history.css"> 
</head>
<body>
    <div class="payment-history-container">
        <h1>Your Payment History</h1>

        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!empty($payments)): ?>
            <table class="payment-history-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Order Date</th>
                        <th>Status</th>
                        <th>Payment Method</th>
                        <th>Card Bank</th>
                        <th>Card Number</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= htmlspecialchars($payment['OrderID']) ?></td>
                            <td><?= htmlspecialchars($payment['OrderDate']) ?></td>
                            <td><?= htmlspecialchars($payment['OrderStatus']) ?></td>
                            <td><?= htmlspecialchars($payment['Payment_Card_Type']) ?></td>
                            <td><?= htmlspecialchars($payment['Payment_Card_Bank']) ?></td>
                            <td><?= htmlspecialchars(substr($payment['Payment_Card_Number'], -4)) ?>****</td> <!-- Show only last 4 digits -->
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No payments found in your history.</p>
        <?php endif; ?>
    </div>
</body>
</html>
