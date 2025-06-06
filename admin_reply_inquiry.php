<?php
require_once 'admin_login_include/config_session.php';
require_once 'admin_login_include/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$inquiry_id = $_GET['id'] ?? null;
$page_errors = [];
$page_success = '';
$inquiry = null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_reply'])) {
    $inquiry_id_post = $_POST['inquiry_id'] ?? '';
    $admin_reply = trim($_POST['admin_reply'] ?? '');

    if (empty($inquiry_id_post) || !filter_var($inquiry_id_post, FILTER_VALIDATE_INT)) {
        $page_errors[] = "Invalid inquiry ID.";
    }
    if (empty($admin_reply)) {
        $page_errors[] = "Reply cannot be empty.";
    }

    if (empty($page_errors)) {
        $stmt = $pdo->prepare("UPDATE 16_customer_inquiries SET admin_reply_content = ?, replied_at = NOW() WHERE id = ?");
        if ($stmt->execute([$admin_reply, $inquiry_id_post])) {
            $page_success = "✅ Reply successfully saved. The customer can now see it.";
        } else {
            $page_errors[] = "❌ Database error: " . $stmt->errorInfo()[2];
        }
    }
}

if ($inquiry_id && filter_var($inquiry_id, FILTER_VALIDATE_INT)) {
    $stmt = $pdo->prepare("SELECT * FROM 16_customer_inquiries WHERE id = ?");
    $stmt->execute([$inquiry_id]);
    $inquiry = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inquiry) {
        $page_errors[] = "No inquiry found for ID $inquiry_id.";
    }
} else if (!$inquiry_id) {
    $page_errors[] = "No inquiry ID provided.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Reply - Inquiry #<?= htmlspecialchars($inquiry_id) ?></title>
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
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 2rem;
            max-width: 900px;
            margin: auto;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .header h2 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #2c3e50;
        }
        .back-link {
            margin-bottom: 1rem;
            display: inline-block;
            color: #4a148c;
            font-weight: 500;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .info-box {
            background: #f3f4f6;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 1px solid #e0e0e0;
        }
        .info-line {
            margin: 0.4rem 0;
        }
        .highlight {
            background: white;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            white-space: pre-wrap;
            margin-top: 0.5rem;
        }
        .form-area label {
            font-weight: 600;
            display: block;
            margin: 1rem 0 0.5rem;
        }
        .form-area textarea {
            width: 100%;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 1rem;
            resize: vertical;
        }
        .btn {
            background: #4a148c;
            color: white;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            margin-top: 1rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .btn:hover {
            background: #311b92;
        }
        .success, .error {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        @media (max-width: 768px) {
            .content-container {
                padding: 1.25rem;
            }
        }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="content-container">
        <a class="back-link" href="admin_layout.php?page=admin_view_inquiries">← Back to Inquiry List</a>
        <div class="header">
            <h2>📩 Admin Reply - Inquiry #<?= htmlspecialchars($inquiry_id) ?></h2>
        </div>

        <?php if (!empty($page_errors)): ?>
            <div class="error">
                <?php foreach ($page_errors as $e): ?>
                    <p>⚠️ <?= htmlspecialchars($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($page_success): ?>
            <div class="success">
                <?= htmlspecialchars($page_success) ?>
            </div>
        <?php endif; ?>

        <?php if ($inquiry): ?>
            <div class="info-box">
                <div class="info-line"><strong>Name:</strong> <?= htmlspecialchars($inquiry['customer_name']) ?></div>
                <div class="info-line"><strong>Email:</strong> <?= htmlspecialchars($inquiry['customer_email']) ?></div>
                <div class="info-line"><strong>Subject:</strong> <?= htmlspecialchars($inquiry['message_subject']) ?></div>
                <div class="info-line"><strong>Submitted:</strong> <?= htmlspecialchars($inquiry['submission_date']) ?></div>
                <div class="info-line"><strong>Message:</strong></div>
                <div class="highlight"><?= nl2br(htmlspecialchars($inquiry['message_content'])) ?></div>
            </div>

            <?php if (!empty($inquiry['admin_reply_content'])): ?>
                <div class="info-box">
                    <strong>Previous Reply (<?= htmlspecialchars($inquiry['replied_at']) ?>):</strong>
                    <div class="highlight"><?= nl2br(htmlspecialchars($inquiry['admin_reply_content'])) ?></div>
                </div>
            <?php endif; ?>

            <form method="post" class="form-area">
                <input type="hidden" name="inquiry_id" value="<?= htmlspecialchars($inquiry_id) ?>">
                <label for="admin_reply">Reply Message:</label>
                <textarea id="admin_reply" name="admin_reply" rows="7" required><?= htmlspecialchars($inquiry['admin_reply_content'] ?? '') ?></textarea>
                <button type="submit" name="submit_reply" class="btn">Submit Reply</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
