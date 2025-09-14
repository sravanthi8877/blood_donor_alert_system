<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "blood_donor_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=donors.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Add header row
fputcsv($output, array('ID', 'Name', 'Blood Group', 'Phone', 'City', 'Latitude', 'Longitude', 'Last Donated'));

// Calculate 3 months ago
$three_months_ago = date('Y-m-d', strtotime('-3 months'));

// Select only eligible donors (never donated OR 3+ months ago)
$sql = "SELECT id, name, blood_group, phone, city, latitude, longitude, last_donation_date 
        FROM donors 
        WHERE last_donation_date IS NULL OR last_donation_date <= '$three_months_ago'";
$rows = $conn->query($sql);

// Write each row to CSV
if ($rows && $rows->num_rows > 0) {
    while($row = $rows->fetch_assoc()) {
        fputcsv($output, $row);
    }
}

fclose($output);
$conn->close();
?>
