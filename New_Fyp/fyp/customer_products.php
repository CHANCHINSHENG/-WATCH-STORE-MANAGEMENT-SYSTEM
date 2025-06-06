<?php
session_start();
include 'db.php';

$product_added = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) 
{
    $product_id = (int)$_POST['product_id'];
    $customerID = $_SESSION['customer_id'] ?? null;
    
    if ($customerID) 
    {
        $sql_check_customer = "SELECT CustomerID FROM `02_customer` WHERE CustomerID = ?";
        $stmt_check_customer = $conn->prepare($sql_check_customer);
        $stmt_check_customer->bind_param("i", $customerID);
        $stmt_check_customer->execute();
        $result_check_customer = $stmt_check_customer->get_result();

        if ($result_check_customer->num_rows > 0) 
        {
            $sql_cart = "SELECT CartID FROM `11_cart` WHERE CustomerID = ?";
            $stmt_cart = $conn->prepare($sql_cart);
            $stmt_cart->bind_param("i", $customerID);
            $stmt_cart->execute();
            $result_cart = $stmt_cart->get_result();

            if ($result_cart->num_rows > 0) 
            {
                $cart_row = $result_cart->fetch_assoc();
                $cartID = $cart_row['CartID'];
            } 
            else 
            {
                $sql_create_cart = "INSERT INTO `11_cart` (CustomerID) VALUES (?)";
                $stmt_create_cart = $conn->prepare($sql_create_cart);
                $stmt_create_cart->bind_param("i", $customerID);
                $stmt_create_cart->execute();
                $cartID = $stmt_create_cart->insert_id;
            }

            $sql_check = "SELECT Quantity FROM `12_cart_item` WHERE CartID = ? AND ProductID = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("ii", $cartID, $product_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) 
            {
                $row = $result_check->fetch_assoc();
                $quantity = $row['Quantity'] + 1; 
                $sql_update = "UPDATE `12_cart_item` SET Quantity = ? WHERE CartID = ? AND ProductID = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("iii", $quantity, $cartID, $product_id);
                $stmt_update->execute();
            } 
            else 
            {
                $quantity = 1; 
                $sql_insert = "INSERT INTO `12_cart_item` (CartID, ProductID, Quantity) VALUES (?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("iii", $cartID, $product_id, $quantity);
                $stmt_insert->execute();
            }

            $sql_product_info = "SELECT ProductName, Product_Price, Product_Image FROM `05_product` WHERE ProductID = ?";
            $stmt_product_info = $conn->prepare($sql_product_info);
            $stmt_product_info->bind_param("i", $product_id);
            $stmt_product_info->execute();
            $result_product_info = $stmt_product_info->get_result();

            if ($result_product_info->num_rows > 0) 
            {
                $product_details = $result_product_info->fetch_assoc();
                $_SESSION['product_added'] = $product_details;
                $product_added = $product_details;
            }
        } 
        else 
        {
            header("Location: customer_login.php");
            exit();
        }
    } 
    else 
    {
        header("Location: customer_login.php");
        exit();
    }
}

$brand_id = $_GET['brand_id'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$search_query = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? '';

$brand_name = $_GET['brand'] ?? '';
if (!empty($brand_name)) 
{
    $stmt_brand = $conn->prepare("SELECT BrandID FROM `03_brand` WHERE BrandName = ?");
    $stmt_brand->bind_param("s", $brand_name);
    $stmt_brand->execute();
    $result_brand = $stmt_brand->get_result();

    if ($row_brand = $result_brand->fetch_assoc()) 
    {
        $brand_id = $row_brand['BrandID']; 
    }
}

$category_name = $_GET['category'] ?? '';
if (!empty($category_name)) 
{
    $stmt_category = $conn->prepare("SELECT CategoryID FROM `04_category` WHERE CategoryName = ?");
    $stmt_category->bind_param("s", $category_name);
    $stmt_category->execute();
    $result_category = $stmt_category->get_result();
    if ($row_category = $result_category->fetch_assoc()) 
    {
        $category_id = $row_category['CategoryID'];
    }
}

$brands = $conn->query("SELECT * FROM `03_brand`");
$categories = $conn->query("SELECT * FROM `04_category`");

$sql = "SELECT * FROM `05_product` WHERE Product_Status = 'Available'";
$params = [];
$types = '';


if ($brand_id !== '') 
{
    $sql .= " AND BrandID = ?";
    $params[] = $brand_id;
    $types .= 'i';
}

if ($category_id !== '') 
{
    $sql .= " AND CategoryID = ?";
    $params[] = $category_id;
    $types .= 'i';
}

if (!empty($search_query)) 
{
    $sql .= " AND ProductName LIKE ?";
    $search_param_val = "%$search_query%"; 
    $params[] = $search_param_val;
    $types .= 's';
}

switch ($sort) 
{
    case 'price_asc':
        $sql .= " ORDER BY Product_Price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY Product_Price DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY ProductName ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY ProductName DESC";
        break;
    default:
        $sql .= " ORDER BY ProductID DESC";
}

$stmt = $conn->prepare($sql);
if (!empty($params)) 
{
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$total_price = $product_added['Product_Price'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shop - Available Products</title>
    <link rel="stylesheet" href="customer_products.css">
    <link rel="stylesheet" href="add_to_cart.css">

    <style>
    .modal 
    {
        display: none;
        position: fixed; 
        z-index: 1000; 
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto; 
        background-color: rgba(0, 0, 0, 0.7); 
        backdrop-filter: blur(8px); 
        -webkit-backdrop-filter: blur(8px);
        display: flex; 
        justify-content: center;
        align-items: center; 
        padding: 1rem;
    }

    .modal-content 
    {
        background: #2e2e2e; 
        padding: 25px; 
        border-radius: 15px;
        max-width: 450px; 
        width: 100%; 
        position: relative;
        color: #fff; 
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); 
        text-align: center; 
    }

    .modal .close 
    {
        position: absolute;
        top: 15px; 
        right: 15px;
        font-size: 1.8rem; 
        font-weight: bold;
        color: #aaa; 
        background: transparent; 
        border: none; 
        cursor: pointer;
        transition: color 0.3s ease, transform 0.3s ease;
        line-height: 1; 
        padding: 0; 
    }

    .modal .close:hover 
    {
        color: #ffd700; 
        transform: scale(1.1); 
    }

    .modal-content h2 
    {
        color: #ffd700; 
        font-size: 1.75rem; 
        margin-top: 0; 
        margin-bottom: 1.5rem; 
    }

    .modal .product-info 
    {
        margin-bottom: 1.5rem; 
    }

    modal .product-info img
     {
        max-width: 130px; 
        height: auto; 
        object-fit: cover;
        border-radius: 10px; 
        margin: 0 auto 1rem auto; 
        display: block; 
        border: 2px solid #444; 
    }

    .modal .product-info p 
    {
        font-size: 1rem; 
        color: #eee; 
        margin: 0.6rem 0; 
    }

    .modal .product-info p strong 
    {
        color: #bbb; 
    }

    .modal .total-section 
    {
        margin-top: 1rem; 
        margin-bottom: 1.5rem; 
        font-size: 1.25rem; 
        font-weight: bold;
        color: #ffd700;
    }

    .modal .button-container 
    {
        display: flex;
        justify-content: space-around; 
        gap: 1rem; 
    }

    .modal .button-container button 
    {
        flex-grow: 1; 
        padding: 12px 10px; 
        color: white;
        border: none;
        border-radius: 8px; 
        font-size: 0.9rem; 
        font-weight: 600; 
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase; 
        letter-spacing: 0.5px; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.15); 
    }

    .modal .button-container button:first-child 
    {
        background-color: #555; 
    }

    .modal .button-container button:first-child:hover 
    {
        background-color: #666; 
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .modal .button-container button:last-child 
    {
        background: linear-gradient(135deg, #ff69b4, #9c27b0); 
    }

    .modal .button-container button:last-child:hover 
    {
        background: linear-gradient(135deg, #e05aa0, #8c239e); 
        box-shadow: 0 4px 10px rgba(255, 105, 180, 0.3); 
    }

    .filter-form {
        display: flex;
        flex-wrap: wrap; /* Allows items to wrap to the next line on smaller screens */
        gap: 1rem; /* Space between form elements */
        margin-bottom: 2rem; /* Space below the form */
        padding: 1.5rem;
        background-color: #2a2a2a; /* Slightly lighter than product card for differentiation */
        border-radius: 15px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .filter-form select,
    .filter-form input[type="text"] {
        padding: 0.75rem 1rem;
        background-color: #333; /* Dark background for inputs */
        color: #f0f0f0; /* Light text color */
        border: 1px solid #555; /* Subtle border */
        border-radius: 8px;
        font-size: 0.95rem;
        flex-grow: 1; /* Allows input fields to grow and fill available space */
        min-width: 180px; /* Minimum width for select and input fields */
        box-sizing: border-box; /* Ensures padding and border don't add to the width */
    }

    .filter-form select {
        cursor: pointer;
    }

    .filter-form input[type="text"]::placeholder {
        color: #888; /* Lighter placeholder text */
    }

    .filter-form button[type="submit"],
    .filter-form .reset-btn {
        padding: 0.75rem 1.5rem;
        color: #121212; /* Dark text for contrast on bright button */
        background-color: #ffd700; /* Gold color to match headers */
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        font-size: 0.95rem;
        text-decoration: none; /* For the reset link styled as a button */
        display: inline-flex; /* Aligns icon and text if an icon was present */
        align-items: center;
        justify-content: center;
        transition: background-color 0.3s ease, transform 0.2s ease;
        flex-grow: 0; /* Buttons don't grow as much as input fields */
    }

    .filter-form .reset-btn {
        background-color: #555; /* Different color for reset button */
        color: #f0f0f0;
    }

    .filter-form button[type="submit"]:hover {
        background-color: #e6c300; /* Darker gold on hover */
        transform: translateY(-2px);
    }

    .filter-form .reset-btn:hover {
        background-color: #666; /* Darker grey on hover for reset */
        transform: translateY(-2px);
    }

    .filter-form button[type="submit"]:active,
    .filter-form .reset-btn:active {
        transform: translateY(0); /* Click effect */
    }

    /* Responsive adjustments for smaller screens */
    @media (max-width: 768px) {
        .filter-form {
            flex-direction: column; /* Stack elements vertically */
        }

        .filter-form select,
        .filter-form input[type="text"],
        .filter-form button[type="submit"],
        .filter-form .reset-btn {
            width: 100%; /* Make all form elements full width */
            min-width: 0; /* Reset min-width */
        }
    }
    </style>
</head>
<body>

<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="Title main page">
    <div class="container"><a class="navbar-brand d-inline-flex" href="customermainpage.php"><img src="assets/img/Screenshot 2025-03-20 113245.png"></a>

    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item px-2">
            <a class="nav-link fw-bold <?= ($current_page ?? '') == 'customer_products.php' ? 'active' : '' ?>" href="customer_products.php">WATCHES</a>
        </li>
        <li class="nav-item px-2">
            <a class="nav-link fw-bold <?= ($current_page ?? '') == 'contact.php' ? 'active' : '' ?>" href="#contact">CONTACT</a>
        </li>
        <li class="nav-item px-2">
            <a class="nav-link fw-bold <?= ($current_page ?? '') == 'cart.php' ? 'active' : '' ?>" href="cart.php"><img src="img/Cart_icon.png" alt="Cart" style="width:24px; height:24px;"></a>
        </li>
        <li class="nav-item px-2">
            <a class="nav-link fw-bold <?= ($current_page ?? '') == 'customer_login.php' ? 'active' : '' ?>" href="customer_login.php"><img src="img/user_icon.png" alt="login" style="width:24px; height:24px;"></a>
        </li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="product-page-container">
        <div class="product-page-header">
            <h1>TOP PICKS</h1>
            <h3>Check out our most popular watches.</h3>
        </div>

        <form method="get" class="filter-form">
            <select name="brand_id">
                <option value="">All Brands</option>
                <?php while ($b = $brands->fetch_assoc()) : ?>
                    <option value="<?= $b['BrandID'] ?>" <?= $brand_id == $b['BrandID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['BrandName']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <select name="category_id">
                <option value="">All Categories</option>
                <?php while ($c = $categories->fetch_assoc()) : ?>
                    <option value="<?= $c['CategoryID'] ?>" <?= $category_id == $c['CategoryID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['CategoryName']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <select name="sort">
                <option value="">Sort By</option>
                <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Name: A to Z</option>
                <option value="name_desc" <?= $sort == 'name_desc' ? 'selected' : '' ?>>Name: Z to A</option>
            </select>

            <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search_query) ?>">
            <button type="submit">Search</button>
            <a href="customer_products.php" class="reset-btn">Reset</a>
        </form>
        
        <div class="product-grid">  
            <?php while ($row = $result->fetch_assoc()) { ?>
                <div class="product-card">
                    <div class="product-info">
                    <a href="product_details.php?id=<?= $row['ProductID']; ?>" class="product-link">
                        <img src="<?= htmlspecialchars($row['Product_Image']); ?>" alt="<?= htmlspecialchars($row['ProductName']); ?>">
                        <h3><?= htmlspecialchars($row['ProductName']); ?></h3>
                        <p><?= htmlspecialchars($row['Product_Description']); ?></p>
                        <p class="product-price">Price: RM <?= number_format($row['Product_Price'], 2); ?></p>
                        <p>Stock: <?= $row['Product_Stock_Quantity']; ?></p>
                    </a>
                    <form action="" method="post">
                        <input type="hidden" name="product_id" value="<?= $row['ProductID']; ?>">
                        <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                    </form>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<?php if (isset($product_added)): ?>
    <div id="myModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Product Added to Cart!</h2>
            <div class="product-info">
                <img src="admin_addproduct_include/<?= htmlspecialchars($product_added['Product_Image']); ?>" alt="Product Image">
                <p><strong>Product Name:</strong> <?= htmlspecialchars($product_added['ProductName']); ?></p>
                <p><strong>Price:</strong> MYR <?= number_format($product_added['Product_Price'], 2); ?></p>
                <div class="total-section">
                    <span>Total: RM <?= number_format($total_price, 2); ?></span>
                </div>
            </div>
            <div class="button-container">
                <button onclick="window.location.href='customer_products.php'">Continue Shopping</button>
                <button onclick="window.location.href='cart.php'">View Cart</button>
            </div>
        </div>
    </div>

    <script>
        var modal = document.getElementById("myModal");
        var span = document.getElementsByClassName("close")[0];
        modal.style.display = "block";
        span.onclick = function () {
            modal.style.display = "none";
        }
        window.onclick = function (event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
<?php endif; ?>
</body>
</html>