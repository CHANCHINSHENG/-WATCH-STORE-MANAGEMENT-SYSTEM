<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $customerId = intval($_GET['id']);

    $stmt = $pdo->prepare("SELECT CartID FROM 11_cart WHERE CustomerID = ?");
    $stmt->execute([$customerId]);
    $cartIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($cartIds)) {
        $inClause = implode(',', array_fill(0, count($cartIds), '?'));
        $stmt = $pdo->prepare("DELETE FROM 12_cart_item WHERE CartID IN ($inClause)");
        $stmt->execute($cartIds);
    }

    $stmt = $pdo->prepare("DELETE FROM 11_cart WHERE CustomerID = ?");
    $stmt->execute([$customerId]);

    $stmt = $pdo->prepare("DELETE FROM 02_customer WHERE CustomerID = ?");
    $stmt->execute([$customerId]);

    header("Location: admin_layout.php?page=admin_view_customer");
    exit();
} else {
    header("Location: admin_layout.php?page=admin_view_customer");
    exit();
}
?>
