        <?php
    require_once 'admin_login_include/config_session.php';
    require_once 'admin_login_include/db.php';

    if (!isset($_SESSION['admin_id'])) {
        header("Location: admin_login.php");
        exit();
    }

    $admin_id = $_SESSION['admin_id'];

    $stmt = $pdo->prepare("SELECT ProfileImage FROM 01_admin WHERE AdminID  = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && !empty($admin['ProfileImage'])) {
        $imagePath = $admin['ProfileImage'];

        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        $stmt = $pdo->prepare("UPDATE 01_admin SET ProfileImage = NULL WHERE AdminID  = ?");
        $stmt->execute([$admin_id]);
    }

    header("Location: admin_layout.php?page=admin_edit_profile");
    exit();
