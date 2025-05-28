<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

// Only allow access if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch all orders and their customer username + tracking
$stmt = $pdo->prepare("
    SELECT o.OrderID, o.OrderDate, o.OrderStatus, o.Total_Price,
           c.Cust_Username, t.Tracking_Number, t.Delivery_Status
    FROM 07_order o
    JOIN 02_customer c ON o.CustomerID = c.CustomerID
    LEFT JOIN 06_tracking t ON o.TrackingID = t.TrackingID
    ORDER BY o.OrderDate DESC
");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="admin_view_allorder.css">

<div class="dashboard-container">
    <div class="header">
            <h2>Order</h2>
        </div>   
       <div class="filter-row">
            <input type="text" id="searchInput" placeholder="Search OrderID, OrderStatus, TrackingNumber, DeliveryStatus...">
            <button id="filterButton" class="btn filter-btn">Filter</button>
            <button id="resetButton" class="btn reset-btn">Reset</button>
        </div>
    <?php if (empty($orders)): ?>
        <div class="order-container">
            <div class="icon">📦</div>
            <div class="message">No orders found.</div>
        </div>
    <?php else: ?>
        <table class="order-table" id="ordertable">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Name</th>
                    <th>Order Status</th>
                    <th>Change Order</th>
                    <th>Tracking Number</th>
                    <th>Delivery Status</th>
                    <th>Change Delivery</th>
                    <th>Total</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
<tr>
    <td><?= htmlspecialchars($order['OrderID']) ?></td>
    <td><?= htmlspecialchars($order['OrderDate']) ?></td>
    <td><?= htmlspecialchars($order['Cust_Username']) ?></td>
    <td>
        <span class="status-badge <?= strtolower(str_replace(' ', '-', $order['OrderStatus'])) ?>">
            <?= htmlspecialchars($order['OrderStatus']) ?>
        </span>
    </td>
    <td>
        <select class="status-dropdown order-dropdown" data-order-id="<?= $order['OrderID'] ?>">
            <?php foreach (["Processing", "Done Processing"] as $status): ?>
                <option value="<?= $status ?>" <?= $order['OrderStatus'] === $status ? 'selected' : '' ?>><?= $status ?></option>
            <?php endforeach; ?>
        </select>
    </td>
    <td><?= $order['Tracking_Number'] ? htmlspecialchars($order['Tracking_Number']) : '<span style="color:#888;">N/A</span>' ?></td>
    <td>
        <span class="status-badge <?= strtolower(str_replace(' ', '-', $order['Delivery_Status'])) ?>">
            <?= htmlspecialchars($order['Delivery_Status']) ?>
        </span>
    </td>
    <td>
        <select class="status-dropdown delivery-dropdown" data-order-id="<?= $order['OrderID'] ?>">
            <?php foreach (["Pending", "Delivered"] as $status): ?>
                <option value="<?= $status ?>" <?= $order['Delivery_Status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>$<?= number_format($order['Total_Price'], 2) ?></td>
    <td>
        <div class="action-buttons">
            <a href="admin_layout.php?page=admin_view_receipt&order_id=<?= $order['OrderID'] ?>" class="btn edit-btn">View Receipt</a>
        </div>
    </td>
            

</tr>
                <?php endforeach; ?>
                
            </tbody>

        </table>
    <?php endif; ?>
</div>
