<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

require_once 'db.php';

$CustomerID = $_SESSION['customer_id'];

// 获取订单ID
if (!isset($_GET['order_id'])) {
    echo "Invalid request.";
    exit();
}

$order_id = intval($_GET['order_id']);

// 查询订单并验证归属
$order_query = "SELECT * FROM `07_order` WHERE OrderID = ? AND CustomerID = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $CustomerID);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo "Order not found or access denied.";
    exit();
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['Shipping_Name'];
    $address = $_POST['Shipping_Address'];
    $city = $_POST['Shipping_City'];
    $postcode = $_POST['Shipping_Postcode'];
    $state = $_POST['Shipping_State'];
    $phone = $_POST['Shipping_Phone'];

    $update_query = "
        UPDATE `07_order` 
        SET Shipping_Name = ?, Shipping_Address = ?, Shipping_City = ?, 
            Shipping_Postcode = ?, Shipping_State = ?, Shipping_Phone = ?
        WHERE OrderID = ? AND CustomerID = ?
    ";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssssssii", $name, $address, $city, $postcode, $state, $phone, $order_id, $CustomerID);
    
    if ($update_stmt->execute()) {
        header("Location: order_view.php?order_id=" . $order_id);
        exit();
    } else {
        echo "Failed to update address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Shipping Address</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #121212; color: #f1f1f1; }
        .container { margin-top: 50px; }
        .section { background-color: #1e1e1e; padding: 20px; border-radius: 10px; }
        label { color: #f1f1f1; }
    </style>
</head>
<body>
<div class="container">
    <div class="section">
        <h2>Edit Shipping Address</h2>
        <form method="POST">
            <div class="mb-3">
                <label for="Shipping_Name" class="form-label">Full Name</label>
                <input type="text" class="form-control" name="Shipping_Name" value="<?= htmlspecialchars($order['Shipping_Name']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="Shipping_Address" class="form-label">Address</label>
                <input type="text" class="form-control" name="Shipping_Address" value="<?= htmlspecialchars($order['Shipping_Address']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="Shipping_City" class="form-label">City</label>
                <input type="text" class="form-control" name="Shipping_City" value="<?= htmlspecialchars($order['Shipping_City']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="Shipping_Postcode" class="form-label">Postcode</label>
                <input type="text" class="form-control" name="Shipping_Postcode" value="<?= htmlspecialchars($order['Shipping_Postcode']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="Shipping_State" class="form-label">State</label>
                <input type="text" class="form-control" name="Shipping_State" value="<?= htmlspecialchars($order['Shipping_State']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="Shipping_Phone" class="form-label">Phone</label>
                <input type="text" class="form-control" name="Shipping_Phone" value="<?= htmlspecialchars($order['Shipping_Phone']) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Address</button>
            <a href="order_view.php?order_id=<?= $order_id ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
</body>
</html>
