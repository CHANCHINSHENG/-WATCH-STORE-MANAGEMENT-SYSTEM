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
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT CartID FROM 11_cart WHERE CustomerID = ?");
        $stmt->execute([$customerId]);
        $cartIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($cartIds)) {
            $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
            $stmt = $pdo->prepare("DELETE FROM 12_cart_item WHERE CartID IN ($placeholders)");
            $stmt->execute($cartIds);
        }

        $stmt = $pdo->prepare("DELETE FROM 11_cart WHERE CustomerID = ?");
        $stmt->execute([$customerId]);

        $stmt = $pdo->prepare("DELETE FROM 02_customer WHERE CustomerID = ?");
        $stmt->execute([$customerId]);

        $pdo->commit();

        header("Location: admin_layout.php?page=admin_view_customer&deletecustomer=success");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        header("Location: admin_layout.php?page=admin_view_customer&deletecustomer=fail");
        exit();
    }

} else {
    header("Location: admin_layout.php?page=admin_view_customer&deletecustomer=fail");
    exit();
}
?>
