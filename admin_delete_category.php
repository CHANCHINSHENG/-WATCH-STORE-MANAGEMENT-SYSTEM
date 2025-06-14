<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$category = null;
$products = [];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $category_id = $_GET['id'];

    $catStmt = $pdo->prepare("SELECT CategoryName FROM 04_category WHERE CategoryID = ?");
    $catStmt->execute([$category_id]);
    $category = $catStmt->fetch(PDO::FETCH_ASSOC);

    if ($category) {
        $productStmt = $pdo->prepare("SELECT ProductID, ProductName FROM 05_product WHERE CategoryID = ?");
        $productStmt->execute([$category_id]);
        $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Confirm Delete Category</title>
    <style>
        body { font-family: Arial; padding: 30px; background: #f9f9f9; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 8px rgba(0,0,0,0.1); max-width: 700px; margin: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
        .warning { color: red; margin: 10px 0; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; margin-right: 10px; cursor: pointer; }
        .btn-danger { background-color: red; color: white; }
        .btn-cancel { background-color: grey; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; }
        .hidden { display: none; }
    </style>
    <script>
        function confirmDelete(hasProducts) {
            let message = hasProducts
                ? "⚠️ There are still products in this category! \nIf you continue, these products will also be deleted. \n\nAre you sure you want to delete? "
: "Are you sure you want to delete this category?";
            if (confirm(message)) {
                document.getElementById("deleteForm").submit();
            }
        }
    </script>
</head>
<body>
<div class="container">
    <?php if (!$category): ?>
        <h2>Cannot find Category</h2>
        <a href="admin_layout.php?page=admin_view_category" class="btn-cancel">Back</a>
    <?php else: ?>
        <h2>Delete Category: <?= htmlspecialchars($category['CategoryName']) ?></h2>

        <?php if (count($products) > 0): ?>
            <p class="warning">⚠️ The following products still use this category:</p>
            <table>
                <thead><tr><th>Product ID</th><th>Product Name</th></tr></thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['ProductID']) ?></td>
                            <td><?= htmlspecialchars($p['ProductName']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>✅ This category is not used by any product and can be safely deleted.</p>
        <?php endif; ?>

        <form method="POST" action="admin_confirm_delete_category.php" id="deleteForm">
            <input type="hidden" name="category_id" value="<?= htmlspecialchars($category_id) ?>">
            <button type="button"
                    class="btn btn-danger"
                    onclick="confirmDelete(<?= count($products) > 0 ? 'true' : 'false' ?>)">
                Comfirm Delete
            </button>
            <a href="admin_layout.php?page=admin_view_category" class="btn-cancel">Cancel</a>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
