<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
    exit();
}

$customerId = $_SESSION['customer_id'];

// cancel processing orders
$cancel_query = "UPDATE 08_order SET OrderStatus = 'Cancelled' WHERE CustomerID = ? AND OrderStatus = 'Processing'";
$cancel_stmt = $conn->prepare($cancel_query);
$cancel_stmt->bind_param("i", $customerId);
$cancel_success = $cancel_stmt->execute();
$cancel_stmt->close();

// delete customer
$delete_query = "UPDATE 02_customer SET Is_Deleted = 1 WHERE CustomerID = ?";
$delete_stmt = $conn->prepare($delete_query);
$delete_stmt->bind_param("i", $customerId);
$delete_success = $delete_stmt->execute();
$delete_stmt->close();

// to check
if ($cancel_success && $delete_success) {
    session_destroy();
    echo json_encode(['status' => 'success', 'message' => 'Your account has been deleted.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Something went wrong while deleting your account.']);
}
