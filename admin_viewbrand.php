<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM 03_brand");
$stmt->execute();
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="admin_viewbrand.css">

<div class="page-wrapper">
    <div class="content-container">
        <div class="header">
            <h2>Brands</h2>
        </div>        
        <div class="top-action">
            <a href="admin_layout.php?page=admin_add_brand" class="btn add-btn">＋ Add Brand</a>
        </div>

        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Search Brand Name...">
            <button class="btn filter-btn" id="filterButton">Filter</button>
            <button class="btn reset-btn" id="resetButton">Reset</button>
        </div>

        <table class="products-table" id="brandTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Brand Name</th>
                    <th>Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($brands as $brand): ?>
                    <tr>
                        <td><?= htmlspecialchars($brand['BrandID']) ?></td>
                        <td><?= htmlspecialchars($brand['BrandName']) ?></td>
                        <td>
                            <?php if (!empty($brand['BrandImage'])): ?>
                                <img src="uploads/<?= $brand['BrandImage'] ?>" alt="<?= $brand['BrandName'] ?>" width="80">

                            <?php else: ?>
                                <span class="no-image">No image</span>
                            <?php endif; ?>
                        </td>
                        <td>
                          <div class="action-buttons">
                        <a href="admin_layout.php?page=admin_edit_brand&id=<?= $brand['BrandID'] ?>" 
                             class="btn edit-btn">Edit</a>
</div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
