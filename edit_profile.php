<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

$CustomerID = $_SESSION['customer_id'];
require_once 'db.php';

// 提交表单时更新数据
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $postcode = $_POST['postcode'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';

    $update_query = "UPDATE `02_customer` SET 
        Cust_First_Name = ?, 
        Cust_Last_Name = ?, 
        Cust_Email = ?, 
        Cust_PhoneNumber = ?, 
        Cust_Address = ?, 
        Cust_Postcode = ?, 
        Cust_City = ?, 
        Cust_State = ?
        WHERE CustomerID = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssssssi", $first_name, $last_name, $email, $phone, $address, $postcode, $city, $state, $CustomerID);
    $stmt->execute();

    header("Location: customer_profile.php");
    exit();
}

// 获取原本数据
$customer_query = "SELECT * FROM `02_customer` WHERE CustomerID = ?";
$customer_stmt = $conn->prepare($customer_query);
$customer_stmt->bind_param("i", $CustomerID);
$customer_stmt->execute();
$customer = $customer_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212;
            color: #f1f1f1;
        }
        .form-container {
            background-color: #1e1e1e;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            margin: 50px auto;
        }
        input, textarea {
            background-color: #2a2a2a;
            color: white;
            border: 1px solid #444;
        }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Edit Profile</h2>
    <form method="POST" action="">
        <div class="mb-3">
            <label>First Name</label>
            <input name="first_name" class="form-control" value="<?= htmlspecialchars($customer['Cust_First_Name']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Last Name</label>
            <input name="last_name" class="form-control" value="<?= htmlspecialchars($customer['Cust_Last_Name']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input name="email" type="email" class="form-control" value="<?= htmlspecialchars($customer['Cust_Email']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Phone</label>
            <input name="phone" class="form-control" value="<?= htmlspecialchars($customer['Cust_PhoneNumber']) ?>">
        </div>
        <div class="mb-3">
            <label>Address</label>
            <textarea name="address" class="form-control"><?= htmlspecialchars($customer['Cust_Address']) ?></textarea>
        </div>
        <div class="mb-3">
            <label>Postcode</label>
            <input name="postcode" class="form-control" value="<?= htmlspecialchars($customer['Cust_Postcode']) ?>">
        </div>
        <div class="mb-3">
            <label>City</label>
            <input name="city" class="form-control" value="<?= htmlspecialchars($customer['Cust_City']) ?>">
        </div>
        <div class="mb-3">
            <label>State</label>
            <input name="state" class="form-control" value="<?= htmlspecialchars($customer['Cust_State']) ?>">
        </div>
        <button type="submit" class="btn btn-success">Save</button>
        <a href="customer_profile.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
