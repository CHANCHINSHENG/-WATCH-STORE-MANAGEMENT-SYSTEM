<?php
// payment_info.php

// 1. 定义接受的支付方式数据
// 实际应用中，这些数据也可以来自数据库或配置
$payment_methods = [
    [
        'name' => '信用卡/借记卡',
        'types' => 'Visa, MasterCard, American Express',
        'description' => '我们接受所有主流信用卡和借记卡支付。您的交易将通过安全的支付网关进行处理。',
        'icon' => 'card-icon.png' // 假设你有支付方式图标，实际路径需要替换
    ],
    [
        'name' => '在线银行转账 (FPX)',
        'types' => 'Maybank2u, CIMB Clicks, Public Bank, Hong Leong Bank 等',
        'description' => '通过马来西亚主要银行的在线银行服务直接支付。这是一个安全且即时的转账方式。',
        'icon' => 'bank-icon.png'
    ],
    [
        'name' => '电子钱包',
        'types' => 'Touch \'n Go eWallet, GrabPay, Boost',
        'description' => '方便快捷的电子钱包支付，扫描二维码或通过应用内支付。',
        'icon' => 'ewallet-icon.png'
    ],
    // 如果有其他支付方式，可以在这里添加
    // [
    //     'name' => '货到付款 (COD)',
    //     'types' => '现金',
    //     'description' => '仅限特定区域和商品，订单确认后将有专人联系您确认。',
    //     'icon' => 'cod-icon.png'
    // ]
];

?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Info</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            padding-top: 50px;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #0056b3;
            margin-bottom: 20px;
        }
        .payment-method {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
            padding: 15px;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            background-color: #fefefe;
        }
        .payment-method img {
            width: 50px;
            height: 50px;
            margin-right: 20px;
            object-fit: contain;
        }
        .payment-method-details h3 {
            margin-top: 0;
            color: #007bff;
        }
        .note {
            background-color: #eaf6ff;
            border-left: 5px solid #007bff;
            padding: 15px;
            border-radius: 5px;
            margin-top: 30px;
        }
        /* 如果你没有实际图标，可以隐藏图标区域 */
        .payment-method img {
            display: none; /* 或者设置为 block 并提供实际路径 */
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Payment Information</h1>
        <p>我们提供多种安全便捷的支付方式，让您的购物体验更加顺畅。</p>

        <h2 class="mt-4">Accepted Payment Methods</h2>
        <?php foreach ($payment_methods as $method): ?>
            <div class="payment-method">
                <?php if (isset($method['icon']) && file_exists($method['icon'])): ?>
                    <img src="<?php echo htmlspecialchars($method['icon']); ?>" alt="<?php echo htmlspecialchars($method['name']); ?> Icon">
                <?php endif; ?>
                <div class="payment-method-details">
                    <h3><?php echo htmlspecialchars($method['name']); ?></h3>
                    <p><strong>支持类型：</strong> <?php echo htmlspecialchars($method['types']); ?></p>
                    <p><?php echo htmlspecialchars($method['description']); ?></p>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="note mt-5">
            <h3>Important Notes:</h3>
            <ul>
                <li>所有交易均通过加密连接进行，以确保您的支付信息安全。</li>
                <li>支付完成后，您将收到一封订单确认邮件。</li>
                <li>如果您的支付遇到任何问题，请及时联系我们的客服团队寻求帮助。</li>
                <li>请确保您的支付信息准确无误，以避免订单处理延迟。</li>
            </ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>