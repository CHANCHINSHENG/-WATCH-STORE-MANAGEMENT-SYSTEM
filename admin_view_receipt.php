<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

if (!isset($_GET['order_id'])) {
    echo "<p style='color:red;'>Order ID is required.</p>";
    exit();
}

$orderId = $_GET['order_id'];

$stmt = $pdo->prepare("
    SELECT o.*, c.Cust_Username, c.Cust_Email, c.Cust_PhoneNumber,
           t.Tracking_Number, t.Delivery_Status, t.Delivery_Address, t.Delivery_City, t.Delivery_Postcode, t.Delivery_State,t.Shipping_Fee
    FROM 07_order o
    JOIN 02_customer c ON o.CustomerID = c.CustomerID
    JOIN 06_tracking t ON o.TrackingID = t.TrackingID
    WHERE o.OrderID = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<p style='color:red;'>Order not found.</p>";
    exit();
}

$itemStmt = $pdo->prepare("
    SELECT od.*, p.ProductName
    FROM 08_order_details od
    JOIN 05_product p ON od.ProductID = p.ProductID
    WHERE od.OrderID = ?
");
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Invoice #<?= $order['OrderID'] ?></title>
    <link rel="stylesheet" href="admin_view_receipt.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <h2>INVOICE</h2>
            <span class="badge <?= strtolower($order['OrderStatus']) ?>"><?= $order['OrderStatus'] ?></span>
        </div>
        <div class="details-row">
            <div>
                <strong>Date:</strong> <?= date('d M, Y', strtotime($order['OrderDate'])) ?><br>
                <strong>Invoice No:</strong> #<?= $order['OrderID'] ?>
            </div>
            <div>
                <strong>Invoice To:</strong><br>
                <?= htmlspecialchars($order['Cust_Username']) ?><br>
                <?= htmlspecialchars($order['Cust_Email']) ?><br>
                <?= htmlspecialchars($order['Cust_PhoneNumber']) ?><br>
                <?= $order['Delivery_Address'] ?>, <?= $order['Delivery_City'] ?><br>
                <?= $order['Delivery_State'] ?> <?= $order['Delivery_Postcode'] ?>
            </div>
        </div>
        <table class="items">
            <thead>
                <tr>
                    <th>SR.</th>
                    <th>PRODUCT TITLE</th>
                    <th>QUANTITY</th>
                    <th>ITEM PRICE</th>
                    <th>AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total = 0; $index = 1;
                foreach ($items as $item):
                    $total += $item['Order_Subtotal'];
                ?>
                <tr>
                    <td><?= $index++ ?></td>
                    <td><?= htmlspecialchars($item['ProductName']) ?></td>
                    <td><?= $item['Order_Quantity'] ?></td>
                    <td>RM<?= number_format($item['Order_Subtotal'] / $item['Order_Quantity'], 2) ?></td>
                    <td>RM<?= number_format($item['Order_Subtotal'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="summary">
            <div><strong>Payment Method:</strong> Card  </div>
            <div><strong>Shipping:</strong> RM<?= number_format($order['Shipping_Fee'] ?? 20.00, 2) ?></div>
            <div><strong>Total:</strong> RM<?= number_format($total + ($order['Shipping_Fee'] ?? 20.00), 2) ?></div>
        </div>
    </div>
    <button onclick="window.print()" class="floating-print-btn">Print Invoice</button>
<button onclick="downloadPDF()" class="floating-download-btn">
  Download PDF
</button>
<script>
function downloadPDF() {
  const element = document.querySelector('.invoice-container');
  const opt = {
    margin:       0.3,
    filename:     'invoice_<?= $order['OrderID'] ?>.pdf',
    image:        { type: 'jpeg', quality: 0.98 },
    html2canvas:  { 
      scale: 2,
      backgroundColor: '#ffffff'  
    },
    jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
  };
  html2pdf().set(opt).from(element).save();
}

</script>
    
</body>

</html>
