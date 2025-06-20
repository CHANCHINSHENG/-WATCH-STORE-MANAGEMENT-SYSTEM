<?php
session_start();
if (!isset($_SESSION['customer_id'])) {
    header("Location: customer_login.php");
    exit();
}
require_once 'db.php';

$CustomerID = $_SESSION['customer_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

$order_query = "SELECT * FROM `08_order` WHERE OrderID = ? AND CustomerID = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $CustomerID);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo "Order not found or access denied.";
    exit();
}

// ✅ 載入合法州屬
function loadAllowedStatesFromCSV($csvFile) {
    $states = [];
    if (($handle = fopen($csvFile, "r")) !== false) {
        fgetcsv($handle); // skip header
        while (($data = fgetcsv($handle)) !== false) {
            $state = trim($data[2]);
            if (!empty($state)) {
                $states[] = strtolower($state);
            }
        }
        fclose($handle);
    }
    return array_unique($states);
}

$allowedStates = loadAllowedStatesFromCSV("shipping_rules.csv");
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['Shipping_Name']);
    $address  = trim($_POST['Shipping_Address']);
    $city     = trim($_POST['Shipping_City']);
    $postcode = trim($_POST['Shipping_Postcode']);
    $state    = trim($_POST['Shipping_State']);
    $phone    = trim($_POST['Shipping_Phone']);

    // ✅ 驗證邏輯
    if (empty($name) || empty($address) || empty($city) || empty($postcode) || empty($state) || empty($phone)) {
        $errorMsg = "❌ All fields are required.";
    } elseif (!preg_match('/^[A-Za-z\s]+$/', $city)) {
        $errorMsg = "❌ City must only contain letters.";
    } elseif (!preg_match('/^\d{5}$/', $postcode)) {
        $errorMsg = "❌ Postcode must be exactly 5 digits.";
    } elseif (!in_array(strtolower($state), $allowedStates)) {
        $errorMsg = "❌ Invalid state. Please select from the list.";
    }
    else if (!preg_match('/^01\d{8,9}$/', $phone)) {
    $errorMsg = "❌ Phone must be 10–11 digits and start with 01.";
}

    if (empty($errorMsg)) {
        $update_query = "
            UPDATE `08_order` 
            SET Shipping_Name = ?, Shipping_Address = ?, Shipping_City = ?,
                Shipping_Postcode = ?, Shipping_State = ?, Shipping_Phone = ?
            WHERE OrderID = ? AND CustomerID = ?
        ";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ssssssii", $name, $address, $city, $postcode, $state, $phone, $order_id, $CustomerID);

        if ($update_stmt->execute()) {
            header("Location: order_view.php?order_id=" . $order_id);
            exit();
        } else {
            $errorMsg = "❌ Failed to update address.";
        }

        // 保留輸入值
        $order['Shipping_Name'] = $name;
        $order['Shipping_Address'] = $address;
        $order['Shipping_City'] = $city;
        $order['Shipping_Postcode'] = $postcode;
        $order['Shipping_State'] = $state;
        $order['Shipping_Phone'] = $phone;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Shipping Address</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #121212; color: #f1f1f1; }
        .container { margin-top: 50px; 
              padding-bottom: 200px; /* 多留一點底部空間 */

         
}
        .section { background-color: #1e1e1e; padding: 20px; border-radius: 10px; }
        label { color: #f1f1f1; }
        .error-message {
            background-color: #ff4c4c;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="section" >
        <h2>Edit Shipping Address</h2>

        <?php if (!empty($errorMsg)): ?>
            <div class="error-message"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" name="Shipping_Name"
                       value="<?= htmlspecialchars($order['Shipping_Name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Address</label>
                <input type="text" class="form-control" name="Shipping_Address"
                       value="<?= htmlspecialchars($order['Shipping_Address']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">City</label>
                <input type="text" class="form-control" name="Shipping_City"
                       pattern="^[A-Za-z\s]+$" title="Only letters allowed"
                       value="<?= htmlspecialchars($order['Shipping_City']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Postcode</label>
                <input type="text" class="form-control" name="Shipping_Postcode"
                       pattern="\d{5}" title="Enter 5 digit postcode"
                       value="<?= htmlspecialchars($order['Shipping_Postcode']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">State</label>
                <select name="Shipping_State" class="form-control" required>
                    <option value="">-- Select State --</option>
                    <?php foreach ($allowedStates as $s): ?>
                        <?php $ucState = ucwords($s); ?>
                        <option value="<?= $ucState ?>" <?= strtolower($order['Shipping_State']) === $s ? 'selected' : '' ?>>
                            <?= $ucState ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
               <input type="text" class="form-control" name="Shipping_Phone"
       pattern="^01\d{8,9}$" title="Must be 10 or 11 digits, starting with 01"
       value="<?= htmlspecialchars($order['Shipping_Phone']) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Address</button>
            <a href="order_view.php?order_id=<?= $order_id ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
</body>
</html>
