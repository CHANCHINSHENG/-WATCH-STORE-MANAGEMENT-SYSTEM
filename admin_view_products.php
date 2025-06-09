<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Pagination setup
$page = isset($_GET['pagenum']) && is_numeric($_GET['pagenum']) ? (int)$_GET['pagenum'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total product count
$total_stmt = $pdo->query("SELECT COUNT(*) FROM 05_PRODUCT");
$total_products = $total_stmt->fetchColumn();

// Fetch products with limit
$query = "SELECT p.ProductID, p.ProductName, p.Product_Price, p.Product_Status, 
                 p.Product_Image, 
                 c.CategoryName, b.BrandName
          FROM 05_PRODUCT p
          LEFT JOIN 04_CATEGORY c ON p.CategoryID = c.CategoryID
          LEFT JOIN 03_BRAND b ON p.BrandID = b.BrandID
          LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt;
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

        <div class="filter-row">
            <input type="text" id="searchInput" placeholder="Search Product">
            <button id="filterButton" class="btn filter-btn">Filter</button>
            <button id="resetButton" class="btn reset-btn">Reset</button>
        </div>

        <?php if ($result->rowCount() > 0) { ?>
            <table id="productTable" class="products-table">
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
                    <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)) { ?>
                        <tr>
                            <td><img src="admin_addproduct_include/<?= htmlspecialchars($row['Product_Image']) ?>" alt="Product Image" class="product-image-large"></td>
                            <td><?= htmlspecialchars($row['ProductName']) ?></td>
                            <td>RM<?= number_format($row['Product_Price'], 2) ?></td>
                            <td><?= htmlspecialchars($row['CategoryName'] ?? 'No Category') ?></td>
                            <td><?= htmlspecialchars($row['BrandName'] ?? 'No Brand') ?></td>
                            <td>
                                <span class="status-badge <?= strtolower($row['Product_Status']) === 'available' ? 'status-available' : 'status-outofstock' ?>">
                                    <?= htmlspecialchars($row['Product_Status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="admin_layout.php?page=admin_edit_product&id=<?= $row['ProductID'] ?>" class="btn edit-btn">Edit</a>
                                <a href="#" class="btn delete-btn btn-delete" data-id="<?= $row['ProductID'] ?>" data-name="<?= htmlspecialchars($row['ProductName']) ?>" data-type="product">Delete</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <?php
            $total_pages = ceil($total_products / $limit);
            if ($total_pages > 1) {
                echo '<div class="pagination">';
                if ($page > 1) {
                    echo '<a class="page-btn" href="admin_layout.php?page=admin_view_products&pagenum=' . ($page - 1) . '">« Prev</a>';
                }
                for ($i = 1; $i <= $total_pages; $i++) {
                    echo '<a class="page-btn' . ($page == $i ? ' active' : '') . '" href="admin_layout.php?page=admin_view_products&pagenum=' . $i . '">' . $i . '</a>';
                }
                if ($page < $total_pages) {
                    echo '<a class="page-btn" href="admin_layout.php?page=admin_view_products&pagenum=' . ($page + 1) . '">Next »</a>';
                }
                echo '</div>';
            }
            ?>

        <?php } else { ?>
            <div class="empty-state">
                <p>No products found in the database.</p>
            </div>
        <?php } ?>
    </div>
</div>
