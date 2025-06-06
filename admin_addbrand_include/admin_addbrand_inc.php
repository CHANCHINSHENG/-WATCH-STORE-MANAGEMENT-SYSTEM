<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once '../admin_login_include/config_session.php';
    require_once '../admin_login_include/db.php';
    require_once 'admin_addbrand_model.php';
    require_once 'admin_addbrand_view.php';

    $brandName = $_POST['BrandName'] ?? '';
    $imagePath = uploadBrandImage($_FILES['BrandImage'] ?? null);

    if (empty($brandName) || empty($imagePath)) {
        setError("❌ Please provide both brand name and a valid image.");
        redirectBack();
    }

    $result = insertdetail($pdo, $brandName, $imagePath);

    if (str_starts_with($result, "✅")) {
        setSuccess($result);
        header("Location: ../admin_layout.php?page=admin_add_brand");
    } else {
        setError($result);
        redirectBack();
    }
    exit();
} else {
    header("Location: ../admin_layout.php?page=admin_add_brand");
    exit();
}
