<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $customerId = intval($_GET['id']);

    try {
        $stmt = $pdo->prepare("UPDATE 02_customer SET Is_Deleted = 1 WHERE CustomerID = ?");
        $stmt->execute([$customerId]);

        header("Location: admin_layout.php?page=admin_view_customer&deletecustomer=success");
        exit();

    } catch (PDOException $e) {
        header("Location: admin_layout.php?page=admin_view_customer&deletecustomer=fail");
        exit();
    }

} else {
    header("Location: admin_layout.php?page=admin_view_customer&deletecustomer=fail");
    exit();
}

