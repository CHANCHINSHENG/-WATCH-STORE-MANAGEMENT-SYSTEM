<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// All-time Sales
$stmt = $pdo->query("SELECT SUM(Total_Price) AS AllTimeSales FROM 08_order WHERE OrderStatus != 'Cancelled'");
$allTimeSales = $stmt->fetch(PDO::FETCH_ASSOC)['AllTimeSales'] ?? 0;

// Order Stats
$totalOrders = $pdo->query("
    SELECT COUNT(*) FROM 08_order 
    WHERE LOWER(TRIM(OrderStatus)) != 'cancelled'
")->fetchColumn();

$ordersProcessing = $pdo->query("SELECT COUNT(*) FROM 08_order WHERE OrderStatus = 'Processing'")->fetchColumn();
$ordersDelivered = $pdo->query("SELECT COUNT(*) FROM 08_order o JOIN 07_tracking t ON o.TrackingID = t.TrackingID WHERE t.Delivery_Status = 'Delivered'")->fetchColumn();

// Top 5 Best-Selling Products
$stmt = $pdo->query("
    SELECT p.ProductName, SUM(od.Order_Quantity) AS TotalSold
    FROM 09_order_details od
    JOIN 05_product p ON od.ProductID = p.ProductID
    JOIN 08_order o ON o.OrderID = od.OrderID
    WHERE o.OrderStatus != 'Cancelled'
    GROUP BY p.ProductID
    ORDER BY TotalSold DESC
    LIMIT 5
");
$productData = $stmt->fetchAll(PDO::FETCH_ASSOC);
$productNames = array_column($productData, 'ProductName');
$productQuantity = array_column($productData, 'TotalSold');

// Weekly sales/order data
$salesData = [];
$orderData = [];
$labels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = $date;

    $stmt = $pdo->prepare("SELECT SUM(Total_Price) FROM 08_order WHERE DATE(OrderDate) = ? AND OrderStatus != 'Cancelled'");
    $stmt->execute([$date]);
    $salesData[] = (float)($stmt->fetchColumn() ?? 0);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM 08_order WHERE DATE(OrderDate) = ? AND OrderStatus != 'Cancelled'");
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

        .page-wrapper { min-height: 100vh; padding: 2rem; }
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

<div class="page-wrapper"> 

    <h2>Dashboard Overview</h2>

    <div class="grid">
        <div class="box card"><h3>All-Time Sales</h3><p>RM <?= number_format($allTimeSales, 2) ?></p></div>
        <div class="box card"><h3>Total Orders</h3><p><?= $totalOrders ?></p></div>
        <div class="box card"><h3>Orders Processing</h3><p><?= $ordersProcessing ?></p></div>
        <div class="box card"><h3>Orders Delivered</h3><p><?= $ordersDelivered ?></p></div>
    </div>

    <div class="grid">
        <div class="box" style="flex:1;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3>Weekly </h3>
                <button onclick="printWeeklyReport()" style="background: orange; color: white; border: none; padding: 5px 10px; border-radius: 4px;">Print</button>
            </div>
            <div class="tab-btns">
                <span id="salesTab" class="active">Sales</span>
                <span id="ordersTab">Orders</span>
            </div>
            <canvas id="weeklyChart"></canvas>
        </div>

        <div class="box" style="flex:1;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3>Top 5 Best Selling Products (by Quantity)</h3>
                <button onclick="printTop5Report()" style="background: orange; color: white; border: none; padding: 5px 10px; border-radius: 4px;">Print</button>
            </div>
            <canvas id="productChart"></canvas>
        </div>
    </div>

    <div class="box" style="padding: 20px 30px;">
        <h3>Recent Orders</h3>
        <div id="recent-orders-container">Loading...</div>
    </div>

   <!-- HIDDEN PRINTABLE BLOCKS -->
<div style="display: none">
    <pre id="printable-weekly">
<?php
    echo "Weekly Sales Summary\n\n";
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $stmt = $pdo->prepare("SELECT SUM(Total_Price) FROM 08_order WHERE DATE(OrderDate) = ? AND OrderStatus != 'Cancelled'");
        $stmt->execute([$date]);
        $sales = $stmt->fetchColumn() ?? 0;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM 08_order WHERE DATE(OrderDate) = ? AND OrderStatus != 'Cancelled'");
        $stmt->execute([$date]);
        $orders = $stmt->fetchColumn() ?? 0;
        echo "$date | Sales: RM " . number_format($sales, 2) . " | Orders: $orders\n";
    }
?>
    </pre>
<pre id="printable-top5">
<?php
    $stmt = $pdo->query("
        SELECT p.ProductName, SUM(od.Order_Quantity) AS TotalSold
        FROM 09_order_details od
        JOIN 05_product p ON od.ProductID = p.ProductID
        JOIN 08_order o ON o.OrderID = od.OrderID
        WHERE o.OrderStatus != 'Cancelled'
        GROUP BY p.ProductID
        ORDER BY TotalSold DESC
        LIMIT 5
    ");

    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($topProducts)) {
        echo "No data available.\n";
    } else {
        foreach ($topProducts as $index => $row) {
            echo ($index + 1) . ". " . $row['ProductName'] . " — " . $row['TotalSold'] . " units\n";
        }
    }
?>
</pre>
</div>

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
            data: <?= json_encode($productQuantity) ?>,
            backgroundColor: ['#22c55e','#2563eb','#f97316','#e11d48','#7c3aed'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { labels: { color: 'white' } } }
    }
});

function openFormattedReport(title, content) {
    const logoURL = 'img/tigo.png'; 
    const win = window.open('', '_blank');
    win.document.write(`
        <html>
        <head>
            <title>${title}</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 40px; line-height: 1.6; }
                h1 { text-align: center; }
                .logo { width: 120px; display: block; margin: 0 auto 20px; }
                .footer { text-align: center; margin-top: 40px; font-size: 0.9em; color: gray; }
                pre { background: #f3f3f3; padding: 20px; border-radius: 6px; white-space: pre-wrap; }
            </style>
        </head>
        <body>
            <img src="${logoURL}" class="logo" alt="Company Logo" />
            <h1>${title}</h1>
            <pre>${content}</pre>
            <div class="footer">Generated by Admin Dashboard | Printed on ${new Date().toLocaleDateString()}</div>
            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(() => window.close(), 100);
                };
            <\/script>
        </body>
        </html>
    `);
    win.document.close();
}

function printWeeklyReport() {
    const content = document.getElementById('printable-weekly').innerText;
    openFormattedReport('Weekly Sales Report', content);
}

function printTop5Report() {
    const content = document.getElementById('printable-top5').innerText;
    openFormattedReport('Top 5 Best-Selling Products', content);
}

</script>
</body>
</html>
