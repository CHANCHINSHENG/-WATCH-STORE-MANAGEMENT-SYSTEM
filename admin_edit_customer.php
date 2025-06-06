<?php
require_once 'admin_login_include/db.php';
require_once 'admin_login_include/config_session.php';

// Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: admin_layout.php?page=admin_view_customer");
    exit();
}

$customer_id = $_GET['id'];

// Fetch customer data
$stmt = $pdo->prepare("SELECT * FROM 02_customer WHERE CustomerID = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    $_SESSION['flash_message'] = "Customer not found.";
    $_SESSION['flash_type'] = "error";
    header("Location: admin_layout.php?page=admin_view_customer");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['Cust_Username'];
    $email = $_POST['Cust_Email'];
    $phone = $_POST['Cust_PhoneNumber'];

    $update_stmt = $pdo->prepare("UPDATE 02_customer SET Cust_Username = ?, Cust_Email = ?, Cust_PhoneNumber = ? WHERE CustomerID = ?");
    $update_stmt->execute([$username, $email, $phone, $customer_id]);

    // Set flash message for success
    $_SESSION['flash_message'] = "Update Successfully";
    $_SESSION['flash_type'] = "success";

    header("Location: admin_layout.php?page=admin_edit_customer&id=$customer_id");
    exit();
}
?>

<link rel="stylesheet" href="admin_edit_customer.css">

<div class="dashboard-container">
    <h2>Edit Customer</h2>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="message <?= $_SESSION['flash_type'] ?? 'success' ?>">
            <?= $_SESSION['flash_message'] ?>
        </div>
        <?php
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        ?>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group">
            <label for="Cust_Username">Customer Name</label>
            <input type="text" name="Cust_Username" value="<?= htmlspecialchars($customer['Cust_Username']) ?>" required>
        </div>

        <div class="input-group">
            <label for="Cust_Email">Email</label>
            <input type="email" name="Cust_Email" value="<?= htmlspecialchars($customer['Cust_Email']) ?>" required>
        </div>

        <div class="input-group">
            <label for="Cust_PhoneNumber">Phone Number</label>
            <input type="text" name="Cust_PhoneNumber" value="<?= htmlspecialchars($customer['Cust_PhoneNumber']) ?>" required>
        </div>

        <div class="button-group">
            <a href="admin_layout.php?page=admin_view_customer" class="btn secondary-btn">Back</a>
            <button type="submit" class="btn primary-btn">Update Customer</button>
        </div>
    </form>
</div>
