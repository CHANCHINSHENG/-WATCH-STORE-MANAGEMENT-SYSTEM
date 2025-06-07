<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT o.OrderID, o.OrderDate, o.OrderStatus, o.Total_Price,
                              o.Admin_Payment_Confirmation,
                              c.Cust_Username, t.Tracking_Number, t.Delivery_Status,
                              pm.Payment_Method_Type
                       FROM 07_order o
                       JOIN 02_customer c ON o.CustomerID = c.CustomerID
                       LEFT JOIN 06_tracking t ON o.TrackingID = t.TrackingID
                       LEFT JOIN 14_order_payment_method pm ON o.OrderID = pm.OrderID
                       ORDER BY o.OrderDate DESC");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="admin_view_allorder.css">

<div class="dashboard-container">
    <div class="header">
        <h2>Orders</h2>
    </div>
    <form class="filter-panel">
        <div class="row">
            <input type="text" placeholder="Search by Customer Name....." id="searchInput">
            <select id="statusFilter">
                <option value="">Order Status</option>
                <option value="Processing">Processing</option>
                <option value="Done Processing">Done Processing</option>
            </select>
            <select id="deliveryStatusFilter">
                <option value="">Delivery Status</option>
                <option value="Pending">Pending</option>
                <option value="Delivered">Delivered</option>
            </select>
            <select id="methodFilter">
                <option value="">Method</option>
                <option value="Visa">Visa</option>
                <option value="Bank">Bank</option>
                <option value="COD">COD</option>
            </select>
            <select id="orderlimits">
                <option value="">Order Limits</option>
                <option value="5days">Last 5 days</option>
                <option value="7days">Last 7 days</option>
                <option value="15days">Last 15 days</option>
                <option value="30days">Last 30 days</option>
            </select>
            <button type="button" class="btn green" id="downloadButton">Download All Orders</button>
        </div>

        <div class="row">
            <label>Start Date</label>
            <input type="date" id="startDate">
            <label>End Date</label>
            <input type="date" id="endDate">
            <button type="button" class="btn green" id="filterButton">Filter</button>
            <button type="reset" class="btn grey" id="resetButton">Reset</button>
        </div>
    </form>

    <?php if (empty($orders)): ?>
        <div class="order-container">
            <div class="icon">📦</div>
            <div class="message">No orders found.</div>
        </div>
    <?php else: ?>
        <table class="order-table" id="ordertable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Name</th>
                    <th>Order Status</th>
                    <th>Change Order</th>
                    <th>Tracking Number</th>
                    <th>Delivery Status</th>
                    <th>Change Delivery</th>
                    <th>Payment Method</th>
                    <th>Total</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
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
                        <td>
                            <?php if ($order['Payment_Method_Type'] === 'COD'): ?>
                                <span style="color:orange;">貨到付款</span>
                            <?php elseif ($order['Admin_Payment_Confirmation'] === 'Confirmed'): ?>
                                <span style="color:green;">✅ 已確認付款</span>
                            <?php else: ?>

                              <form method="GET" action="admin_verify_payment.php">
    <input type="hidden" name="order_id" value="<?= $order['OrderID'] ?>">
    <button type="submit" class="btn green">查看付款紀錄</button>
</form>

                            <?php endif; ?>
                        </td>
                        <td>$<?= number_format($order['Total_Price'], 2) ?></td>
                        <td>
    <div class="action-buttons">
        <a href="admin_layout.php?page=admin_view_receipt&order_id=<?= $order['OrderID'] ?>" class="btn edit-btn">View Receipt</a>
        <button class="btn delete-btn btn-delete"
    data-id="<?= $order['OrderID'] ?>"
    data-name="Order #<?= $order['OrderID'] ?>"
    data-type="orders">
    Delete
</button>
    </div>
</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
