<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_addcategory_include/admin_addcategory_view.php'; 
?>

<link rel="stylesheet" href="admin_add_category.css">
<div class="page-wrapper">
  <div class="form-container">
    <h2>Add New Category</h2>

    <?php displayFormMessages(); ?>

    <form method="POST" action="admin_addcategory_include/admin_addcategory_inc.php">
        <div class="input-group">
            <label for="CategoryName">Category Name</label>
            <input type="text" name="CategoryName" placeholder="Enter category name" required>
        </div>

        <div class="button-group">
            <a href="admin_layout.php?page=admin_view_category" class="btn secondary-btn">Back</a>
            <button type="submit" class="btn primary-btn">Add Category</button>
        </div>
    </form>
  </div>
</div>
