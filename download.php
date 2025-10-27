<?php
// Set headers → download as CSV file
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="donors.csv"');

// Database connection
$servername = "sql202.infinityfree.com";
$username   = "if0_39592163";
$password   = "s0Rb9U8iUP6tZ";
$dbname     = "if0_39592163_blood";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// Get filters from GET parameters
$filter_name  = isset($_GET['name']) ? trim($_GET['name']) : '';
$filter_blood = isset($_GET['blood_group']) ? trim($_GET['blood_group']) : '';
$filter_city  = isset($_GET['city']) ? trim($_GET['city']) : '';

$where = [];
if($filter_name != '') $where[] = "LOWER(TRIM(name)) LIKE LOWER('%$filter_name%')";
if($filter_blood != '') $where[] = "LOWER(TRIM(blood_group)) = LOWER('$filter_blood')";
if($filter_city != '') $where[] = "LOWER(TRIM(city)) LIKE LOWER('%$filter_city%')";

// 3 months restriction → only if filter applied
$three_months_ago = date('Y-m-d', strtotime('-3 months'));
if(!empty($where)){
    $where[] = "(last_donation_date IS NULL OR last_donation_date <= '$three_months_ago')";
}

// Base query
$sql = "SELECT name, blood_group, phone, city, last_donation_date FROM donors";
if(!empty($where)){
    $sql .= " WHERE ".implode(" AND ", $where);
}
$sql .= " ORDER BY id DESC";

$result = $conn->query($sql);

// Open output stream
$output = fopen("php://output", "w");
fputcsv($output, ['Name', 'Blood Group', 'Phone', 'City', 'Last Donation']);

// If no exact donors → fetch compatible donors
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
} elseif ($filter_blood != '') {
    // Blood compatibility map
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
        $sql2 = "SELECT name, blood_group, phone, city, last_donation_date 
                 FROM donors 
                 WHERE blood_group IN ($groups) 
                 AND (last_donation_date IS NULL OR last_donation_date <= '$three_months_ago')
                 ORDER BY id DESC";
        $compatible_result = $conn->query($sql2);

        if ($compatible_result && $compatible_result->num_rows > 0) {
            while($row = $compatible_result->fetch_assoc()) {
                fputcsv($output, $row);
            }
        } else {
            fputcsv($output, ["No compatible donors found"]);
        }
    }
} else {
    fputcsv($output, ["No donors found"]);
}

fclose($output);
$conn->close();
?>

