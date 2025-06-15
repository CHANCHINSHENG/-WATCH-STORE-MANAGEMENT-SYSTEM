<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $brand_id = $_GET['id'];

    // Get image path first
    $stmtImg = $pdo->prepare("SELECT BrandImage FROM 03_BRAND WHERE BrandID = ?");
    $stmtImg->execute([$brand_id]);
    $brand = $stmtImg->fetch(PDO::FETCH_ASSOC);

    if ($brand) {
        $imagePath = $brand['BrandImage'];
        $fullImagePath = __DIR__ . '/' . $imagePath;

        //  Delete the brand record
        $stmt = $pdo->prepare("DELETE FROM 03_BRAND WHERE BrandID = ?");
        
        if ($stmt->execute([$brand_id])) {
            // Step 3: Delete the image file if it exists
            if (!empty($imagePath) && file_exists($fullImagePath)) {
                unlink($fullImagePath);
            }
        } else {
            // 顯示錯誤訊息
            print_r($stmt->errorInfo());
            exit();
        }
    }
}

header("Location: admin_layout.php?page=admin_viewbrand");
exit();
