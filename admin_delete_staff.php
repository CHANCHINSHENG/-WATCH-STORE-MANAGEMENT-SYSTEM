<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: admin_layout.php?page=admin_view_staff&delete=fail");
    exit();
}

$adminIdToDelete = intval($_GET['id']);
$currentAdminId = intval($_SESSION['admin_id']);

if ($adminIdToDelete === $currentAdminId) {
    header("Location: admin_layout.php?page=admin_view_staff&delete=self");
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM 01_admin WHERE AdminID = ?");
    $stmt->execute([$adminIdToDelete]);
    header("Location: admin_layout.php?page=admin_view_staff&delete=success");
} catch (Exception $e) {
    header("Location: admin_layout.php?page=admin_view_staff&delete=fail");
}
exit();
