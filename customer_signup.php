<?php
include 'db.php';

$errors = [];
$success = "";

$states = ['Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Perak', 'Perlis', 'Penang', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu'];

$values = [
    'Cust_First_Name' => '',
    'Cust_Last_Name' => '',
    'Cust_Address' => '',
    'Cust_City' => '',
    'Cust_Postcode' => '',
    'Cust_State' => '',
    'Cust_Email' => '',
    'Cust_Username' => '',
    'Cust_PhoneNumber' => ''
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    foreach ($values as $key => $value) {
        $values[$key] = trim($_POST[$key] ?? '');
    }

    $password = $_POST['Cust_Password'] ?? '';
    $confirm_password = $_POST['Confirm_Password'] ?? '';

    // Custom Validation
    if (!preg_match("/^[A-Za-z]+$/", $values['Cust_First_Name'])) {
        $errors['Cust_First_Name'] = "First name must contain letters only.";
    }

    if (!preg_match("/^[A-Za-z]+$/", $values['Cust_Last_Name'])) {
        $errors['Cust_Last_Name'] = "Last name must contain letters only.";
    }

    if (empty($values['Cust_Address'])) {
        $errors['Cust_Address'] = "Address is required.";
    }

    if (!preg_match("/^[A-Za-z ]+$/", $values['Cust_City'])) {
        $errors['Cust_City'] = "City must contain letters only.";
    }

    if (!preg_match("/^\d{5}$/", $values['Cust_Postcode'])) {
        $errors['Cust_Postcode'] = "Postcode must be exactly 5 digits.";
    }

    if (!in_array($values['Cust_State'], $states)) {
        $errors['Cust_State'] = "Please select a valid state.";
    }

    if (!filter_var($values['Cust_Email'], FILTER_VALIDATE_EMAIL) || !str_ends_with($values['Cust_Email'], 'email.com')) {
        $errors['Cust_Email'] = "Email must be valid and end with 'email.com'.";
    }

    if (empty($values['Cust_Username'])) {
        $errors['Cust_Username'] = "Username is required.";
    }

    if (!preg_match("/^01[0-9]-?[0-9]{7,8}$/", $values['Cust_PhoneNumber'])) {
        $errors['Cust_PhoneNumber'] = "Phone must follow Malaysian format (e.g., 012-3456789).";
    }

    if (empty($password)) {
        $errors['Cust_Password'] = "Password is required.";
    }

    if ($password !== $confirm_password) {
        $errors['Confirm_Password'] = "Passwords do not match.";
    }

    // Check for existing user
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT CustomerID FROM 02_CUSTOMER WHERE Cust_Email = ? OR Cust_Username = ?");
        $stmt->bind_param("ss", $values['Cust_Email'], $values['Cust_Username']);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors['Cust_Email'] = "Email or Username already exists.";
        }
    }

    // Insert
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO 02_CUSTOMER (Cust_First_Name, Cust_Last_Name, Cust_Address, Cust_City, Cust_Postcode, Cust_State, Cust_Email, Cust_Password, Cust_Username, Cust_PhoneNumber)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssss",
            $values['Cust_First_Name'], $values['Cust_Last_Name'], $values['Cust_Address'], $values['Cust_City'],
            $values['Cust_Postcode'], $values['Cust_State'], $values['Cust_Email'], $hash,
            $values['Cust_Username'], $values['Cust_PhoneNumber']);

        if ($stmt->execute()) {
            $success = "✅ Account created successfully!";
            $values = array_fill_keys(array_keys($values), '');
        } else {
            $errors['general'] = "❌ Error creating account.";
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
    <style>
        .error-msg { color: red; font-size: 12px; margin-top: 4px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
<div class="container">
    <div class="login-box">
        <img src="img/tigo.png" alt="TIGO Fashion Watch" class="logo">
        <h2>Sign Up</h2>

        <?php if (!empty($errors['general'])) echo "<p class='error'>{$errors['general']}</p>"; ?>
        <?php if ($success) echo "<p class='success'>$success</p>"; ?>

        <form method="POST" novalidate>
            <?php
            function input($name, $label, $type = "text") {
                global $values, $errors;
                echo "<div class='input-group'>";
                echo "<label>$label</label>";
                echo "<input type='$type' name='$name' value='" . htmlspecialchars($values[$name]) . "' required>";
                if (isset($errors[$name])) echo "<div class='error-msg'>{$errors[$name]}</div>";
                echo "</div>";
            }

            input("Cust_First_Name", "First Name");
            input("Cust_Last_Name", "Last Name");
            input("Cust_Email", "Email", "email");
            input("Cust_Username", "Username");
            input("Cust_PhoneNumber", "Phone Number");
            input("Cust_Address", "Address");
            input("Cust_City", "City");
            input("Cust_Postcode", "Postcode");
            ?>
            <div class="input-group">
                <label>State</label>
                <select name="Cust_State" required>
                    <option value="">-- Select State --</option>
                    <?php foreach ($states as $state): ?>
                        <option value="<?= $state ?>" <?= ($values['Cust_State'] === $state) ? 'selected' : '' ?>>
                            <?= $state ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['Cust_State'])) echo "<div class='error-msg'>{$errors['Cust_State']}</div>"; ?>
            </div>


            <div class="input-group">
                <label>Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="Cust_Password" required>
                    <span class="toggle-password" onclick="togglePassword()">👁️</span>
                </div>
                <?php if (isset($errors['Cust_Password'])) echo "<div class='error-msg'>{$errors['Cust_Password']}</div>"; ?>
            </div>

            <div class="input-group">
                <label>Confirm Password</label>
                <div class="password-container">
                    <input type="password" id="confirm_password" name="Confirm_Password" required>
                    <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
                </div>
                <?php if (isset($errors['Confirm_Password'])) echo "<div class='error-msg'>{$errors['Confirm_Password']}</div>"; ?>
            </div>

            <button type="submit">Sign Up</button>
            <p class="signin-link">Already have an account? <a href="customer_login.php">Sign in here</a></p>
        </form>
    </div>
</div>

<script>
    function togglePassword(id = "password") {
        const field = document.getElementById(id);
        field.type = field.type === "password" ? "text" : "password";
    }
</script>
</body>
</html>
