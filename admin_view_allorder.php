<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$limit = 10;
$page  = isset($_GET['pagenum']) && is_numeric($_GET['pagenum']) ? (int)$_GET['pagenum'] : 1;
$offset = ($page - 1) * $limit;

$search         = $_GET['search'] ?? '';
$status         = $_GET['status'] ?? '';
$delivery       = $_GET['delivery'] ?? '';
$method         = $_GET['method'] ?? '';
$payment_status = $_GET['payment_status'] ?? '';
$start_date     = $_GET['start_date'] ?? '';
$end_date       = $_GET['end_date'] ?? '';

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
    $where[] = "pm.Payment_Type = ?";
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

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM 08_order o
    JOIN 02_customer c ON o.CustomerID = c.CustomerID
    LEFT JOIN 07_tracking t ON o.TrackingID = t.TrackingID
    LEFT JOIN 10_payment pm ON o.OrderID = pm.OrderID
    $where_sql");
$count_stmt->execute($params);
$total_orders = (int)$count_stmt->fetchColumn();
$total_pages  = ceil($total_orders / $limit);

$query = "
    SELECT o.OrderID, o.OrderDate, o.OrderStatus, o.Total_Price,
           o.Admin_Payment_Confirmation,
           c.Cust_Username, t.Tracking_Number, t.Delivery_Status,
           pm.Payment_Type
    FROM 08_order o
    JOIN 02_customer c ON o.CustomerID = c.CustomerID
    LEFT JOIN 07_tracking t ON o.TrackingID = t.TrackingID
    LEFT JOIN 10_payment pm ON o.OrderID = pm.OrderID
    $where_sql
    ORDER BY o.OrderDate DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$queryParams = http_build_query([
    'page' => 'admin_view_allorder',
    'search' => $search,
    'status' => $status,
    'delivery' => $delivery,
    'method' => $method,
    'payment_status' => $payment_status,
    'start_date' => $start_date,
    'end_date' => $end_date
]);
?>

<link rel="stylesheet" href="admin_view_allorder.css">

<div class="dashboard-container">
    <div class="header"><h2>Orders</h2></div>

    <form class="filter-panel" method="GET" action="admin_layout.php">
        <input type="hidden" name="page" value="admin_view_allorder">
          <input type="hidden" name="pagenum" value="1">
        <div class="row">
            <input type="text" name="search" placeholder="Search by Customer Name..." value="<?= htmlspecialchars($search) ?>">
       <select name="status">
    <option value="">All Status</option>
    <option value="Processing" <?= $status === 'Processing' ? 'selected' : '' ?>>Processing</option>
    <option value="Done Processing" <?= $status === 'Done Processing' ? 'selected' : '' ?>>Done Processing</option>
    <option value="Cancelled" <?= $status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
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
                    <th>Date</th><th>Name</th>
                    <th>Order Status</th>
                    <th>Tracking Number</th><th>Delivery Status</th><th>Change Delivery</th>
                    <th>Payment Method</th><th>Total</th><th>ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr id="order<?= $order['OrderID'] ?>">
                    <td><?= htmlspecialchars($order['OrderDate']) ?></td>
                    <td><?= htmlspecialchars($order['Cust_Username']) ?></td>
                    <?php
                    $orderStatus = $order['OrderStatus'] ?? 'Unknown';
$status_class = 'status-badge';

switch (strtolower(trim($orderStatus))) {  
    case 'done processing':
        $status_class .= ' done-processing';
        break;
    case 'processing':
        $status_class .= ' processing';
        break;
    case 'cancelled':
        $status_class .= ' cancelled';
        break;
    default:
        $status_class .= ' unknown';
}

                        ?>
<td><span class="<?= $status_class ?>"><?= htmlspecialchars($orderStatus) ?></span></td>
                 
                    <td><?= $order['Tracking_Number'] ?: '<span style="color:#888;">N/A</span>' ?></td>
                    <?php
                    $delivery = $order['Delivery_Status'] ?? 'Unknown';
                    $delivery_class = 'status-badge';

                    switch (strtolower($delivery)) {
                        case 'pending':
                            $delivery_class .= ' pending';
                            break;
                        case 'delivered':
                            $delivery_class .= ' delivered';
                            break;
                        default:
                            $delivery_class .= ' unknown';
                    }
?>
<td><span class="<?= $delivery_class ?>"><?= htmlspecialchars($delivery) ?></span></td>

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
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

       <!-- Pagination Display (below the table) -->
<?php if ($total_pages > 1): ?>
  <div class="pagination">
    <?php if ($page > 1): ?>
      <a href="admin_layout.php?<?= $queryParams ?>&pagenum=<?= $page - 1 ?>">&laquo; Prev</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <a href="admin_layout.php?<?= $queryParams ?>&pagenum=<?= $i ?>" <?= $i === $page ? 'class="active"' : '' ?>><?= $i ?></a>
    <?php endfor; ?>

    <?php if ($page < $total_pages): ?>
      <a href="admin_layout.php?<?= $queryParams ?>&pagenum=<?= $page + 1 ?>">Next &raquo;</a>
    <?php endif; ?>
  </div>
<?php endif; ?>

    <?php endif; ?>
</div>
