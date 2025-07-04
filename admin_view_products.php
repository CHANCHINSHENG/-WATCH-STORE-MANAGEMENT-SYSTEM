<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$page = isset($_GET['pagenum']) && is_numeric($_GET['pagenum']) ? (int)$_GET['pagenum'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$baseQuery = "FROM 05_PRODUCT p
              LEFT JOIN 04_CATEGORY c ON p.CategoryID = c.CategoryID
              LEFT JOIN 03_BRAND b ON p.BrandID = b.BrandID";

$whereClause = "";
$params = [];

if ($search !== '') {
    $whereClause = " WHERE p.ProductName LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

$countStmt = $pdo->prepare("SELECT COUNT(*) $baseQuery $whereClause");
$countStmt->execute($params);
$total_products = $countStmt->fetchColumn();

$query = "SELECT p.ProductID, p.ProductName, p.Product_Price, p.Product_Status, p.Product_Stock_Quantity,
       (SELECT ImagePath 
        FROM 06_product_images i 
        WHERE i.ProductID = p.ProductID 
        ORDER BY IsPrimary DESC, ImageOrder ASC 
        LIMIT 1) AS Product_Image,
       c.CategoryName, b.BrandName
$baseQuery $whereClause
LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR); 
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$queryParams = [
    'page' => 'admin_view_products',
    'search' => $search
];
$baseUrl = 'admin_layout.php?' . http_build_query($queryParams);
?>

<link rel="stylesheet" href="admin_view_products.css">
<div class="page-wrapper">
    <div class="content-container">
        <div class="header">
            <h2>Products</h2>
        </div>

        <div class="top-action">
            <a href="admin_layout.php?page=admin_add_product" class="btn add-btn">＋ Add Product</a>
        </div>

        <form method="GET" action="admin_layout.php" class="filter-row">
            <input type="hidden" name="page" value="admin_view_products">
            <input type="text" id="searchInput" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search Product">
            <button type="submit" class="btn filter-btn">Filter</button>
            <a href="admin_layout.php?page=admin_view_products" class="btn reset-btn">Reset</a>
        </form>

        <?php if (count($products) > 0): ?>
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Product Image</th>
                        <th>Product Name</th>
                        <th>Price</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $row): ?>
                        <tr>
                            <td><img src="admin_addproduct_include/<?= htmlspecialchars($row['Product_Image']) ?>" class="product-image-large"></td>
                            <td><?= htmlspecialchars($row['ProductName']) ?></td>
                            <td>RM<?= number_format($row['Product_Price'], 2) ?></td>
                            <td><?= htmlspecialchars($row['CategoryName'] ?? 'No Category') ?></td>
                            <td><?= htmlspecialchars($row['BrandName'] ?? 'No Brand') ?></td>
                            <td>
                                <?php $stock = (int)$row['Product_Stock_Quantity']; ?>
                                <span class="status-badge <?= $stock > 0 ? 'status-available' : 'status-outofstock' ?>">
                                    <?= $stock > 0 ? 'Available' : 'Out of Stock' ?>
                                </span>
                            </td>
                            <td>
                                <a href="admin_layout.php?page=admin_edit_product&id=<?= $row['ProductID'] ?>" class="btn edit-btn">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php $total_pages = ceil($total_products / $limit); ?>

            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a class="page-btn" href="<?= $baseUrl ?>&pagenum=<?= $page - 1 ?>">« Prev</a>
                <?php endif; ?>

                <?php
                if ($page > 3) {
                    echo '<a class="page-btn" href="' . $baseUrl . '&pagenum=1">1</a>';
                    echo '<span class="page-btn dots">...</span>';
                }

                $start = max(1, $page - 1);
                $end = min($total_pages, $page + 1);
                for ($i = $start; $i <= $end; $i++) {
                    $activeClass = ($i == $page) ? ' active' : '';
                    echo '<a class="page-btn' . $activeClass . '" href="' . $baseUrl . '&pagenum=' . $i . '">' . $i . '</a>';
                }

                if ($page < $total_pages - 2) {
                    echo '<span class="page-btn dots">...</span>';
                    echo '<a class="page-btn" href="' . $baseUrl . '&pagenum=' . $total_pages . '">' . $total_pages . '</a>';
                }
                ?>

                <?php if ($page < $total_pages): ?>
                    <a class="page-btn" href="<?= $baseUrl ?>&pagenum=<?= $page + 1 ?>">Next »</a>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div class="empty-state">
                <p>No products found in the database.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
