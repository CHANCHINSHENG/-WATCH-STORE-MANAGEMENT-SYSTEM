<?php

$shipping_data = [
    'Johor' => [
        'fee' => 10.00,
        'delivery_days' => '2 -- 3 '
    ],
    'Kedah' => [
        'fee' => 12.00,
        'delivery_days' => '3 -- 5 '
    ],
    'Kelantan' => [
        'fee' => 12.00,
        'delivery_days' => '3 -- 4  '
    ],
    'Melaka (Malacca)' => [
        'fee' => 8.00,
        'delivery_days' => '2 -- 3 '
    ],
    'Negeri Sembilan' => [
        'fee' => 8.00,
        'delivery_days' => '2 -- 3 '
    ],
    'Pahang' => [
        'fee' => 10.00,
        'delivery_days' => '3 -- 5 '
    ],
    'Perak' => [
        'fee' => 10.00,
        'delivery_days' => '2 -- 4 '
    ],
    'Perlis' => [
        'fee' => 12.00,
        'delivery_days' => '3 -- 5 '
    ],
    'Pulau Pinang' => [
        'fee' => 8.00,
        'delivery_days' => '2 -- 4 '
    ],
    'Sabah' => [
        'fee' => 20.00, 
        'delivery_days' => '5 -- 7 '
    ],
    'Sarawak' => [
        'fee' => 20.00,
        'delivery_days' => '5 -- 7'
    ],
    'Selangor' => [
        'fee' => 5.00,
        'delivery_days' => '1 -- 2 '
    ],
    'Terengganu' => [
        'fee' => 12.00,
        'delivery_days' => '3 -- 5'
    ],
    'Wilayah Persekutuan Kuala Lumpur' => [
        'fee' => 00.00,
        'delivery_days' => '1 -- 2 '
    ],
    'Wilayah Persekutuan Putrajaya' => [
        'fee' => 5.00,
        'delivery_days' => '1 -- 2 '
    ]
];

?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Info - Standard Delivery (Malaysia)</title>
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
        .table th, .table td {
            vertical-align: middle;
        }
        .table thead th {
            background-color: #e9ecef;
        }
        .note {
            background-color: #eaf6ff;
            border-left: 5px solid #007bff;
            padding: 15px;
            border-radius: 5px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Shipping Info - Standard Delivery (Malaysia)</h1>
        <p>Below are the standard delivery cost details and estimated delivery date for each state in Malaysia.</p>

        <h2 class="mt-4">Shipping Fee Details & Delivery Dates</h2>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th scope="col">State / Federal Territory</th>
                        <th scope="col">Shipping Fee (MYR)</th>
                        <th scope="col">Estimated Delivery Dates (Working Date)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($shipping_data as $state => $details) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($state) . '</td>';
                        echo '<td>RM ' . number_format($details['fee'], 2) . '</td>';
                        echo '<td>' . htmlspecialchars($details['delivery_days']) . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="note mt-5">
            <h3>Important Notes :</h3>
            <ul>
                <li>The above shipping costs are for standard delivery service and may be adjusted depending on the weight, size or promotions of the package.</li>
                <li>Estimated delivery dates are working days, excluding weekends and public holidays.</li>
                <li>Actual delivery time may vary due to customs clearance, weather conditions, logistics delays or other force majeure factors.</li>
                <li>Remote areas may require additional time.</li>
                <li>For more detailed shipping calculations or special circumstances, please contact our customer service team.</li>
            </ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>