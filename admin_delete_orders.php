<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $orderId = intval($_GET['id']);

    try {
        $stmt0 = $pdo->prepare("DELETE FROM 14_order_payment_method WHERE OrderID = ?");
        $stmt0->execute([$orderId]);

        $stmt1 = $pdo->prepare("DELETE FROM 08_order_details WHERE OrderID = ?");
        $stmt1->execute([$orderId]);

        $stmt2 = $pdo->prepare("DELETE FROM 07_order WHERE OrderID = ?");
        $stmt2->execute([$orderId]);

        header("Location: admin_layout.php?page=admin_view_allorder&delete=success");
        exit();
    } catch (Exception $e) {
        echo "❌ Delete failed: " . $e->getMessage();
        exit();
    }
} else {
    echo "❌ Invalid ID.";
    exit();
}
