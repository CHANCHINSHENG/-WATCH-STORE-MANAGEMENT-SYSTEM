<?php
session_start();
require_once 'db.php';

$product_added = null;
$stock_error = null; 

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
            $sql_stock = "SELECT Product_Stock_Quantity, ProductName FROM `05_product` WHERE ProductID = ?";
            $stmt_stock = $conn->prepare($sql_stock);
            $stmt_stock->bind_param("i", $product_id);
            $stmt_stock->execute();
            $result_stock = $stmt_stock->get_result();
            $product_stock_data = $result_stock->fetch_assoc();
            $available_stock = $product_stock_data['Product_Stock_Quantity'];
            $productNameForError = $product_stock_data['ProductName'];

            $sql_cart = "SELECT CartID FROM `11_cart` WHERE CustomerID = ?";
            $stmt_cart = $conn->prepare($sql_cart);
            $stmt_cart->bind_param("i", $customerID);
            $stmt_cart->execute();
            $result_cart = $stmt_cart->get_result();
            $cartID = null;

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

            $quantity_in_cart = 0;
            if ($cartID) 
            {
                $sql_check = "SELECT Quantity FROM `12_cart_item` WHERE CartID = ? AND ProductID = ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("ii", $cartID, $product_id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                if ($result_check->num_rows > 0) 
                {
                    $row = $result_check->fetch_assoc();
                    $quantity_in_cart = $row['Quantity'];
                }
            }
            
            if ($quantity_in_cart >= 10) 
            {
                $stock_error = "Oops! " . htmlspecialchars($productNameForError) . "<br><br>This watch is limited to 10 pieces per customer. You can't add any more!";
            }
            else if (($quantity_in_cart + 1) > $available_stock) 
            {
                $stock_error = "Oops! " . htmlspecialchars($productNameForError) . "<br><br>There isn't enough stock to add that quantity.";
            } 
            else 
            {
                if ($quantity_in_cart > 0)
                {
                    $quantity = $quantity_in_cart + 1;
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

                $product_added = true; 
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

if (isset($_SESSION['customer_id']) && isset($_GET['id'])) 
{
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

$stmt = $conn->prepare("SELECT * FROM `05_product` WHERE ProductID = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) 
{
    die("❌ Product not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['ProductName']); ?> - TIGO</title>
    <link rel="stylesheet" href="product_details.css">
    
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
            align-items: center; 
            justify-content: center; 
        }

        .modal-content 
        {
            background: #1e1e1e; 
            padding: 30px; 
            border-radius: 18px;
            width: 90%;
            max-width: 450px; 
            position: relative;
            color: #f0f0f0; 
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.7); 
            text-align: center; 
            border: 1px solid #333;
        }

        .modal .close 
        {
            position: absolute;
            top: 15px; 
            right: 20px;
            font-size: 2rem; 
            font-weight: bold;
            color: #777; 
            background: transparent; 
            border: none; 
            cursor: pointer;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .modal .close:hover 
        {
            color: #ff9800;
            transform: scale(1.1); 
        }
        
        .modal-content h2 
        {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #ff5252;
        }
        
        .modal-content .modal-text 
        {
            font-size: 1.1rem; 
            color: #ccc; 
            line-height: 1.6;
            margin-bottom: 25px;
            text-align: center; 
        }

        .modal-content .ok-btn 
        {
            background: #333;
            color: #fff;
            font-size: 1rem;
            padding: 12px 50px;
            border: 1px solid #555;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .modal-content .ok-btn:hover 
        {
             background-color: #444;
             border-color: #777;
        }

    </style>
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
            const images = 
            [
                <?= json_encode($product['Product_Image']); ?>,
                <?= json_encode($product['Product_Image2']); ?>,
                <?= json_encode($product['Product_Image3']); ?>
            ].filter(img => img);
            let currentIndex = 0;
            function showImage(index) 
            {
                currentIndex = index;
                document.getElementById("mainImage").src = "admin_addproduct_include/" + images[currentIndex];
            }
            function prevImage() 
            {
                currentIndex = (currentIndex - 1 + images.length) % images.length;
                showImage(currentIndex);
            }
            function nextImage() 
            {
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
                <button type="submit" class="add-to-cart-btn" <?= $product['Product_Stock_Quantity'] == 0 ? 'disabled' : '' ?>>
                    <?= $product['Product_Stock_Quantity'] == 0 ? 'Out of Stock' : 'add to cart' ?>
                </button>
            </form>
        </div>
    </div>

    <?php if (isset($stock_error)): ?>
    <div id="errorModal" class="modal" style="display: flex;">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('errorModal').style.display='none'">&times;</span>
            <h2>Oops! Something went wrong!</h2>
            <div class="modal-text">
                <p style="font-size: 2.5rem; margin: 10px 0;">😟</p>
                <p><?= $stock_error; ?></p>
            </div>
            <button class="ok-btn" onclick="document.getElementById('errorModal').style.display='none'">OK</button>
        </div>
    </div>
     <script>
        var errorModal = document.getElementById("errorModal");
        var closeBtn = errorModal.querySelector(".close");
        window.onclick = function (event) {
            if (event.target == errorModal) {
                errorModal.style.display = "none";
            }
        }
    </script>
    <?php endif; ?>

    <?php if (isset($product_added) && $product_added): ?>
    <script>
        alert("Successfully added to cart!");
    </script>
    <?php endif; ?>

</body>
</html>
