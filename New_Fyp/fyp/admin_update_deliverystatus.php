<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo "Unauthorized";
    exit();
}

$order_id = $_POST['order_id'] ?? null;
$delivery_status = $_POST['delivery_status'] ?? null;

if (!$order_id || !$delivery_status) {
    http_response_code(400);
    echo "Missing data";
    exit();
}

// 根據 order ID 找出 Tracking ID
$stmt = $pdo->prepare("SELECT TrackingID FROM 07_order WHERE OrderID = ?");
$stmt->execute([$order_id]);
$tracking = $stmt->fetch(PDO::FETCH_ASSOC);

if ($tracking && $tracking['TrackingID']) {
    $update = $pdo->prepare("UPDATE 06_tracking SET Delivery_Status = ? WHERE TrackingID = ?");
    $update->execute([$delivery_status, $tracking['TrackingID']]);
    echo "success";
} else {
    http_response_code(404);
    echo "No tracking record found";
}
