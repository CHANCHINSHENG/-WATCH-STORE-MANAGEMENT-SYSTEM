<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin_dashboard.css"> 

</head>
<body>
    <div class="dashboard-container">
        <h2>Welcome, Admin</h2>
        <div class="dashboard-links">
            <a href="admin_add_product.php">
                <span>➕</span>
                Add New Product
            </a>
            <a href="admin_view_products.php">
                <span>📦</span>
                View Products
            </a>
            <a href="admin_logout.php">
                <span>🚪</span>
                Logout
            </a>
        </div>
       
    </div>
</body>
</html>