<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';

// Check login
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Check for customer ID
if (!isset($_GET['id'])) {
    echo "<div class='message error'>No customer selected.</div>";
    exit();
}

$customer_id = $_GET['id'];

// Fetch customer name
$stmt_customer = $pdo->prepare("SELECT Cust_Username FROM 02_customer WHERE CustomerID = ?");
$stmt_customer->execute([$customer_id]);
$customer = $stmt_customer->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    echo "<div class='message error'>Customer not found.</div>";
    exit();
}

// Fetch orders + tracking data
$stmt_orders = $pdo->prepare("SELECT o.*, t.Tracking_Number, t.Delivery_Status FROM 08_order o LEFT JOIN 07_tracking t ON o.TrackingID = t.TrackingID WHERE o.CustomerID = ? ORDER BY o.OrderDate DESC");
$stmt_orders->execute([$customer_id]);
$orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="admin_view_cusorder.css">

<div class="dashboard-container">
    <h2>Orders for <?= htmlspecialchars($customer['Cust_Username']) ?></h2>

    <?php if (empty($orders)): ?>
        <div class="order-container">
            <div class="icon">🛍️</div>
            <div class="message">This Customer has no order Yet!</div>
        </div>
    <?php else: ?>
        <table class="order-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Order Status</th>
                    <th>Tracking Number</th>
                    <th>Delivery Status</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['OrderDate']) ?></td>
                        <td>
    <span class="status-badge <?= strtolower(str_replace(' ', '-', $order['OrderStatus'])) ?>">
        <?= htmlspecialchars($order['OrderStatus']) ?>
    </span>
</td>
                        
                        <td><?= $order['Tracking_Number'] ? htmlspecialchars($order['Tracking_Number']) : '<span style="color: #aaa;">Not Assigned</span>' ?></td>
                        <td>
    <span class="status-badge <?= strtolower(str_replace(' ', '-', $order['Delivery_Status'])) ?>">
        <?= htmlspecialchars($order['Delivery_Status']) ?>
    </span>
</td>
                       
                        <td>$<?= number_format($order['Total_Price'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

  <div class="button-group">
        <a href="admin_layout.php?page=admin_view_customer" class="btn secondary-btn">Back to Customer List</a>
    </div>
</div>