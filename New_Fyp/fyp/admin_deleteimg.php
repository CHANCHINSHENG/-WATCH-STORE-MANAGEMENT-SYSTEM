        <?php
    require_once 'admin_login_include/config_session.php';
    require_once 'admin_login_include/db.php';

    if (!isset($_SESSION['admin_id'])) {
        header("Location: admin_login.php");
        exit();
    }

    $admin_id = $_SESSION['admin_id'];

    // 取得目前圖片路徑
    $stmt = $pdo->prepare("SELECT ProfileImage FROM 01_admin WHERE AdminID  = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && !empty($admin['ProfileImage'])) {
        $imagePath = $admin['ProfileImage'];

        // 從資料夾中刪除圖片
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        // 從資料庫中清除圖片欄位
        $stmt = $pdo->prepare("UPDATE 01_admin SET ProfileImage = NULL WHERE AdminID  = ?");
        $stmt->execute([$admin_id]);
    }

    header("Location: admin_layout.php?page=admin_edit_profile");
    exit();
