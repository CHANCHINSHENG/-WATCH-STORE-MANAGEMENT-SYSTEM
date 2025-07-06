<?php
include 'db.php';

$errors = [];
$success = "";

$malaysian_locations =
[
    'Johor' => ['Batu Pahat', 'Johor Bahru', 'Kluang', 'Kota Tinggi', 'Kulai', 'Mersing', 'Muar', 'Pasir Gudang', 'Pontian', 'Segamat', 'Skudai', 'Tangkak'],
    'Kedah' => ['Alor Setar', 'Baling', 'Jitra', 'Kulim', 'Kuala Nerang', 'Langkawi', 'Pendang', 'Sungai Petani'],
    'Kelantan' => ['Bachok', 'Gua Musang', 'Jeli', 'Kota Bharu', 'Kuala Krai', 'Machang', 'Pasir Mas', 'Pasir Puteh', 'Tanah Merah', 'Tumpat'],
    'Kuala Lumpur' => ['Kuala Lumpur'],
    'Labuan' => ['Labuan'],
    'Melaka' => ['Alor Gajah', 'Jasin', 'Melaka City'],
    'Negeri Sembilan' => ['Jelebu', 'Jempol', 'Kuala Pilah', 'Nilai', 'Port Dickson', 'Rembau', 'Seremban', 'Tampin'],
    'Pahang' => ['Bentong', 'Bera', 'Cameron Highlands', 'Genting Highlands', 'Jerantut', 'Kuantan', 'Kuala Lipis', 'Maran', 'Pekan', 'Raub', 'Rompin', 'Temerloh'],
    'Penang' => ['Bayan Lepas', 'Bukit Mertajam', 'Butterworth', 'George Town', 'Seberang Perai'],
    'Perak' => ['Bagan Serai', 'Batu Gajah', 'Ipoh', 'Kampar', 'Kuala Kangsar', 'Lumut', 'Sitiawan', 'Taiping', 'Tanjung Malim', 'Teluk Intan'],
    'Perlis' => ['Arau', 'Kangar', 'Kuala Perlis', 'Padang Besar'],
    'Putrajaya' => ['Putrajaya'],
    'Sabah' => ['Beaufort', 'Keningau', 'Kota Belud', 'Kota Kinabalu', 'Kudat', 'Lahad Datu', 'Papar', 'Penampang', 'Ranau', 'Sandakan', 'Semporna', 'Tawau', 'Tenom'],
    'Sarawak' => ['Betong', 'Bintulu', 'Kapit', 'Kuching', 'Limbang', 'Miri', 'Mukah', 'Samarahan', 'Serian', 'Sibu', 'Sri Aman'],
    'Selangor' => ['Ampang Jaya', 'Bangi', 'Banting', 'Cyberjaya', 'Kajang', 'Klang', 'Kuala Selangor', 'Petaling Jaya', 'Puchong', 'Rawang', 'Sabak Bernam', 'Sekinchan', 'Selayang', 'Sepang', 'Shah Alam', 'Subang Jaya'],
    'Terengganu' => ['Dungun', 'Hulu Terengganu', 'Kemaman', 'Kuala Besut', 'Kuala Terengganu', 'Marang']
];

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

    if (!preg_match("/^[A-Za-z]+$/", $values['Cust_First_Name'])) {
        $errors['Cust_First_Name'] = "First name must contain letters only.";
    }

    if (!preg_match("/^[A-Za-z]+$/", $values['Cust_Last_Name'])) {
        $errors['Cust_Last_Name'] = "Last name must contain letters only.";
    }

    if (empty($values['Cust_Address'])) {
        $errors['Cust_Address'] = "Address is required.";
    }
    
    if (!preg_match("/^\d{5}$/", $values['Cust_Postcode'])) {
        $errors['Cust_Postcode'] = "Postcode must be exactly 5 digits.";
    }

    $selectedState = $values['Cust_State'];
    $selectedCity = $values['Cust_City'];

    if (!array_key_exists($selectedState, $malaysian_locations)) {
        $errors['Cust_State'] = "Please select a valid state.";
    } elseif (empty($selectedCity) || !in_array($selectedCity, $malaysian_locations[$selectedState])) {
        $errors['Cust_City'] = "Please select a valid city for the chosen state.";
    }

    if (!filter_var($values['Cust_Email'], FILTER_VALIDATE_EMAIL) || !str_ends_with($values['Cust_Email'], 'gmail.com')) {
        $errors['Cust_Email'] = "Email must be valid and end with 'gmail.com'.";
    }

    if (empty($values['Cust_Username'])) {
        $errors['Cust_Username'] = "Username is required.";
    }

    if (!preg_match("/^01[0-9]-?[0-9]{7,8}$/", $values['Cust_PhoneNumber'])) {
        $errors['Cust_PhoneNumber'] = "Phone must follow Malaysian format (e.g., 012-3456789).";
    }

    if (empty($password)) {
        $errors['Cust_Password'] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors['Cust_Password'] = "Password must be at least 6 characters long.";
    } elseif (!preg_match("/[A-Za-z]/", $password) || !preg_match("/\d/", $password)) {
        $errors['Cust_Password'] = "Password must contain at least one letter and one number.";
    }

    if ($password !== $confirm_password) {
        $errors['Confirm_Password'] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT CustomerID FROM 02_CUSTOMER WHERE Cust_Email = ? OR Cust_Username = ?");
        $stmt->bind_param("ss", $values['Cust_Email'], $values['Cust_Username']);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors['Cust_Email'] = "Email or Username already exists.";
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO 02_CUSTOMER (Cust_First_Name, Cust_Last_Name, Cust_Address, Cust_City, Cust_Postcode, Cust_State, Cust_Email, Cust_Password, Cust_Username, Cust_PhoneNumber) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
    <div class="form-grid">
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
        input("Cust_Address", "Address", "text");
        input("Cust_Postcode", "Postcode");
        ?>

        <div class="input-group">
            <label>State</label>
            <select id="state" name="Cust_State" required>
                <option value="">-- Select State --</option>
                <?php foreach (array_keys($malaysian_locations) as $state): ?>
                    <option value="<?= htmlspecialchars($state) ?>" <?= ($values['Cust_State'] === $state) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($state) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['Cust_State'])) echo "<div class='error-msg'>{$errors['Cust_State']}</div>"; ?>
        </div>

        <div class="input-group">
            <label>City</label>
            <select id="city" name="Cust_City" required disabled>
                <option value="">-- Select a State First --</option>
                <?php
                    if (!empty($values['Cust_State']) && isset($malaysian_locations[$values['Cust_State']])) {
                        echo '<script>document.addEventListener("DOMContentLoaded", function() { document.getElementById("city").disabled = false; });</script>';
                        foreach ($malaysian_locations[$values['Cust_State']] as $city_option) {
                            $selected = ($values['Cust_City'] === $city_option) ? 'selected' : '';
                            echo "<option value=\"" . htmlspecialchars($city_option) . "\" $selected>" . htmlspecialchars($city_option) . "</option>";
                        }
                    }
                ?>
            </select>
            <?php if (isset($errors['Cust_City'])) echo "<div class='error-msg'>{$errors['Cust_City']}</div>"; ?>
        </div>
        <div class="input-group full">
            <label>Password</label>
            <div class="password-container">
                <input type="password" id="password" name="Cust_Password" required>
                <span class="toggle-password" onclick="togglePassword()">👁️</span>
            </div>
            <?php if (isset($errors['Cust_Password'])) echo "<div class='error-msg'>{$errors['Cust_Password']}</div>"; ?>
        </div>

        <div class="input-group full">
            <label>Confirm Password</label>
            <div class="password-container">
                <input type="password" id="confirm_password" name="Confirm_Password" required>
                <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
            </div>
            <?php if (isset($errors['Confirm_Password'])) echo "<div class='error-msg'>{$errors['Confirm_Password']}</div>"; ?>
        </div>
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

    const stateSelect = document.getElementById('state');
    const citySelect = document.getElementById('city');
    const locations = <?php echo json_encode($malaysian_locations); ?>;

    stateSelect.addEventListener('change', function() {
        citySelect.innerHTML = '<option value="">-- Select City --</option>';
        citySelect.disabled = true;

        const selectedState = this.value;

        if (selectedState && locations[selectedState]) {
            citySelect.disabled = false;
            locations[selectedState].forEach(function(city) {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                citySelect.appendChild(option);
            });
        }
    });
</script>
</body>
</html>