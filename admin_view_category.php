<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';

// Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch categories from DB
$stmt = $pdo->prepare("SELECT * FROM 04_category");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="admin_view_category.css">

<div class="page-wrapper">
    <div class="content-container">
        <div class="header">
            <h2>Category</h2>
        </div>
          <div class="top-action">
            <a href="admin_layout.php?page=admin_add_category" class="btn add-btn">＋ Add Category</a>
        </div>

        <!-- Search bar -->
        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Search Category Name...">
            <button id="filterButton" class="btn filter-btn">Filter</button>
            <button id="resetButton" class="btn reset-btn">Reset</button>
        </div>

        <!-- Table -->
        <table class="products-table" id="categoryTable">
            <thead>
                <tr>
                    <th>Category Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?= htmlspecialchars($category['CategoryName']) ?></td>
                        <td>
                        <div class="action-buttons">
                            <a href="admin_layout.php?page=admin_edit_category&id=<?= $category['CategoryID'] ?>"
                            class="btn edit-btn">
                            Edit 
                            </a>
                        </div>
                    </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
