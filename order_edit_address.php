<?php
session_start();
if (!isset($_SESSION['customer_id'])) 
{
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

if (!$order) 
{
    echo "Order not found or access denied.";
    exit();
}

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

$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    $name     = trim($_POST['Shipping_Name']);
    $address  = trim($_POST['Shipping_Address']);
    $city     = trim($_POST['Shipping_City']);
    $postcode = trim($_POST['Shipping_Postcode']);
    $state    = trim($_POST['Shipping_State']);
    $phone    = trim($_POST['Shipping_Phone']);

    if (empty($name) || empty($address) || empty($city) || empty($postcode) || empty($state) || empty($phone)) 
    {
        $errorMsg = "❌ All fields are required.";
    } 
    elseif (!preg_match('/^\d{5}$/', $postcode)) 
    {
        $errorMsg = "❌ Postcode must be exactly 5 digits.";
    } 
    else if (!preg_match('/^01\d{8,9}$/', $phone)) 
    {
        $errorMsg = "❌ Phone must be 10–11 digits and start with 01.";
    } 
    else 
    {
        if (!array_key_exists($state, $malaysian_locations) || !in_array($city, $malaysian_locations[$state])) 
        {
            $errorMsg = "❌ Invalid state or city. Please choose from the list.";
        }
    }

    if (empty($errorMsg)) 
    {
        $update_query = "
            UPDATE `08_order` 
            SET Shipping_Name = ?, Shipping_Address = ?, Shipping_City = ?,
                Shipping_Postcode = ?, Shipping_State = ?, Shipping_Phone = ?
            WHERE OrderID = ? AND CustomerID = ?
        ";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ssssssii", $name, $address, $city, $postcode, $state, $phone, $order_id, $CustomerID);

        if ($update_stmt->execute()) 
        {
            header("Location: order_view.php?order_id=" . $order_id);
            exit();
        } 
        else 
        {
            $errorMsg = "❌ Failed to update address.";
        }

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
        .container 
        { margin-top: 50px; 
            padding-bottom: 200px; 
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
            <input type="text" class="form-control" name="Shipping_Name" value="<?= htmlspecialchars($order['Shipping_Name']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Address</label>
            <input type="text" class="form-control" name="Shipping_Address" value="<?= htmlspecialchars($order['Shipping_Address']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">State</label>
            <select id="shipping_state" name="Shipping_State" class="form-control" required>
                <option value="">-- Select State --</option>
                <?php foreach (array_keys($malaysian_locations) as $state_option): ?>
                    <option value="<?= $state_option ?>" <?= ($order['Shipping_State'] === $state_option) ? 'selected' : '' ?>>
                        <?= $state_option ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">City</label>
            <select id="shipping_city" name="Shipping_City" class="form-control" required>
                <option value="">-- Select City --</option>
                <?php
                    if (!empty($order['Shipping_State']) && isset($malaysian_locations[$order['Shipping_State']])) 
                    {
                        foreach ($malaysian_locations[$order['Shipping_State']] as $city_option) 
                        {
                            $selected = ($order['Shipping_City'] === $city_option) ? 'selected' : '';
                            echo "<option value=\"$city_option\" $selected>$city_option</option>";
                        }
                    }
                ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Postcode</label>
            <input type="text" class="form-control" name="Shipping_Postcode" pattern="\d{5}" title="Enter 5 digit postcode" value="<?= htmlspecialchars($order['Shipping_Postcode']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" class="form-control" name="Shipping_Phone" pattern="^01\d{8,9}$" title="Must be 10 or 11 digits, starting with 01" value="<?= htmlspecialchars($order['Shipping_Phone']) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Update Address</button>
        <a href="order_view.php?order_id=<?= $order_id ?>" class="btn btn-secondary">Cancel</a>
    </form>
    </div>
</div>
</body>

<script>
    const malaysianLocations = <?php echo json_encode($malaysian_locations); ?>;

    const stateSelect = document.getElementById('shipping_state');
    const citySelect = document.getElementById('shipping_city');

    function updateCityDropdown() 
    {
        const selectedState = stateSelect.value;
        
        citySelect.innerHTML = '<option value="">-- Select City --</option>'; 

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
</script>
</html>
