<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand_id   = $_POST['BrandID'];
    $brand_name = trim($_POST['BrandName']);

    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM 03_brand WHERE LOWER(BrandName) = LOWER(?) AND BrandID != ?");
    $checkStmt->execute([$brand_name, $brand_id]);
    $existing = $checkStmt->fetchColumn();

    if ($existing > 0) {
        $_SESSION['error_message'] = "❌ Brand name '$brand_name' already exists.";
        header("Location: admin_layout.php?page=admin_edit_brand&id=$brand_id");
        exit();
    }

    $stmt = $pdo->prepare("SELECT BrandImage FROM 03_brand WHERE BrandID = ?");
    $stmt->execute([$brand_id]);
    $brand = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$brand) {
        echo "Brand not found.";
        exit();
    }

    if (!empty($_FILES['BrandImage']['name'])) {
        if (!empty($brand['BrandImage']) && file_exists("uploads/" . $brand['BrandImage'])) {
            unlink("uploads/" . $brand['BrandImage']);
        }

        $target_dir = "uploads/";
        $image_name = time() . '_' . basename($_FILES['BrandImage']['name']);
        $target_path = $target_dir . $image_name;
        move_uploaded_file($_FILES['BrandImage']['tmp_name'], $target_path);
    } else {
        $image_name = $brand['BrandImage'];
    }

    $update = $pdo->prepare("UPDATE 03_brand SET BrandName = ?, BrandImage = ? WHERE BrandID = ?");
    $update->execute([$brand_name, $image_name, $brand_id]);

    $_SESSION['success_message'] = "✅ Brand updated successfully.";
    header("Location: admin_layout.php?page=admin_edit_brand&id=$brand_id");
    exit();
}
?>
