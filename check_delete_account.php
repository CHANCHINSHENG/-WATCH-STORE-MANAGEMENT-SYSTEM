<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['hasProcessing' => false]);
    exit();
}

$customerId = $_SESSION['customer_id'];

$stmt = $conn->prepare("SELECT COUNT(*) FROM 08_order WHERE CustomerID = ? AND OrderStatus = 'Processing'");
$stmt->bind_param("i", $customerId);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

echo json_encode(['hasProcessing' => $count > 0]);
