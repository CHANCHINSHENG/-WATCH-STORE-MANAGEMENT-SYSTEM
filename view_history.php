<?php
session_start();
require_once 'db.php';

// 验证用户是否已登录
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}

$CustomerID = $_SESSION['customer_id'];

// 获取浏览记录
$sql = "SELECT vh.ViewTime, p.ProductID, p.ProductName, p.Product_Price, p.Product_Image
        FROM `15_view_history` vh
        JOIN `05_product` p ON vh.ProductID = p.ProductID
        WHERE vh.CustomerID = ?
        ORDER BY vh.ViewTime DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $CustomerID);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Viewing History</title>
  
  <style>
    body {
        background-color: #121212;
        font-family: Arial, sans-serif;
        color: white;
        margin: 0;
        padding: 20px;
    }
    .history-container {
        max-width: 1100px;
        margin: 40px auto;
        padding: 30px;
        background-color: #1e1e1e;
        border-radius: 12px;
        box-shadow: 0 0 10px rgba(255, 255, 255, 0.05);
    }
    .history-title {
        font-size: 30px;
        margin-bottom: 30px;
        text-align: center;
        color: #f4d03f;
    }
    .history-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 25px;
    }
    .history-card {
        background-color: #2c2c2c;
        padding: 15px;
        border-radius: 10px;
        transition: 0.3s;
        text-align: center;
    }
    .history-card:hover {
        transform: scale(1.03);
        background-color: #333;
    }
    .history-card img {
        width: 100%;
        height: 180px;
        object-fit: contain;
        border-radius: 6px;
        margin-bottom: 10px;
        background-color: white;
    }
    .history-card h4 {
        margin: 10px 0 5px;
        font-size: 18px;
    }
    .history-card p {
        margin: 0;
        color: #ccc;
    }
    .view-time {
        font-size: 12px;
        color: #999;
        margin-top: 8px;
    }
    .no-history {
        text-align: center;
        padding: 40px 20px;
        font-size: 18px;
        color: #aaa;
    }
    
    .top-nav {
    display: flex;
    justify-content: flex-start;
    gap: 15px;
    margin-bottom: 10px;    
    }

    .top-nav button {
        background-color: #333;
        color: #fff;
        border: 1px solid #555;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.3s ease;
    }
    .top-nav button:hover {
    background-color: #444;
    }
  </style>
</head>

<body>
    <nav class="top-nav">
        <button onclick="window.location.href='customermainpage.php'">🏠 Home</button>
        <button onclick="history.back()">🔙 Back</button>
    </nav>
    <div class="history-container">
        <div class="history-title">Your Viewing History</div>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="history-grid">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="history-card">
                        <a href="product_details.php?id=<?= htmlspecialchars($row['ProductID']) ?>" style="text-decoration: none; color: inherit;">
                            <img src="admin_addproduct_include/<?= htmlspecialchars($row['Product_Image']) ?>" alt="<?= htmlspecialchars($row['ProductName']) ?>">
                            <h4><?= htmlspecialchars($row['ProductName']) ?></h4>
                            <p>RM <?= number_format($row['Product_Price'], 2) ?></p>
                            <div class="view-time">Viewed at: <?= htmlspecialchars($row['ViewTime']) ?></div>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-history">You haven't viewed any products yet.</div>
        <?php endif; ?>
    </div>
</body>
</html>
