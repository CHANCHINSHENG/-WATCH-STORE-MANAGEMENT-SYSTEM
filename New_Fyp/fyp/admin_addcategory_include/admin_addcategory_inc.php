<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once '../admin_login_include/config_session.php';
    require_once '../admin_login_include/db.php';
    require_once 'admin_addcategory_model.php';
    require_once 'admin_addcategory_view.php';

    $categoryName = $_POST['CategoryName'] ?? '';

    if (empty($categoryName)) {
        setError("❌ Please provide a category name.");
        redirectBack();
    }

    $result = insertCategory($pdo, $categoryName);

    if (str_starts_with($result, "✅")) {
        setSuccess($result);
        header("Location: ../admin_layout.php?page=admin_add_category");
    } else {
        setError($result);
        redirectBack();
    }
    exit();
} else {
    header("Location: ../admin_layout.php?page=admin_add_category");
    exit();
}
