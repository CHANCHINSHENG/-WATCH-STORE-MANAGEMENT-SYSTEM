<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';

// Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch customers from DB
$stmt = $pdo->prepare("SELECT * FROM 02_customer");
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href=".css">
<div class="page-wrapper">
    <div class="content-container">
        <div class="header">
            <h2>All Customers</h2>
        </div>

        <!-- Search bar -->
        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Search by name, email, or phone...">
            <button id="filterButton" class="btn filter-btn">Filter</button>
            <button id="resetButton" class="btn reset-btn">Reset</button>
        </div>

        <!-- Table -->
        <table class="customer-table" id="customerTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?= htmlspecialchars($customer['CustomerID']) ?></td>
                        <td><?= htmlspecialchars($customer['Cust_Username']) ?></td>
                        <td><?= htmlspecialchars($customer['Cust_Email']) ?></td>
                        <td><?= htmlspecialchars($customer['Cust_PhoneNumber']) ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="admin_layout.php?page=admin_edit_customer&id=<?= $customer['CustomerID'] ?>" class="btn edit-btn">Edit</a>
                                <a href="admin_layout.php?page=admin_view_cusorder&id=<?=$customer['CustomerID'] ?>" class="btn edit-btn">View</a>
                                <form method="POST" action="admin_delete_customer.php" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $customer['CustomerID'] ?>">
                                    <button type="submit" class="btn delete-btn" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            
        </table>
    </div>
</div>



