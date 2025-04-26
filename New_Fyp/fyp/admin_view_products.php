<?php
 require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$query = "SELECT p.ProductID, p.ProductName, p.Product_Price, p.Product_Status, 
                 c.CategoryName, b.BrandName
          FROM `05_PRODUCT` p
          LEFT JOIN `04_CATEGORY` c ON p.CategoryID = c.CategoryID
          LEFT JOIN `03_BRAND` b ON p.BrandID = b.BrandID";

$result = $pdo->query($query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Products - Watch Store Admin</title>
    <link rel="stylesheet" href="admin_view_products.css">
</head>
<body>
    <div class="page-wrapper">
        <a href="admin_dashboard.php" class="back-btn">
            ← Back to Dashboard
        </a>

        <div class="content-container">
            <div class="header">
                <h2>All Products</h2>
                <div class="watch-icon">⌚</div>
            </div>

            <?php if ($result->rowCount() > 0) { ?>
                <table class="products-table">
                    <thead>
                        <tr>
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
                                <td><?php echo htmlspecialchars($row['ProductName']); ?></td>
                                <td>$<?php echo number_format($row['Product_Price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['CategoryName'] ?? 'No Category'); ?></td>
                                <td><?php echo htmlspecialchars($row['BrandName'] ?? 'No Brand'); ?></td>
                                <td>
                                    <span class="status-badge <?php echo strtolower($row['Product_Status']) === 'available' ? 'status-available' : 'status-outofstock'; ?>">
                                        <?php echo htmlspecialchars($row['Product_Status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="admin_edit_product.php?id=<?php echo $row['ProductID']; ?>" class="btn edit-btn">
                                            Edit
                                        </a>
                                        <a href="admin_delete_product.php?id=<?php echo $row['ProductID']; ?>" 
                                           onclick="return confirm('Are you sure you want to delete this product?')" 
                                           class="btn delete-btn">
                                            Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <div class="empty-state">
                    <p>No products found in the database.</p>
                </div>
            <?php } ?>

          
        </div>
    </div>
</body>
</html>