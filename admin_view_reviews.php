<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$reviews = [];
$error_message = '';

$sql = "SELECT r.ReviewID, r.Rating, r.Comment, r.ReviewDate, c.Cust_Username
        FROM 18_reviews r
        JOIN 07_order o ON r.OrderID = o.OrderID
        JOIN 02_customer c ON o.CustomerID = c.CustomerID
        ORDER BY r.ReviewDate DESC";

$result = $pdo->query($sql);
if ($result) {
    $reviews = $result->fetchAll(PDO::FETCH_ASSOC);
} else {
    $error_message = "Error fetching reviews: " . $pdo->errorInfo()[2];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Customer Reviews</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a1a, #4a148c, #311b92);
            color: #333;
        }
        .page-wrapper {
            min-height: 100vh;
            padding: 2rem;
        }
        .content-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem;
            max-width: 1000px;
            margin: auto;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .header h2 {
            font-size: 2rem;
            font-weight: 600;
            color: #2c3e50;
        }
        .reviews-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }
        .reviews-table th,
        .reviews-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .reviews-table th {
            background-color: #4a148c;
            color: white;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        .reviews-table tr:hover {
            background-color: rgba(74, 20, 140, 0.05);
        }
        .no-reviews {
            text-align: center;
            color: #6b7280;
            font-size: 1.1rem;
            padding: 2rem;
        }
        .error-message {
            color: #b91c1c;
            background: #fee2e2;
            border: 1px solid #fecaca;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .content-container {
                padding: 1.5rem;
            }
            .reviews-table th,
            .reviews-table td {
                padding: 0.75rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="content-container">
        <div class="header">
            <h2>🌟 Customer Reviews</h2>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (empty($reviews) && empty($error_message)): ?>
            <div class="no-reviews">No reviews found.</div>
        <?php else: ?>
            <table class="reviews-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Review Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td><?= htmlspecialchars($review['Cust_Username']); ?></td>
                            <td><?= htmlspecialchars($review['Rating']); ?> / 5</td>
                            <td><?= nl2br(htmlspecialchars($review['Comment'])); ?></td>
                            <td><?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($review['ReviewDate']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
