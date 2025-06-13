<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// 分頁參數
$limit = 10;
$page  = isset($_GET['pagenum']) && is_numeric($_GET['pagenum']) ? (int)$_GET['pagenum'] : 1;
$offset = ($page - 1) * $limit;

// 篩選參數
$search         = isset($_GET['search']) ? trim($_GET['search']) : '';
$status         = isset($_GET['status']) ? $_GET['status'] : '';
$delivery       = isset($_GET['delivery']) ? $_GET['delivery'] : '';
$method         = isset($_GET['method']) ? $_GET['method'] : '';
$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';
$start_date     = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date       = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// 構建 WHERE 條件與參數
$where  = [];
$params = [];

if ($search !== '') {
    $where[] = "c.Cust_Username LIKE ?";
    $params[] = "%$search%";
}
if ($status !== '') {
    $where[] = "o.OrderStatus = ?";
    $params[] = $status;
}
if ($delivery !== '') {
    $where[] = "t.Delivery_Status = ?";
    $params[] = $delivery;
}
if ($method !== '') {
    $where[] = "pm.Payment_Method_Type = ?";
    $params[] = $method;
}
if ($payment_status !== '') {
    $where[] = "o.Admin_Payment_Confirmation = ?";
    $params[] = $payment_status;
}
if ($start_date !== '') {
    $where[] = "DATE(o.OrderDate) >= ?";
    $params[] = $start_date;
}
if ($end_date !== '') {
    $where[] = "DATE(o.OrderDate) <= ?";
    $params[] = $end_date;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// 查詢總筆數
$count_stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM 07_order o
    JOIN 02_customer c ON o.CustomerID = c.CustomerID
    LEFT JOIN 06_tracking t ON o.TrackingID = t.TrackingID
    LEFT JOIN 14_order_payment_method pm ON o.OrderID = pm.OrderID
    $where_sql
");
$count_stmt->execute($params);
$total_orders = (int)$count_stmt->fetchColumn();
$total_pages  = ceil($total_orders / $limit);

// 查詢當前頁資料
$query = "
    SELECT o.OrderID, o.OrderDate, o.OrderStatus, o.Total_Price,
           o.Admin_Payment_Confirmation,
           c.Cust_Username, t.Tracking_Number, t.Delivery_Status,
           pm.Payment_Method_Type
    FROM 07_order o
    JOIN 02_customer c ON o.CustomerID = c.CustomerID
    LEFT JOIN 06_tracking t ON o.TrackingID = t.TrackingID
    LEFT JOIN 14_order_payment_method pm ON o.OrderID = pm.OrderID
    $where_sql
    ORDER BY o.OrderDate DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="admin_view_allorder.css">

<div class="dashboard-container">
    <div class="header"><h2>Orders</h2></div>

    <form class="filter-panel" method="GET" action="admin_layout.php">
        <input type="hidden" name="page" value="admin_view_allorder">
        <div class="row">
            <input type="text" name="search" placeholder="Search by Customer Name..." value="<?= htmlspecialchars($search) ?>">
            <select name="status">
                <option value="">Order Status</option>
                <option value="Processing"       <?= $status == 'Processing'       ? 'selected' : '' ?>>Processing</option>
                <option value="Done Processing" <?= $status == 'Done Processing'  ? 'selected' : '' ?>>Done Processing</option>
                <option value="Cancelled"       <?= $status == 'Cancelled'       ? 'selected' : '' ?>>Cancelled</option>
            </select>
            <select name="delivery">
                <option value="">Delivery Status</option>
                <option value="Pending"   <?= $delivery == 'Pending'   ? 'selected' : '' ?>>Pending</option>
                <option value="Delivered" <?= $delivery == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
            </select>
            <select name="method">
                <option value="">Payment Method</option>
                <option value="Visa" <?= $method == 'Visa' ? 'selected' : '' ?>>Visa</option>
                <option value="Bank" <?= $method == 'Bank' ? 'selected' : '' ?>>Bank</option>
            </select>
            <select name="payment_status">
                <option value="">Payment Status</option>
                <option value="Confirmed" <?= $payment_status == 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="Pending"   <?= $payment_status == 'Pending'   ? 'selected' : '' ?>>Pending</option>
            </select>
        </div>
        <div class="row">
            <label>Start Date</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
            <label>End Date</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
            <button type="submit" class="btn green">Filter</button>
            <a href="admin_layout.php?page=admin_view_allorder" class="btn grey">Reset</a>
        </div>
    </form>

    <?php if (empty($orders)): ?>
        <div class="order-container"><div class="icon">📦</div><div class="message">No orders found.</div></div>
    <?php else: ?>
        <table class="order-table">
            <thead>
                <tr>
                    <th>Date</th><th>Name</th><th>Order Status</th><th>Change Order</th>
                    <th>Tracking Number</th><th>Delivery Status</th><th>Change Delivery</th>
                    <th>Payment Method</th><th>Total</th><th>ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr id="order<?= $order['OrderID'] ?>">
                    <td><?= htmlspecialchars($order['OrderDate']) ?></td>
                    <td><?= htmlspecialchars($order['Cust_Username']) ?></td>
                    <td><span class="status-badge"><?= htmlspecialchars($order['OrderStatus']) ?></span></td>
                    <td>
                        <select class="status-dropdown order-dropdown" data-order-id="<?= $order['OrderID'] ?>">
                            <?php foreach (["Processing", "Done Processing", "Cancelled"] as $s): ?>
                            <option value="<?= $s ?>" <?= $order['OrderStatus'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><?= $order['Tracking_Number'] ?: '<span style="color:#888;">N/A</span>' ?></td>
                    <td><span class="status-badge"><?= htmlspecialchars($order['Delivery_Status']) ?></span></td>
                    <td>
                        <select class="status-dropdown delivery-dropdown" data-order-id="<?= $order['OrderID'] ?>">
                            <?php foreach (["Pending", "Delivered"] as $d): ?>
                            <option value="<?= $d ?>" <?= $order['Delivery_Status'] === $d ? 'selected' : '' ?>><?= $d ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <?php if ($order['Admin_Payment_Confirmation'] === 'Confirmed'): ?>
                        <span style="color:green;">✅ Confirmed</span>
                        <?php else: ?>
                        <form method="GET" action="admin_verify_payment.php">
                            <input type="hidden" name="order_id" value="<?= $order['OrderID'] ?>">
                            <button type="submit" class="btn">View Payment Details</button>
                        </form>
                        <?php endif; ?>
                    </td>
                    <td>RM<?= number_format($order['Total_Price'], 2) ?></td>
                    <td>
                        <div class="action-buttons">
                            <a href="admin_layout.php?page=admin_view_receipt&order_id=<?= $order['OrderID'] ?>" class="btn edit-btn">View Receipt</a>
                            <button class="btn delete-btn btn-delete" data-id="<?= $order['OrderID'] ?>" data-name="Order #<?= $order['OrderID'] ?>" data-type="orders">Delete</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        $base = "admin_layout.php?page=admin_view_allorder"
              . "&search=" . urlencode($search)
              . "&status=" . urlencode($status)
              . "&delivery=" . urlencode($delivery)
              . "&method=" . urlencode($method)
              . "&payment_status=" . urlencode($payment_status)
              . "&start_date=" . urlencode($start_date)
              . "&end_date=" . urlencode($end_date);
        echo '<div class="pagination">';
        if ($page > 1) echo '<a class="page-btn" href="'. $base .'&pagenum='.($page-1).'">« Prev</a>';
        for ($i =1; $i <= $total_pages; $i++) {
            echo '<a class="page-btn'.($page==$i?' active':'').'" href="'. $base .'&pagenum='.$i.'">'.$i.'</a>';
        }
        if ($page < $total_pages) echo '<a class="page-btn" href="'. $base .'&pagenum='.($page+1).'">Next »</a>';
        echo '</div>';
        ?>
    <?php endif; ?>
</div>
