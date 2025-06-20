<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Pagination settings
$limit = 10;
$page = isset($_GET['pagenum']) && is_numeric($_GET['pagenum']) ? (int)$_GET['pagenum'] : 1;
$offset = ($page - 1) * $limit;

// Count total customers
$total_stmt = $pdo->query("SELECT COUNT(*) FROM 02_customer");
$total_customers = $total_stmt->fetchColumn();
$total_pages = ceil($total_customers / $limit);

// Fetch paginated customers
$stmt = $pdo->prepare("SELECT * FROM 02_customer ORDER BY CustomerID ASC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
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
                    <th>Customer Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                     <th>Status</th> 
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?= htmlspecialchars($customer['Cust_Username']) ?></td>
                        <td><?= htmlspecialchars($customer['Cust_Email']) ?></td>
                        <td><?= htmlspecialchars($customer['Cust_PhoneNumber']) ?></td>
                        <td>
                <?php if ($customer['Is_Deleted'] == 1): ?>
                    <span style="color: red; font-weight: bold;">Inactive</span>
                <?php else: ?>
                    <span style="color: green; font-weight: bold;">Active</span>
                <?php endif; ?>
            </td>
                        <td>
                            <div class="action-buttons">
                                <a href="admin_layout.php?page=admin_view_cusorder&id=<?= $customer['CustomerID'] ?>" class="btn edit-btn">View</a>
                                <button 
                                    class="btn btn-danger btn-delete" 
                                    data-id="<?= $customer['CustomerID'] ?>" 
                                    data-name="<?= $customer['Cust_Username'] ?>" 
                                    data-type="customer" 
                                    data-status="<?= $customer['Is_Deleted'] ? 'inactive' : 'active' ?>">
                                    Delete
                                    </button>

                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a class="page-btn" href="admin_layout.php?page=admin_view_customer&pagenum=<?= $page - 1 ?>">« Prev</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a class="page-btn <?= $page == $i ? 'active' : '' ?>" href="admin_layout.php?page=admin_view_customer&pagenum=<?= $i ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a class="page-btn" href="admin_layout.php?page=admin_view_customer&pagenum=<?= $page + 1 ?>">Next »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
