<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$stmt = $pdo->prepare("SELECT Admin_Role FROM 01_admin WHERE AdminID = ?");
$stmt->execute([$admin_id]);
$currentRole = $stmt->fetchColumn();

$staffStmt = $pdo->query("SELECT * FROM 01_admin");
$staffs = $staffStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="admin_view_staff.css">
<div class="page-wrapper">
  <div class="content-container">
    <div class="header">
      <h2>Staff Management</h2>
    </div>

    <?php if ($currentRole === 'super admin'): ?>
            <div class="top-action">
            <a href="admin_layout.php?page=admin_add_newstaff" class="btn add-btn">＋ Add New Staff</a>
        </div>
      <?php endif; ?>
          <div class="filter-row">
            <input type="text" id="searchInput" placeholder="Search Staff Name.....">
            <button id="filterButton" class="btn filter-btn">Filter</button>
            <button id="resetButton" class="btn reset-btn">Reset</button>
        </div>

    <?php if (empty($staffs)): ?>
      <div class="empty-state">No staff found.</div>
    <?php else: ?>
      <table class="products-table" id="staffTable">
        <thead>
          <tr>
            <th>Name</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Profile Image</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($staffs as $staff): ?>
            <tr>
              <td><?= htmlspecialchars($staff['Admin_Name']) ?></td>
              <td><?= htmlspecialchars($staff['Admin_Username']) ?></td>
              <td><?= htmlspecialchars($staff['Admin_Email']) ?></td>
              <td>
                <span class="status-badge <?= $staff['Admin_Role'] === 'super admin' ? 'status-available' : 'status-outofstock' ?>">
                  <?= htmlspecialchars($staff['Admin_Role']) ?>
                </span>
              </td>
              <td>
                <?php if (!empty($staff['ProfileImage'])): ?>
                  <img src="<?= $staff['ProfileImage'] ?>" alt="Profile" width="40">
                <?php else: ?>
                  <em>N/A</em>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($currentRole === 'super admin'): ?>
                  <div class="action-buttons">
                    <a href="admin_layout.php?page=superadmin_editstaff&id=<?= $staff['AdminID'] ?>" class="btn edit-btn">Edit</a>
                  </div>
                <?php else: ?>
                  <em>No Access</em>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
