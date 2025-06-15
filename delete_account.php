<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$customer_id = $_SESSION['customer_id'];

$stmt = $conn->prepare("UPDATE 02_customer SET Is_Deleted = 1, Deleted_At = NOW() WHERE CustomerID = ?");
$stmt->bind_param("i", $customer_id);
$success = $stmt->execute();

if ($success) {
    session_destroy(); 
    echo json_encode(['status' => 'success', 'message' => 'Account deleted successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete account.']);
}
?>
