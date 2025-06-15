<?php
session_start();
require_once 'db.php';

// 这边开始 add to cart的function
$product_added = null; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']))
{
    $product_id_from_post = (int)$_POST['product_id']; 
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
            $stmt_check->bind_param("ii", $cartID, $product_id_from_post);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0)
            {
                $row = $result_check->fetch_assoc();
                $quantity = $row['Quantity'] + 1;
                $sql_update = "UPDATE `12_cart_item` SET Quantity = ? WHERE CartID = ? AND ProductID = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("iii", $quantity, $cartID, $product_id_from_post);
                $stmt_update->execute();
            }
            else
            {
                $quantity = 1;
                $sql_insert = "INSERT INTO `12_cart_item` (CartID, ProductID, Quantity) VALUES (?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("iii", $cartID, $product_id_from_post, $quantity);
                $stmt_insert->execute();
            }

            $sql_product_info = "SELECT ProductName, Product_Price, Product_Image FROM `05_product` WHERE ProductID = ?";
            $stmt_product_info = $conn->prepare($sql_product_info);
            $stmt_product_info->bind_param("i", $product_id_from_post);
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
// 这边结束 add to cart 的function

if (isset($_SESSION['customer_id']) && isset($_GET['id'])) {
    $customerID = $_SESSION['customer_id'];
    $productID = intval($_GET['id']);

    $stmt = $conn->prepare("INSERT INTO `15_view_history` (CustomerID, ProductID, ViewTime) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $customerID, $productID);
    $stmt->execute();
}


if (!isset($_GET['id']) || empty($_GET['id'])) 
{
    die("❌ Product ID is missing.");
}

$product_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM 05_product WHERE ProductID = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    die("❌ Product not found.");
}

// Fetch recommended products
$like_products = [];
$customer_id = $_SESSION['customer_id'] ?? null;

if ($customer_id) {
    $customer_id = (int)$customer_id;

    // 查询最近浏览的产品（最多6个）
    $like_query = "
        SELECT p.*
        FROM (
            SELECT ProductID, MAX(Viewed_At) AS LastViewed
            FROM `15_view_history`
            WHERE CustomerID = $customer_id
            GROUP BY ProductID
        ) AS vh
        JOIN `05_product` p ON vh.ProductID = p.ProductID
        ORDER BY vh.LastViewed DESC
        LIMIT 3
    ";

    $like_result = mysqli_query($conn, $like_query);

    if ($like_result && mysqli_num_rows($like_result) > 0) {
        while ($row = mysqli_fetch_assoc($like_result)) {
            $like_products[] = $row;
        }
    }
}

// 如果登录但没浏览记录，或查询失败，则随机推荐
if (empty($like_products)) {
    $fallback_query = "SELECT * FROM `05_product` ORDER BY RAND() LIMIT 6";
    $fallback_result = mysqli_query($conn, $fallback_query);
    if ($fallback_result) {
        while ($row = mysqli_fetch_assoc($fallback_result)) {
            $like_products[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['ProductName']); ?> - TIGO</title>
    <link rel="stylesheet" href="product_details.css">
</head>
<body>
    <nav class="top-nav">
        <button onclick="window.location.href='customermainpage.php'">🏠 Home</button>
        <button onclick="window.location.href='customer_products.php'">🔙 Back</button>
    </nav>

    <div class="product-detail-container">
        <div class="product-gallery">
        <div class="main-image-container">
            <button class="arrow left" onclick="prevImage()">&#10094;</button>
            <img id="mainImage" src="admin_addproduct_include/<?= htmlspecialchars($product['Product_Image']); ?>" alt="Main Image">
            <button class="arrow right" onclick="nextImage()">&#10095;</button>
        </div>

        <div class="thumbnail-container">
            <?php if (!empty($product['Product_Image'])): ?>
                <img class="thumbnail" src="admin_addproduct_include/<?= htmlspecialchars($product['Product_Image']); ?>" onclick="showImage(0)">
            <?php endif; ?>
            <?php if (!empty($product['Product_Image2'])): ?>
                <img class="thumbnail" src="admin_addproduct_include/<?= htmlspecialchars($product['Product_Image2']); ?>" onclick="showImage(1)">
            <?php endif; ?>
            <?php if (!empty($product['Product_Image3'])): ?>
                <img class="thumbnail" src="admin_addproduct_include/<?= htmlspecialchars($product['Product_Image3']); ?>" onclick="showImage(2)">
            <?php endif; ?>
        </div>
    </div>
    <script>
    const images = [
        <?= json_encode($product['Product_Image']); ?>,
        <?= json_encode($product['Product_Image2']); ?>,
        <?= json_encode($product['Product_Image3']); ?>
    ].filter(img => img);

    let currentIndex = 0;

    function showImage(index) {
    currentIndex = index;
    document.getElementById("mainImage").src = "admin_addproduct_include/" + images[currentIndex];
}


    function prevImage() {
        currentIndex = (currentIndex - 1 + images.length) % images.length;
        showImage(currentIndex);
    }

    function nextImage() {
        currentIndex = (currentIndex + 1) % images.length;
        showImage(currentIndex);
    }
</script>

        <div class="product-info">
            <h1><?= htmlspecialchars($product['ProductName']); ?></h1>
            <p class="product-price">RM <?= number_format($product['Product_Price'], 2); ?></p>
            <p class="product-description"><?= nl2br(htmlspecialchars($product['Product_Description'])); ?></p>
            <p class="product-stock">Stock: <?= $product['Product_Stock_Quantity']; ?></p>

            <form action="" method="post">
                <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['ProductID']); ?>">
                <button type="submit" class="add-to-cart-btn">add to cart</button>
            </form>
        </div>
    </div>
    <?php if (!empty($like_products)): ?>
    <div class="recommended-section">
        <h2>Recommended For You</h2>
        <div class="recommended-products">
            <?php foreach ($like_products as $rec): ?>
                <div class="recommended-card">
                    <a href="customer_product.php?id=<?= $rec['ProductID']; ?>">
                        <img src="admin_addproduct_include/<?= htmlspecialchars($rec['Product_Image']); ?>" alt="<?= htmlspecialchars($rec['ProductName']); ?>">
                        <h3><?= htmlspecialchars($rec['ProductName']); ?></h3>
                        <p>RM <?= number_format($rec['Product_Price'], 2); ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <style>
    .recommended-section {
        margin-top: 50px;
        padding: 30px;
        background-color: #f4f4f4;
        text-align: center;
    }

    .recommended-section h2 {
        font-size: 28px;
        margin-bottom: 20px;
        color: #333;
    }

    .recommended-products {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 20px;
    }

    .recommended-card {
        background-color: white;
        border: 1px solid #ddd;
        border-radius: 10px;
        padding: 15px;
        width: 200px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: transform 0.2s ease;
    }

    .recommended-card:hover {
        transform: translateY(-5px);
    }

    .recommended-card img {
        width: 100%;
        height: 180px;
        object-fit: cover;
        border-radius: 5px;
    }

    .recommended-card h3 {
        font-size: 18px;
        margin: 10px 0 5px;
        color: #333;
    }

    .recommended-card p {
        font-size: 16px;
        color: #666;
    }
    </style>

    <?php if (isset($product_added)): ?>
    <div id="myModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Product added to cart!🛒</h2>
            </div>
    </div>

    <script>
        var modal = document.getElementById("myModal");
        var span = document.getElementsByClassName("close")[0];

        if (modal) { // 检查 modal 是否存在
            modal.style.display = "flex"; // 修改为 flex 使其居中

            span.onclick = function () {
                modal.style.display = "none";
            }
            window.onclick = function (event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
        }
    </script>
<?php endif; ?>
</body>
</html>