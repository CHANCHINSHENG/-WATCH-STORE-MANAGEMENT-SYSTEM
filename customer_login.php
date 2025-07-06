<?php
session_start();
include 'db.php'; 
if ($_SERVER["REQUEST_METHOD"] == "POST") 
{
    $username = $_POST['Cust_Username'];
    $password = $_POST['Cust_Password'];

    $stmt = $conn->prepare("SELECT CustomerID, Cust_Password FROM `02_customer` WHERE Cust_Username = ? AND Is_Deleted = 0");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) 
    {
        $stmt->bind_result($CustomerID, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) 
        {
            $_SESSION['customer_id'] = $CustomerID;

            $sql_cart = "SELECT CartID FROM `11_cart` WHERE CustomerID = ?";
            $stmt_cart = $conn->prepare($sql_cart);
            $stmt_cart->bind_param("i", $CustomerID);   
            $stmt_cart->execute();
            $result_cart = $stmt_cart->get_result();

            if ($result_cart->num_rows > 0) 
            {
                $cart_row = $result_cart->fetch_assoc();
                $cartID = $cart_row['CartID'];

                $sql_items = "
                SELECT  p.ProductID,  p.ProductName, ci.Quantity, p.Product_Price, 
                (SELECT ImagePath FROM 06_product_images WHERE ProductID = p.ProductID AND IsPrimary = 1 LIMIT 1) AS Product_Image
                FROM `12_cart_item` ci
                JOIN `05_product` p ON ci.ProductID = p.ProductID
                WHERE ci.CartID = ?

                ";
                $stmt_items = $conn->prepare($sql_items);
                $stmt_items->bind_param("i", $cartID);
                $stmt_items->execute();
                $result_items = $stmt_items->get_result();

                $cart_items = [];
                while ($row = $result_items->fetch_assoc()) 
                {
                    $cart_items[] = $row;  
                }

                $_SESSION['cart_items'] = $cart_items;
            } 
            else 
            {
                $_SESSION['cart_items'] = [];
            }

            header("Location: customermainpage.php");
            exit();
        } 
        else 
        {
            $error = "❌ Incorrect password. Please try again.";
        }
    } 
    else 
    {
        $error = "❌ Username not found, or account has been deactivated.";

    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TIGO Customer Login</title>
    <link rel="stylesheet" href="customerlogin.css">
</head>
<body>

    <div class="login-container">
        <h2 class="login-title">Customer Login</h2>

        <?php if (isset($error)) 
        { ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php 
        } ?>

        <form action="customer_login.php" method="POST">
    <div class="input-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="Cust_Username" placeholder="Enter your username" required>
    </div>

    <div class="input-group">
        <label for="password">Password</label>
        <div class="password-wrapper">
            <input type="password" id="password" name="Cust_Password" placeholder="Enter your password" required>
            <span class="toggle-password" onclick="togglePassword()">👁️</span>
        </div>
    </div>

    <div class="text-right" style="text-align:right; margin-bottom: 10px;">
        <a href="forgot_password.php" class="forgot-password-link">Forgot Password?</a>
    </div>

    <button type="submit" class="login-btn">Login</button>

    <p class="register-link">
        Don't have an account? <a href="customer_signup.php">Sign up here</a>
    </p>
</form>

    </div>

    <script>
        function togglePassword() 
        {
            let passwordField = document.getElementById("password");
            passwordField.type = passwordField.type === "password" ? "text" : "password";
        }
    </script>

</body>
</html>
