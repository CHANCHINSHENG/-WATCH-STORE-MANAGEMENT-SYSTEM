<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['Cust_First_Name']);
    $last_name = trim($_POST['Cust_Last_Name']);
    $address = trim($_POST['Cust_Address']);
    $city = trim($_POST['Cust_City']);
    $postcode = trim($_POST['Cust_Postcode']);
    $state = trim($_POST['Cust_State']);
    $email = trim($_POST['Cust_Email']);
    $username = trim($_POST['Cust_Username']);
    $phone = trim($_POST['Cust_PhoneNumber']);
    $password = password_hash($_POST['Cust_Password'], PASSWORD_DEFAULT);

    // Check if the username or email already exists
    $stmt = $conn->prepare("SELECT CustomerID FROM 02_CUSTOMER WHERE Cust_Email = ? OR Cust_Username = ?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $error = "❌ Email or Username already exists!";
    } else {
        // Insert new customer into the database
        $stmt = $conn->prepare("INSERT INTO 02_CUSTOMER (Cust_First_Name, Cust_Last_Name, Cust_Address, Cust_City, Cust_Postcode, Cust_State, Cust_Email, Cust_Password, Cust_Username, Cust_PhoneNumber) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssss", $first_name, $last_name, $address, $city, $postcode, $state, $email, $password, $username, $phone);
        
        if ($stmt->execute()) {
            $success = "✅ Account created successfully!";
        } else {
            $error = "❌ Error creating account.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up - TIGO</title>
    <link rel="stylesheet" href="customersignup.css">
</head>
<body>
    <div class="container">
        <div class="login-box">
            <img src="img/tigo.png" alt="TIGO Fashion Watch" class="logo">
            <h2>Sign Up</h2>

            <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
            <?php if (isset($success)) { echo "<p class='success'>$success</p>"; } ?>

            <form method="POST">
                <div class="input-group">
                    <label>First Name</label>
                    <input type="text" name="Cust_First_Name" required>
                </div>

                <div class="input-group">
                    <label>Last Name</label>
                    <input type="text" name="Cust_Last_Name" required>
                </div>

                <div class="input-group">
                    <label>Address</label>
                    <input type="text" name="Cust_Address" required>
                </div>

                <div class="input-group">
                    <label>City</label>
                    <input type="text" name="Cust_City" required>
                </div>

                <div class="input-group">
                    <label>Postcode</label>
                    <input type="text" name="Cust_Postcode" required>
                </div>

                <div class="input-group">
                    <label>State</label>
                    <input type="text" name="Cust_State" required>
                </div>

                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="Cust_Email" required>
                </div>

                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="Cust_Username" required>
                </div>

                <div class="input-group">
                    <label>Phone Number</label>
                    <input type="text" name="Cust_PhoneNumber" required>
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="password-container">
                        <input type="password" id="password" name="Cust_Password" placeholder="Enter password" required>
                        <span class="toggle-password" onclick="togglePassword()">👁️</span>
                    </div>
                </div>

                <button type="submit">Sign Up</button>

                <!-- Added "Already have an account?" link -->
                <p class="signin-link">Already have an account? <a href="customer_login.php">Sign in here</a></p>

            </form>
        </div>
    </div>

    <script>
       function togglePassword() {
            let passwordField = document.getElementById("password");
            if (passwordField.type === "password") {
                passwordField.type = "text";
            } else {
                passwordField.type = "password";
            }
        }
    </script>
</body>
</html>
