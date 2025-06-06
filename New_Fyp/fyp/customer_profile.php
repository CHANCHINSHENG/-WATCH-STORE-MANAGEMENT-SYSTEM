<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

$CustomerID = $_SESSION['customer_id'];
require_once 'db.php';

$customer_query = "SELECT * FROM 02_customer WHERE CustomerID = ?";
$customer_stmt = $conn->prepare($customer_query);
$customer_stmt->bind_param("i", $CustomerID);
$customer_stmt->execute();
$customer = $customer_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212;
            color: #f1f1f1;
        }
        .section {
            background-color: #1e1e1e;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .section h2 {
            border-bottom: 1px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .table-dark th,
        .table-dark td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark mb-4">
  <div class="container d-flex justify-content-between">
    <a class="navbar-brand d-flex align-items-center" href="customermainpage.php">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-house me-2" viewBox="0 0 16 16">
        <path fill-rule="evenodd" d="M8 3.293l6 6V13.5a1.5 1.5 0 0 1-1.5 1.5h-3v-4H7v4H4.5A1.5 1.5 0 0 1 3 13.5V9.293l5-5zM7.293 2.5 1 8.793V14.5A1.5 1.5 0 0 0 2.5 16h11a1.5 1.5 0 0 0 1.5-1.5V8.793L8.707 2.5a1 1 0 0 0-1.414 0z"/>
      </svg>
      Home
    </a>
    <a href="customer_logout.php" class="btn btn-outline-light">Logout</a>
  </div>
</nav>

<div class="container py-5">
    <h1 class="text-center mb-5">Customer Dashboard</h1>

    <div class="section">
        <h2>Profile</h2>
        <p><strong>Name:</strong> <?= htmlspecialchars($customer['Cust_First_Name'] . ' ' . $customer['Cust_Last_Name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($customer['Cust_Email']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($customer['Cust_PhoneNumber']) ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($customer['Cust_Address'] . ' ' .$customer['Cust_Postcode'] . ', ' . $customer['Cust_City'] . ' ' .$customer['Cust_State'] ) ?></p>
        <a href="edit_profile.php" class="btn btn-warning mt-3">Edit Profile</a>
    </div>

    <div class="section">
    <h2>Orders</h2>
    <table class="table table-dark table-striped">
        <thead>
        <tr>
            <th>Order ID</th>
            <th>Date</th>
            <th>Status</th>
            <th>Total</th>
            <th>Tracking ID</th>
            <th>Shipping</th>
            <th>Actions</th> 
        </tr>
        </thead>
        <tbody>
        <?php
        $orders_query = 
        "
            SELECT o.OrderID, o.OrderDate, o.OrderStatus, o.TrackingID, o.Total_Price, 
                o.Shipping_Name, o.Shipping_Address, o.Shipping_City, 
                o.Shipping_Postcode, o.Shipping_State, o.Shipping_Phone,
                o.Admin_Payment_Confirmation,
                t.Delivery_Status 
            FROM `07_order` o
            LEFT JOIN `06_tracking` t ON o.TrackingID = t.TrackingID 
            WHERE o.CustomerID = ?
            ORDER BY o.OrderDate DESC
        ";
        $orders_stmt = $conn->prepare($orders_query);
        $orders_stmt->bind_param("i", $CustomerID);
        $orders_stmt->execute();
        $orders = $orders_stmt->get_result();

        if ($orders->num_rows === 0): ?>
            <tr><td colspan="7" class="text-center">No orders found.</td></tr>
        <?php endif;

        while ($row = $orders->fetch_assoc()): ?>
            <tr>
                <td><?= $row['OrderID'] ?></td>
                <td><?= $row['OrderDate'] ?></td>
                <td>
                    <?php
                    $display_order_status = '';
                    
                    if (isset($row['Delivery_Status']) && $row['Delivery_Status'] === 'Delivered') 
                    {
                        $display_order_status = 'Delivered';
                    } 
                    else if (isset($row['Admin_Payment_Confirmation']) && $row['Admin_Payment_Confirmation'] === 'Confirmed') 
                    {
                        $display_order_status = 'Shipped'; 
                    } 
                    else 
                    {
                        $display_order_status = $row['OrderStatus'] ?? 'Unknown'; 
                    }
                    echo htmlspecialchars($display_order_status);
                    ?>
                </td>
                <td>RM<?= number_format($row['Total_Price'], 2) ?></td>
                <td><?= $row['TrackingID'] ?></td>
                <td>
                    <?= htmlspecialchars($row['Shipping_Name']) ?><br>
                    <?= htmlspecialchars($row['Shipping_Address']) ?><br>
                    <?= htmlspecialchars($row['Shipping_Postcode'] . ' ' . $row['Shipping_City']) ?><br>
                    <?= htmlspecialchars($row['Shipping_State']) ?><br>
                    Phone: <?= htmlspecialchars($row['Shipping_Phone']) ?>
                </td>
                <td>
                    <a href="order_view.php?order_id=<?= $row['OrderID'] ?>" class="btn btn-sm btn-info mb-1">View</a><br>
                    <a href="order_edit_address.php?order_id=<?= $row['OrderID'] ?>" class="btn btn-sm btn-warning mb-1">Edit Address</a><br>
                    <a href="order_cancel.php?order_id=<?= $row['OrderID'] ?>" class="btn btn-sm btn-danger mb-1" onclick="return confirm('Are you sure you want to cancel this order?');">Cancel</a><br>
                    <a href="order_track.php?order_id=<?= $row['OrderID'] ?>" class="btn btn-sm btn-secondary">Track</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

  

</div>
</body>
</html>
