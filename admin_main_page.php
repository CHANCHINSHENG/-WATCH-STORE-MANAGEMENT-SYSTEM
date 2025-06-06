    <?php
    require_once 'admin_login_include/config_session.php';
    require_once 'admin_login_include/db.php';

    if (!isset($_SESSION['admin_id'])) {
        header("Location: admin_login.php");
        exit();
    }

    // Total Sales (Exclude Cancelled)
    $stmt = $pdo->query("SELECT SUM(Total_Price) AS AllTimeSales FROM 07_order WHERE OrderStatus != 'Cancelled'");
    $allTimeSales = $stmt->fetch(PDO::FETCH_ASSOC)['AllTimeSales'] ?? 0;

    // Order Stats
    $totalOrders = $pdo->query("SELECT COUNT(*) FROM 07_order")->fetchColumn();
    $ordersProcessing = $pdo->query("SELECT COUNT(*) FROM 07_order WHERE OrderStatus = 'Processing'")->fetchColumn();
    $ordersDelivered = $pdo->query("SELECT COUNT(*) FROM 07_order o JOIN 06_tracking t ON o.TrackingID = t.TrackingID WHERE t.Delivery_Status = 'Delivered'")->fetchColumn();

    // Best Selling Products by Revenue (Top 5)
    $stmt = $pdo->query("
        SELECT p.ProductName, SUM(od.Order_Subtotal) AS TotalRevenue
        FROM 08_order_details od
        JOIN 05_product p ON od.ProductID = p.ProductID
        JOIN 07_order o ON o.OrderID = od.OrderID
        WHERE o.OrderStatus != 'Cancelled'
        GROUP BY p.ProductID
        ORDER BY TotalRevenue DESC
        LIMIT 5
    ");
    $productData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $productNames = array_column($productData, 'ProductName');
    $productRevenue = array_column($productData, 'TotalRevenue');

    // Weekly Sales & Orders
    $salesData = [];
    $orderData = [];
    $labels = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels[] = $date;

        $stmt = $pdo->prepare("SELECT SUM(Total_Price) FROM 07_order WHERE DATE(OrderDate) = ? AND OrderStatus != 'Cancelled'");
        $stmt->execute([$date]);
        $salesData[] = (float)($stmt->fetchColumn() ?? 0);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM 07_order WHERE DATE(OrderDate) = ? AND OrderStatus != 'Cancelled'");
        $stmt->execute([$date]);
        $orderData[] = (int)($stmt->fetchColumn() ?? 0);
    }
    ?>

 
  
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: #0f172a;
            color: white;
            font-family: Arial, sans-serif;
            min-height: 100vh;
            line-height: 1.6;
        }

        .page-wrapper {
            min-height: 100vh;
            padding: 2rem;
        }

        .box { background: #1e293b; padding: 20px; margin: 10px 0; border-radius: 8px; }
        .grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .card { flex: 1; min-width: 200px; text-align: center; }
        .tab-btns { display: flex; gap: 20px; margin-bottom: 10px; }
        .tab-btns span { cursor: pointer; padding-bottom: 5px; }
        .tab-btns span.active { border-bottom: 2px solid orange; color: orange; }
        canvas { background: #1e293b; border-radius: 8px; padding: 10px; max-height: 300px; }
    </style>
</head>
<body>

<div class="page-wrapper"> <!-- ✅ 這一層非常重要 -->

    <h2>Dashboard Overview</h2>

    <div class="grid">
        <div class="box card"><h3>All-Time Sales</h3><p>RM <?= number_format($allTimeSales, 2) ?></p></div>
        <div class="box card"><h3>Total Orders</h3><p><?= $totalOrders ?></p></div>
        <div class="box card"><h3>Orders Processing</h3><p><?= $ordersProcessing ?></p></div>
        <div class="box card"><h3>Orders Delivered</h3><p><?= $ordersDelivered ?></p></div>
    </div>

    <div class="grid">
        <div class="box" style="flex:1;">
            <h3>Weekly Sales</h3>
            <div class="tab-btns">
                <span id="salesTab" class="active">Sales</span>
                <span id="ordersTab">Orders</span>
            </div>
            <canvas id="weeklyChart"></canvas>
        </div>

        <div class="box" style="flex:1;">
            <h3>Top 5 Best Selling Products (by Revenue)</h3>
            <canvas id="productChart"></canvas>
        </div>
    </div>

    <div class="box" style="padding: 20px 30px;">
        <h3>Recent Orders</h3>
        <div id="recent-orders-container">Loading...</div>
    </div>

</div> <!-- ✅ End of .page-wrapper -->

<script>
function loadRecentOrders(page = 1) {
    fetch('load_recent_orders.php?page=' + page)
        .then(res => res.text())
        .then(html => {
            document.getElementById('recent-orders-container').innerHTML = html;
            document.querySelectorAll('.pagination a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const pageNum = this.getAttribute('data-page');
                    loadRecentOrders(pageNum);
                });
            });
        });
}

document.addEventListener('DOMContentLoaded', () => {
    loadRecentOrders();
});

const weeklyCtx = document.getElementById('weeklyChart');
const weeklyChart = new Chart(weeklyCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Sales',
            data: <?= json_encode($salesData) ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.2)',
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { labels: { color: 'white' } } },
        scales: { x: { ticks: { color: 'white' } }, y: { ticks: { color: 'white' }, beginAtZero: true } }
    }
});

document.getElementById('salesTab').onclick = () => {
    weeklyChart.data.datasets[0].label = 'Sales';
    weeklyChart.data.datasets[0].data = <?= json_encode($salesData) ?>;
    weeklyChart.update();
    document.getElementById('salesTab').classList.add('active');
    document.getElementById('ordersTab').classList.remove('active');
};

document.getElementById('ordersTab').onclick = () => {
    weeklyChart.data.datasets[0].label = 'Orders';
    weeklyChart.data.datasets[0].data = <?= json_encode($orderData) ?>;
    weeklyChart.update();
    document.getElementById('ordersTab').classList.add('active');
    document.getElementById('salesTab').classList.remove('active');
};

new Chart(document.getElementById('productChart'), {
    type: 'pie',
    data: {
        labels: <?= json_encode($productNames) ?>,
        datasets: [{
            data: <?= json_encode($productRevenue) ?>,
            backgroundColor: ['#22c55e','#2563eb','#f97316','#e11d48','#7c3aed'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { labels: { color: 'white' } } }
    }
});
</script>
</body>
</html>
