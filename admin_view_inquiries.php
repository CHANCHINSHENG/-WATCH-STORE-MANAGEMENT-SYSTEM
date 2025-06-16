
<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$inquiries = [];
$error_message = '';

$sql = "SELECT id, customer_name, customer_email, message_subject, submission_date, replied_at 
        FROM 14_customer_inquiries 
        ORDER BY submission_date DESC";

$result = $pdo->query($sql);
if ($result) {
    $inquiries = $result->fetchAll(PDO::FETCH_ASSOC);
} else {
    $error_message = "Error fetching inquiries: " . $pdo->errorInfo()[2];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Customer Inquiries</title>
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
            max-width: 1200px;
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
        .inquiries-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }
        .inquiries-table th,
        .inquiries-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .inquiries-table th {
            background-color: #4a148c;
            color: white;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        .inquiries-table tr:hover {
            background-color: rgba(74, 20, 140, 0.05);
        }
        .status-replied {
            background: rgba(22, 163, 74, 0.1);
            color: #16a34a;
            padding: 6px 12px;
            border-radius: 20px;
            display: inline-block;
            font-weight: 500;
            font-size: 0.875rem;
        }
        .status-pending {
            background: rgba(220, 38, 38, 0.1);
            color: #dc2626;
            padding: 6px 12px;
            border-radius: 20px;
            display: inline-block;
            font-weight: 500;
            font-size: 0.875rem;
        }
        .action-link a {
            color: #4a148c;
            text-decoration: none;
            font-weight: 500;
        }
        .action-link a:hover {
            text-decoration: underline;
        }
        .error-message {
            color: #b91c1c;
            background: #fee2e2;
            border: 1px solid #fecaca;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .no-inquiries {
            text-align: center;
            color: #6b7280;
            font-size: 1.1rem;
            padding: 2rem;
        }

        @media (max-width: 768px) {
            .content-container {
                padding: 1.5rem;
            }
            .inquiries-table th,
            .inquiries-table td {
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
            <h2>📨 Customer Inquiries</h2>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (empty($inquiries) && empty($error_message)): ?>
            <div class="no-inquiries">No customer inquiries found.</div>
        <?php else: ?>
            <table class="inquiries-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Submitted At</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inquiries as $inq): ?>
                        <tr>
                            <td><?= htmlspecialchars($inq['id']); ?></td>
                            <td><?= htmlspecialchars($inq['customer_name']); ?></td>
                            <td><?= htmlspecialchars($inq['customer_email']); ?></td>
                            <td><?= htmlspecialchars($inq['message_subject']); ?></td>
                            <td><?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($inq['submission_date']))); ?></td>
                            <td>
                                <?php if (!empty($inq['replied_at'])): ?>
                                    <span class="status-replied">✔️ Replied</span>
                                <?php else: ?>
                                    <span class="status-pending">💬 Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="action-link">
                                <a href="admin_layout.php?page=admin_reply_inquiry&id=<?= htmlspecialchars($inq['id']); ?>">View & Reply</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
