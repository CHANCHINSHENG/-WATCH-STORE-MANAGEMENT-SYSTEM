<?php
session_start();
include 'db.php';

// --- 登录状态判断 ---
$loggedIn = isset($_SESSION['CustomerID']);
$email = '';

// --- 获取用户/访客邮箱 ---
if ($loggedIn) {
    $stmt = $conn->prepare("SELECT Cust_Email FROM 02_customer WHERE CustomerID = ?");
    $stmt->bind_param("i", $_SESSION['CustomerID']);
    $stmt->execute();
    $result = $stmt->get_result();
    $email = $result->fetch_assoc()['Cust_Email'] ?? '';
} elseif (isset($_POST['guest_email'])) {
    $email = filter_var($_POST['guest_email'], FILTER_SANITIZE_EMAIL);
    $_SESSION['Guest_Email'] = $email;
} elseif (isset($_SESSION['Guest_Email'])) {
    $email = $_SESSION['Guest_Email'];
}

// --- 获取购物车函数 ---
function getCartItems($conn, $orderId) {
    $stmt = $conn->prepare("
        SELECT o.OrderID, od.Order_detailsID, p.*, od.Order_Quantity, od.Order_Subtotal
        FROM `Order` o
        JOIN `Order Details` od ON o.OrderID = od.OrderID
        JOIN Product p ON od.ProductID = p.ProductID
        WHERE o.OrderID = ? AND o.OrderStatus = 'cart'
    ");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    return $stmt->get_result();
}

// --- 获取购物车内容 ---
$cart_items = [];
$subtotal = 0;
$order_id = null;

if ($loggedIn) {
    $stmt = $conn->prepare("SELECT OrderID FROM `Order` WHERE CustomerID = ? AND OrderStatus = 'cart'");
    $stmt->bind_param("i", $_SESSION['CustomerID']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $order_id = $row['OrderID'];
        $cart_result = getCartItems($conn, $order_id);
        while ($item = $cart_result->fetch_assoc()) {
            $cart_items[] = $item;
            $subtotal += $item['Order_Subtotal'];
        }
    }
} elseif (isset($_SESSION['guest_cart_id'])) {
    $order_id = $_SESSION['guest_cart_id'];
    $cart_result = getCartItems($conn, $order_id);
    while ($item = $cart_result->fetch_assoc()) {
        $cart_items[] = $item;
        $subtotal += $item['Order_Subtotal'];
    }
}

// --- 运费计算 ---
$shipping_fee = ($subtotal > 100) ? 0 : 10;
$total = $subtotal + $shipping_fee;

// --- 处理订单提交 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $required_fields = [
        'name' => trim($_POST['name']),
        'address' => trim($_POST['address']),
        'city' => trim($_POST['city']),
        'postcode' => trim($_POST['postcode']),
        'state' => trim($_POST['state']),
        'phone' => trim($_POST['phone']),
        'payment_method' => trim($_POST['payment_method'])
    ];

    foreach ($required_fields as $field => $value) {
        if (empty($value)) {
            $_SESSION['error'] = "Missing required field: $field";
            header("Location: checkout.php");
            exit();
        }
    }

    if (!preg_match('/^\d{10,15}$/', $required_fields['phone'])) {
        $_SESSION['error'] = "Invalid phone number format.";
        header("Location: checkout.php");
        exit();
    }

    try {
        $conn->begin_transaction();

        // 更新订单状态
        $stmt = $conn->prepare("
            UPDATE `Order`
            SET OrderStatus = 'pending',
                Shipping_Method = 'Standard Delivery (Malaysia)',
                Shipping_Name = ?,
                Shipping_Address = ?,
                Shipping_City = ?,
                Shipping_Postcode = ?,
                Shipping_State = ?,
                Shipping_Phone = ?,
                Total_Price = ?
            WHERE OrderID = ?
        ");
        $stmt->bind_param(
            "ssssssdi",
            $required_fields['name'],
            $required_fields['address'],
            $required_fields['city'],
            $required_fields['postcode'],
            $required_fields['state'],
            $required_fields['phone'],
            $total,
            $order_id
        );
        $stmt->execute();

        // 插入支付信息
        $stmt = $conn->prepare("INSERT INTO Payment (OrderID, Payment_Method, Payment_Status) VALUES (?, ?, 'pending')");
        $stmt->bind_param("is", $order_id, $required_fields['payment_method']);
        $stmt->execute();

        $conn->commit();

        // 清除购物车 session
        if ($loggedIn) {
            unset($_SESSION['cart']);
        } else {
            unset($_SESSION['guest_cart_id']);
        }

        header("Location: order_confirmation.php?id=$order_id");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Order failed: " . $e->getMessage();
        header("Location: checkout.php");
        exit();
    }
}
?>
