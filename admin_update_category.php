<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';


if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['CategoryID'];
    $name = trim($_POST['CategoryName']);

    if (empty($name)) {
        echo "Category name cannot be empty.";
        exit();
    }

    $stmt = $pdo->prepare("UPDATE 04_category SET CategoryName = ? WHERE CategoryID = ?");
    $stmt->execute([$name, $id]);
$_SESSION['success_message'] = "Category updated successfully.";
header("Location: admin_layout.php?page=admin_edit_category&id=" . $id);

    exit();
}
?>
