<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Disable cache → data refresh instant
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Database connection
$servername = "sql202.infinityfree.com";
$username   = "if0_39592163";
$password   = "s0Rb9U8iUP6tZ";
$dbname     = "if0_39592163_blood";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// Filters
$filter_name  = isset($_GET['name']) ? trim($_GET['name']) : '';
$filter_blood = isset($_GET['blood_group']) ? trim($_GET['blood_group']) : '';
$filter_city  = isset($_GET['city']) ? trim($_GET['city']) : '';
$current_lat  = isset($_GET['latitude']) ? floatval($_GET['latitude']) : null;
$current_lon  = isset($_GET['longitude']) ? floatval($_GET['longitude']) : null;

// Build WHERE clause
$where = [];
if($filter_name != '') $where[] = "LOWER(TRIM(name)) LIKE LOWER('%$filter_name%')";
if($filter_blood != '') $where[] = "LOWER(TRIM(blood_group)) = LOWER('$filter_blood')";
if($filter_city != '') $where[] = "LOWER(TRIM(city)) LIKE LOWER('%$filter_city%')";

// 3 months restriction → only when filter applied
$three_months_ago = date('Y-m-d', strtotime('-3 months'));
if(!empty($where)){
    $where[] = "(last_donation_date IS NULL OR last_donation_date <= '$three_months_ago')";
}

// Base SQL
$sql = "SELECT id, name, blood_group, phone, city, latitude, longitude, last_donation_date";

if($current_lat && $current_lon){
    $sql .= ", (6371 * acos(
        cos(radians($current_lat)) *
        cos(radians(latitude)) *
        cos(radians(longitude) - radians($current_lon)) +
        sin(radians($current_lat)) *
        sin(radians(latitude))
    )) AS distance";
}

$sql .= " FROM donors";

if(!empty($where)){
    $sql .= " WHERE ".implode(" AND ", $where);
}

if($current_lat && $current_lon){
    $sql .= " ORDER BY distance ASC";
}else{
    $sql .= " ORDER BY id DESC";
}

$result = $conn->query($sql);

// If no exact donors found and blood group filter applied → fetch compatible donors
$compatible_result = null;
if ($result && $result->num_rows == 0 && $filter_blood != '') {
    $compatibility = [
        "A+" => ["A+", "A-", "O+", "O-"],
        "A-" => ["A-", "O-"],
        "B+" => ["B+", "B-", "O+", "O-"],
        "B-" => ["B-", "O-"],
        "AB+" => ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"],
        "AB-" => ["AB-", "A-", "B-", "O-"],
        "O+" => ["O+", "O-"],
        "O-" => ["O-"]
    ];

    if (isset($compatibility[$filter_blood])) {
        $groups = "'" . implode("','", $compatibility[$filter_blood]) . "'";
        $sql2 = "SELECT id, name, blood_group, phone, city, last_donation_date";
        if($current_lat && $current_lon){
            $sql2 .= ", (6371 * acos(
                cos(radians($current_lat)) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians($current_lon)) +
                sin(radians($current_lat)) *
                sin(radians(latitude))
            )) AS distance";
        }
        $sql2 .= " FROM donors WHERE blood_group IN ($groups)";
        $sql2 .= " AND (last_donation_date IS NULL OR last_donation_date <= '$three_months_ago')";
        if($current_lat && $current_lon){
            $sql2 .= " ORDER BY distance ASC";
        } else {
            $sql2 .= " ORDER BY id DESC";
        }
        $compatible_result = $conn->query($sql2);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Blood Donor Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; }
        h2 { text-align: center; color: darkred; margin-top: 20px; }
        table { width: 95%; margin: 20px auto; border-collapse: collapse; font-size:14px; background: #fff; box-shadow: 0 0 8px #ccc; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f44336; color: white; }
        button { padding: 6px 12px; background: green; color: white; border: none; border-radius: 6px; cursor: pointer; }
        button:hover { background: darkgreen; }
        .filter-form { text-align: center; margin: 15px; }
        .filter-form input[type="text"], select { padding: 6px; margin-right: 5px; }
        .filter-form input[type="submit"], .csv-button input[type="submit"] { padding: 6px 12px; background: crimson; color: white; border: none; border-radius: 6px; cursor: pointer; }
        .filter-form input[type="submit"]:hover, .csv-button input[type="submit"]:hover { background: darkred; }
        .csv-button { text-align:center; margin:10px; }
        @media only screen and (max-width: 600px) {
            table, th, td { font-size:12px; }
            button { padding:5px 10px; }
        }
    </style>
</head>
<body>
    <h2>🩸 Blood Donor Dashboard</h2>

    <div class="filter-form">
        <form method="GET" id="filterForm">
            <input type="text" name="name" placeholder="Name" value="<?php echo htmlspecialchars($filter_name); ?>">
            <select name="blood_group">
                <option value="">Select Blood Group</option>
                <?php 
                $groups = ["A+","A-","B+","B-","O+","O-","AB+","AB-"];
                foreach($groups as $g){
                    $sel = ($filter_blood==$g) ? "selected" : "";
                    echo "<option value='$g' $sel>$g</option>";
                }
                ?>
            </select>
            <input type="text" name="city" placeholder="City" value="<?php echo htmlspecialchars($filter_city); ?>">
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">
            <input type="submit" value="Filter">
        </form>
    </div>

    <!-- ✅ CSV Download form with filters -->
    <div class="csv-button">
        <form action="download.php" method="GET">
            <input type="hidden" name="name" value="<?php echo htmlspecialchars($filter_name); ?>">
            <input type="hidden" name="blood_group" value="<?php echo htmlspecialchars($filter_blood); ?>">
            <input type="hidden" name="city" value="<?php echo htmlspecialchars($filter_city); ?>">
            <input type="hidden" name="latitude" value="<?php echo htmlspecialchars($current_lat); ?>">
            <input type="hidden" name="longitude" value="<?php echo htmlspecialchars($current_lon); ?>">
            <input type="submit" value="Download Donor CSV">
        </form>
    </div>

    <table>
        <tr>
            <th>Name</th>
            <th>Blood Group</th>
            <th>Phone</th>
            <th>City</th>
            <th>Last Donation</th>
            <th>Action</th>
            <?php if($current_lat !== null) echo "<th>Distance (km)</th>"; ?>
        </tr>
        <?php
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>".$row['name']."</td>
                        <td>".$row['blood_group']."</td>
                        <td>".$row['phone']."</td>
                        <td>".$row['city']."</td>
                        <td>".($row['last_donation_date'] ? $row['last_donation_date'] : 'N/A')."</td>
                        <td><a href='tel:+91".$row['phone']."'><button>Call Donor</button></a></td>";
                if($current_lat !== null && isset($row['distance'])) echo "<td>".round($row['distance'],2)."</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='".($current_lat!==null?7:6)."'>⚠️ No donors found with $filter_blood</td></tr>";
            if ($compatible_result && $compatible_result->num_rows > 0) {
                echo "<tr><td colspan='".($current_lat!==null?7:6)."' style='color:darkred;font-weight:bold;'>Compatible Donors (can donate to $filter_blood):</td></tr>";
                while($row = $compatible_result->fetch_assoc()) {
                    echo "<tr>
                            <td>".$row['name']."</td>
                            <td>".$row['blood_group']."</td>
                            <td>".$row['phone']."</td>
                            <td>".$row['city']."</td>
                            <td>".($row['last_donation_date'] ? $row['last_donation_date'] : 'N/A')."</td>
                            <td><a href='tel:+91".$row['phone']."'><button>Call Donor</button></a></td>";
                    if($current_lat !== null && isset($row['distance'])) echo "<td>".round($row['distance'],2)."</td>";
                    echo "</tr>";
                }
            }
        }
        ?>
    </table>

    <script>
        if(navigator.geolocation){
            navigator.geolocation.getCurrentPosition(function(position){
                document.getElementById("latitude").value = position.coords.latitude;
                document.getElementById("longitude").value = position.coords.longitude;
                if(!window.location.search.includes("latitude")){
                    document.getElementById("filterForm").submit();
                }
            }, function(error){
                console.log("Location error:", error.message);
            });
        }
    </script>
</body>
</html>

