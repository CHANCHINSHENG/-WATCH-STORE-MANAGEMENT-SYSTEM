<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["order_id"])) {
    $orderID = $_POST["order_id"];

    $stmt = $pdo->prepare("UPDATE 08_order SET Admin_Payment_Confirmation = 'Confirmed', OrderStatus = 'Done Processing',Customer_Review_Status = 'Eligible' WHERE OrderID = ?");
    $stmt->execute([$orderID]);

    header("Location: admin_layout.php?page=admin_view_allorder");
    exit();
}
?>
