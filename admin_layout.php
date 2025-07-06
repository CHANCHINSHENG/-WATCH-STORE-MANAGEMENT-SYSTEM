    <?php
    require_once 'admin_login_include/config_session.php';
    require_once 'admin_login_include/db.php';

    if (!isset($_SESSION['admin_id'])) {
        header("Location: admin_login.php");
        exit();
    }

    // Fetch admin info
    $admin_id = $_SESSION['admin_id'];
    $stmt = $pdo->prepare("SELECT * FROM 01_admin WHERE AdminID = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        echo "<p style='color:red;'>Admin not found in database.</p>";
        exit();
    }

    $_SESSION['admin_role'] = $admin['Admin_Role'];

    $profileImg = !empty($admin['ProfileImage']) ? htmlspecialchars($admin['ProfileImage']) : 'assets/default_avatar.png';

    $page = $_GET['page'] ?? 'admin_main_page';
    $allowed_pages = [
        'admin_main_page', 'admin_add_product', 'admin_view_products', 'admin_view_customer',
        'admin_edit_product', 'admin_edit_customer', 'admin_view_cusorder', 'admin_viewbrand', 'admin_edit_profile','admin_add_brand','admin_view_category','admin_add_category','admin_view_allorder','admin_view_receipt','load_recent_orders','admin_view_staff',
        'admin_add_newstaff','superadmin_editstaff','admin_reply_inquiry','admin_view_inquiries','admin_view_reviews','admin_edit_category','admin_edit_brand'
    ];
    if (!in_array($page, $allowed_pages)) {
        $page = 'admin_main_page';
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Dashboard</title>
        <link rel="stylesheet" href="admin_layout.css">
        <link rel="stylesheet" href="admin_view_products.css"> 
        <link rel="stylesheet" href="admin_view_customer.css">
        <link rel="stylesheet" href="admin_viewbrand.css">
        <script src="https://unpkg.com/lucide@latest"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="admin_layout.js" defer></script>
    </head>

    <body> 
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i data-lucide="package"></i>
                    <span>ADMIN PANEL</span>
                </div>
                <button class="sidebar-toggle">
                    <i data-lucide="chevron-left"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <a href="admin_layout.php?page=admin_main_page" class="nav-item"><i data-lucide="layout-dashboard"></i><span>Dashboard</span></a>
                <div class="nav-item has-submenu">
                    <i data-lucide="layers"></i><span>Catalog</span>
                    <i data-lucide="chevron-right" class="submenu-icon"></i>
                    <div class="submenu">
                        <a href="admin_layout.php?page=admin_view_products" class="submenu-item">Product</a>
                        <a href="admin_layout.php?page=admin_viewbrand" class="submenu-item">Brand</a>
                        <a href="admin_layout.php?page=admin_view_category" class="submenu-item">Category</a>

                    </div>
                </div>
                <a href="admin_layout.php?page=admin_view_customer" class="nav-item"><i data-lucide="users"></i><span>Customers</span></a>
                <a href="admin_layout.php?page=admin_view_allorder" class="nav-item"><i data-lucide="shopping-cart"></i><span>Orders</span></a>
                <a href="admin_layout.php?page=admin_view_staff" class="nav-item"><i data-lucide="users"></i><span>Staff</span></a>
                <a href="admin_layout.php?page=admin_view_inquiries" class="nav-item"><i data-lucide="star"></i><span>Product Review</span></a>
                <a href="admin_layout.php?page=admin_view_reviews" class="nav-item"><i data-lucide="truck"></i><span>DeliveryReview</span></a>
            </nav>

            <div class="sidebar-footer">
                <a href="admin_logout.php" class="nav-item"><i data-lucide="log-out"></i><span>Log Out</span></a>
            </div>
        </aside>

        <main class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <h1>HI,ADMIN <?= htmlspecialchars($admin['Admin_Username']) ?>!, WELCOME BACK</h1>
                <div class="header-actions">
    <div class="notifications">
    <i data-lucide="bell"></i>
    <span id="notifCount" class="notif-count" style="display: none;"></span>

    <div id="notificationDropdown" class="dropdown-menu" style="display: none;">
        <ul id="notificationList">
        <li>No new orders</li>
        </ul>
    </div>
    </div>


                    <div class="user-profile" id="userProfile">
                        <div class="avatar" id="adminIcon">
                            <img src="<?= $profileImg ?>" alt="Admin" class="avatar-img" />
                        </div>
                        <div class="dropdown-menu" id="profileDropdown">
                            <a href="admin_layout.php?page=admin_main_page"><i data-lucide="layout-dashboard"></i> Dashboard</a>
                            <a href="admin_layout.php?page=admin_edit_profile"><i data-lucide="settings"></i> Edit Profile</a>
                            <a href="admin_logout.php"><i data-lucide="log-out"></i> Log Out</a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dynamic Page Load -->
            <?php include $page . ".php"; ?>
        </main>
    </div>
    </body>
    </html>
