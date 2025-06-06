<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';

// Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Delete customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Customerid'])) {
    $id = intval($_POST['Customerid']);
    $stmt = $pdo->prepare("DELETE FROM 02_customer WHERE Customerid = ?");
    $stmt->execute([$id]);
    
    header("Location: admin_layout.php?page=admin_view_customers");
    exit();
} else {
    header("Location: admin_layout.php?page=admin_view_customers");
    exit();
}
?>
