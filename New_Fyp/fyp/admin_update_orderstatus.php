<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo "Unauthorized";
    exit();
}

$order_id = $_POST['order_id'] ?? null;
$order_status = $_POST['order_status'] ?? null;

if (!$order_id || !$order_status) {
    http_response_code(400);
    echo "Missing data";
    exit();
}

// ✅ Validate status against allowed values
$allowed_statuses = ['Processing', 'Done Processing'];
if (!in_array($order_status, $allowed_statuses)) {
    http_response_code(400);
    echo "Invalid status";
    exit();
}

// ✅ Update the order
$stmt = $pdo->prepare("UPDATE 07_order SET OrderStatus = ? WHERE OrderID = ?");
if ($stmt->execute([$order_status, $order_id])) {
    echo "success";
} else {
    http_response_code(500);
    echo "Update failed";
}
