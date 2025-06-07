<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM 02_customer");
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="admin_view_customer.css">
<div class="page-wrapper">
    <div class="content-container">
        <div class="header">
            <h2>All Customers</h2>
        </div>

        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Search by name, email, or phone...">
            <button id="filterButton" class="btn filter-btn">Filter</button>
            <button id="resetButton" class="btn reset-btn">Reset</button>
        </div>

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
                                <a href="admin_layout.php?page=admin_view_cusorder&id=<?= $customer['CustomerID'] ?>" class="btn edit-btn">View</a>
                                <button class="btn delete-btn btn-delete" 
                                        data-id="<?= $customer['CustomerID'] ?>" 
                                        data-name="<?= htmlspecialchars($customer['Cust_Username']) ?>"
                                        data-type="customer">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
