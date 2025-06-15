<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("SELECT o.OrderID, o.OrderDate, c.Cust_Username, o.Total_Price, o.OrderStatus FROM 08_order o JOIN 02_customer c ON o.CustomerID = c.CustomerID ORDER BY o.OrderDate DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalRecent = $pdo->query("SELECT COUNT(*) FROM 08_order")->fetchColumn();
$totalPages = ceil($totalRecent / $limit);
?>

<style>
    table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed; 
        margin-top: 10px;
    }

    thead {
        background-color: #1e293b;
    }

    th, td {
        padding: 12px 18px;
        border-bottom: 1px solid #334155;
        text-align: left;
        white-space: nowrap;
    }

    th:nth-child(1), td:nth-child(1) { width: 10%; }
    th:nth-child(2), td:nth-child(2) { width: 22%; }
    th:nth-child(3), td:nth-child(3) { width: 20%; }
    th:nth-child(4), td:nth-child(4) { width: 15%; }
    th:nth-child(5), td:nth-child(5) { width: 15%; }
    th:nth-child(6), td:nth-child(6) { width: 18%; }

    .pagination {
        margin-top: 15px;
        text-align: right;
    }

    .pagination a {
        color: white;
        margin: 0 3px;
        padding: 6px 12px;
        text-decoration: none;
        border-radius: 5px;
        background: #334155;
        transition: background 0.3s, color 0.3s;
    }

    .pagination a.active {
        background: #10b981;
        color: white;
        font-weight: bold;
    }

    .pagination a:hover:not(.active) {
        background: #475569;
    }
    .status-dropdown {
  padding: 0.5rem 1rem;
  font-size: 0.95rem;
  font-weight: 500;
  border-radius: 10px;
  border: 2px solid #e2e8f0;
  background-color: #fff;
  color: #4a0072;
  transition: all 0.3s ease;
  cursor: pointer;
  appearance: none;
}

.status-dropdown:hover {
  border-color: #4a0072;
  background-color: rgba(74, 0, 114, 0.05);
}

.status-dropdown:focus {
  outline: none;
  border-color: #4a0072;
  box-shadow: 0 0 0 3px rgba(74, 0, 114, 0.2);
}
/* Badge Styles */
.status-badge {
  padding: 0.45rem 1rem;
  border-radius: 999px;
  font-weight: 600;
  font-size: 0.85rem;
  display: inline-block;
  white-space: nowrap;
}

.status-badge.processing {
    background: rgba(251, 191, 36, 0.15);
    color: #f59e0b;
}

.status-badge.done-processing {
    background: rgba(74, 20, 140, 0.1);
    color: #22c55e;
}
</style>

<table>
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Amount (RM)</th>
            <th>Status</th>
            <th>Change Status</th>
        </tr>
    </thead>
    <tbody>
   <?php foreach ($recentOrders as $order): ?>
    <?php $statusClass = strtolower(str_replace(' ', '-', $order['OrderStatus'])); ?>
    <tr>
        <td><?= $order['OrderID'] ?></td>
        <td><?= date("d M Y, h:i A", strtotime($order['OrderDate'])) ?></td>
        <td><?= htmlspecialchars($order['Cust_Username']) ?></td>
        <td><?= number_format($order['Total_Price'], 2) ?></td>
        <td>
            <span class="status-badge <?= $statusClass ?>">
                <?= htmlspecialchars($order['OrderStatus']) ?>
            </span>
        </td>
        <td>
            <select class="status-dropdown order-dropdown" data-order-id="<?= $order['OrderID'] ?>">
                <?php foreach (["Processing", "Done Processing","Cancelled"] as $status): ?>
                    <option value="<?= $status ?>" <?= $order['OrderStatus'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
<?php endforeach; ?>
    </tbody>
</table>

<div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="javascript:void(0);" class="page-link <?= $i == $page ? 'active' : '' ?>" data-page="<?= $i ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>


