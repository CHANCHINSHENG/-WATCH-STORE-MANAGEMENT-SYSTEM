<?php
session_start();
if (!isset($_SESSION['customer_id'])) 
{
    header("Location: customer_login.php");
    exit();
}

$CustomerID = $_SESSION['customer_id'];
require_once 'db.php';

$malaysian_locations = 
[
    'Johor' => 
    [
        'Batu Pahat',
        'Johor Bahru',
        'Kluang',
        'Kota Tinggi',
        'Kulai',
        'Mersing',
        'Muar',
        'Pasir Gudang',
        'Pontian',
        'Segamat',
        'Skudai',
        'Tangkak'
    ],

    'Kedah' => 
    [
        'Alor Setar',
        'Baling',
        'Jitra',
        'Kulim',
        'Kuala Nerang',
        'Langkawi',
        'Pendang',
        'Sungai Petani'
    ],

    'Kelantan' => 
    [
        'Bachok',
        'Gua Musang',
        'Jeli',
        'Kota Bharu',
        'Kuala Krai',
        'Machang',
        'Pasir Mas',
        'Pasir Puteh',
        'Tanah Merah',
        'Tumpat'
    ],

    'Kuala Lumpur' => 
    [
        'Kuala Lumpur'
    ],

    'Labuan' => 
    [
        'Labuan'
    ],

    'Melaka' => 
    [
        'Alor Gajah',
        'Jasin',
        'Melaka City'
    ],

    'Negeri Sembilan' => 
    [
        'Jelebu',
        'Jempol',
        'Kuala Pilah',
        'Nilai',
        'Port Dickson',
        'Rembau',
        'Seremban',
        'Tampin'
    ],

    'Pahang' => 
    [
        'Bentong',
        'Bera',
        'Cameron Highlands',
        'Genting Highlands',
        'Jerantut',
        'Kuantan',
        'Kuala Lipis',
        'Maran',
        'Pekan',
        'Raub',
        'Rompin',
        'Temerloh'
    ],

    'Penang' => 
    [
        'Bayan Lepas',
        'Bukit Mertajam',
        'Butterworth',
        'George Town',
        'Seberang Perai'
    ],

    'Perak' => 
    [
        'Bagan Serai',
        'Batu Gajah',
        'Ipoh',
        'Kampar',
        'Kuala Kangsar',
        'Lumut',
        'Sitiawan',
        'Taiping',
        'Tanjung Malim',
        'Teluk Intan'
    ],

    'Perlis' =>
    [
        'Arau',
        'Kangar',
        'Kuala Perlis',
        'Padang Besar'
    ],

    'Putrajaya' =>
    [
        'Putrajaya'
    ],

    'Sabah' => 
    [
        'Beaufort',
        'Keningau',
        'Kota Belud',
        'Kota Kinabalu',
        'Kudat',
        'Lahad Datu',
        'Papar',
        'Penampang',
        'Ranau',
        'Sandakan',
        'Semporna',
        'Tawau',
        'Tenom'
    ],

    'Sarawak' => 
    [
        'Betong',
        'Bintulu',
        'Kapit',
        'Kuching',
        'Limbang',
        'Miri',
        'Mukah',
        'Samarahan',
        'Serian',
        'Sibu',
        'Sri Aman'
    ],

    'Selangor' => 
    [
        'Ampang Jaya',
        'Bangi',
        'Banting',
        'Cyberjaya',
        'Kajang',
        'Klang',
        'Kuala Selangor',
        'Petaling Jaya',
        'Puchong',
        'Rawang',
        'Sabak Bernam',
        'Sekinchan',
        'Selayang',
        'Sepang',
        'Shah Alam',
        'Subang Jaya'
    ],

    'Terengganu' => 
    [
        'Dungun',
        'Hulu Terengganu',
        'Kemaman',
        'Kuala Besut',
        'Kuala Terengganu',
        'Marang'
    ]
];

$errors = []; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $postcode   = trim($_POST['postcode'] ?? '');
    $city       = trim($_POST['city'] ?? '');
    $state      = trim($_POST['state'] ?? '');

    if (empty($first_name)) 
    { 
        $errors[] = "First name is required."; 
    }

    if (empty($last_name)) 
    { 
        $errors[] = "Last name is required."; 
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
    { 
        $errors[] = "Invalid email format."; 
    }

    if (!empty($phone) && !preg_match('/^01\d{8,9}$/', $phone)) 
    { 
        $errors[] = "Phone must be 10-11 digits starting with 01."; 
    }

    if (!empty($postcode) && !preg_match('/^\d{5}$/', $postcode)) 
    { 
        $errors[] = "Postcode must be 5 digits."; 
    }

    if (!empty($state) && !array_key_exists($state, $malaysian_locations)) 
    { 
        $errors[] = "Invalid state selected."; 
    }

    if (!empty($city) && !empty($state) && !in_array($city, $malaysian_locations[$state] ?? [])) 
    { 
        $errors[] = "The selected city does not belong to the selected state."; 
    }

    if (empty($errors)) 
    {
        $update_query = "UPDATE `02_customer` SET 
            Cust_First_Name = ?, Cust_Last_Name = ?, Cust_Email = ?, Cust_PhoneNumber = ?, 
            Cust_Address = ?, Cust_Postcode = ?, Cust_City = ?, Cust_State = ?
            WHERE CustomerID = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssssssssi", $first_name, $last_name, $email, $phone, $address, $postcode, $city, $state, $CustomerID);
        
        if ($stmt->execute()) 
        {
            header("Location: customer_profile.php?success=1");
            exit();
        } 
        else 
        {
            $errors[] = "Failed to update profile. Please try again.";
        }
    }
}

$customer_query = "SELECT * FROM `02_customer` WHERE CustomerID = ?";
$customer_stmt = $conn->prepare($customer_query);
$customer_stmt->bind_param("i", $CustomerID);
$customer_stmt->execute();
$customer = $customer_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body 
        {
            background-color: #121212;
            color: #f1f1f1;
        }
        .form-container 
        {
            background-color: #1e1e1e;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            margin: 50px auto;
        }
        input, textarea 
        {
            background-color: #2a2a2a;
            color: white;
            border: 1px solid #444;
        }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Edit Profile</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p class="mb-0"><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label>First Name</label>
                <input name="first_name" class="form-control" value="<?= htmlspecialchars($customer['Cust_First_Name']) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label>Last Name</label>
                <input name="last_name" class="form-control" value="<?= htmlspecialchars($customer['Cust_Last_Name']) ?>" required>
            </div>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input name="email" type="email" class="form-control" value="<?= htmlspecialchars($customer['Cust_Email']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Phone</label>
            <input name="phone" class="form-control" value="<?= htmlspecialchars($customer['Cust_PhoneNumber']) ?>" placeholder="e.g., 0123456789">
        </div>
        <div class="mb-3">
            <label>Address</label>
            <textarea name="address" class="form-control"><?= htmlspecialchars($customer['Cust_Address']) ?></textarea>
        </div>
         <div class="mb-3">
            <label>Postcode</label>
            <input name="postcode" class="form-control" value="<?= htmlspecialchars($customer['Cust_Postcode']) ?>" placeholder="e.g., 81300">
        </div>

        <div class="mb-3">
            <label>State</label>
            <select id="state" name="state" class="form-control">
                <option value="">-- Select a State --</option>
                <?php foreach (array_keys($malaysian_locations) as $state_option): ?>
                    <option value="<?= $state_option ?>" <?= ($customer['Cust_State'] === $state_option) ? 'selected' : '' ?>>
                        <?= $state_option ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>City</label>
            <select id="city" name="city" class="form-control">
                <option value="">-- Select a City --</option>
                <?php
                    if (!empty($customer['Cust_State']) && isset($malaysian_locations[$customer['Cust_State']])) 
                    {
                        foreach ($malaysian_locations[$customer['Cust_State']] as $city_option) 
                        {
                            $selected = ($customer['Cust_City'] === $city_option) ? 'selected' : '';
                            echo "<option value=\"$city_option\" $selected>$city_option</option>";
                        }
                    }
                ?>
            </select>
        </div>

        <button type="submit" class="btn btn-success">Save</button>
        <a href="customer_profile.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>

<script>
    const malaysianLocations = <?php echo json_encode($malaysian_locations); ?>;

    const stateSelect = document.getElementById('state');
    const citySelect = document.getElementById('city');

    function updateCityDropdown() 
    {
        const selectedState = stateSelect.value;
        citySelect.innerHTML = '<option value="">-- Select a City --</option>'; 

        if (selectedState && malaysianLocations[selectedState]) 
        {
            citySelect.disabled = false;
            const cities = malaysianLocations[selectedState];

            cities.forEach(city => 
            {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                citySelect.appendChild(option);
            });
        } 
        else 
        {
            citySelect.disabled = true;
        }
    }

    stateSelect.addEventListener('change', updateCityDropdown);

    document.addEventListener('DOMContentLoaded', () => 
    {
        if (!stateSelect.value) 
        {
            citySelect.disabled = true;
        }
    });
</script>   
</html>
