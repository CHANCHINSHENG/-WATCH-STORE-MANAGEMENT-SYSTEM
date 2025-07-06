<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = $_POST['CategoryID'];
    $name = trim($_POST['CategoryName']);

    if (empty($name)) {
        $_SESSION['error_message'] = "❌ Category name cannot be empty.";
        header("Location: admin_layout.php?page=admin_edit_category&id=$id");
        exit();
    }

    // ✅ Check for duplicate category name (excluding current ID)
    $check = $pdo->prepare("SELECT COUNT(*) FROM 04_category WHERE CategoryName = ? AND CategoryID != ?");
    $check->execute([$name, $id]);
    $exists = $check->fetchColumn();

    if ($exists > 0) {
        $_SESSION['error_message'] = "❌ Category name '$name' already exists.";
        header("Location: admin_layout.php?page=admin_edit_category&id=$id");
        exit();
    }

    // ✅ Update if name is unique
    $stmt = $pdo->prepare("UPDATE 04_category SET CategoryName = ? WHERE CategoryID = ?");
    $stmt->execute([$name, $id]);

    $_SESSION['success_message'] = "✅ Category updated successfully.";
    header("Location: admin_layout.php?page=admin_edit_category&id=$id");
    exit();
}
?>
