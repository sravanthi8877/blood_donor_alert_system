<?php
// Debugging ON
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "sql202.infinityfree.com";  
$username   = "if0_39592163";         
$password   = "s0Rb9U8iUP6tZ";   
$dbname     = "if0_39592163_blood";   

// DB connect
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// Collect form values safely
$name        = isset($_POST['name']) ? trim($conn->real_escape_string($_POST['name'])) : '';
$blood_group = isset($_POST['blood_group']) ? trim($conn->real_escape_string($_POST['blood_group'])) : '';
$phone       = isset($_POST['phone']) ? trim($conn->real_escape_string($_POST['phone'])) : '';
$city        = isset($_POST['city']) ? trim($conn->real_escape_string($_POST['city'])) : '';
$latitude    = isset($_POST['latitude']) ? trim($conn->real_escape_string($_POST['latitude'])) : '';
$longitude   = isset($_POST['longitude']) ? trim($conn->real_escape_string($_POST['longitude'])) : '';

$last_donation_date = (!empty($_POST['last_donation_date'])) 
    ? $conn->real_escape_string($_POST['last_donation_date']) 
    : NULL;

// Prepare SQL insert query
$sql = "INSERT INTO donors (name, blood_group, phone, city, latitude, longitude, last_donation_date)
        VALUES ('$name', '$blood_group', '$phone', '$city', '$latitude', '$longitude', " .
        ($last_donation_date ? "'$last_donation_date'" : "NULL") . ")";

if ($conn->query($sql) === TRUE) {
    // Success page
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Registration Successful</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f8fc; 
                   display: flex; justify-content: center; align-items: center; height: 100vh; }
            .success-box { text-align: center; background: #fff; padding: 30px 40px; border-radius: 10px;
                           box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
            h2 { color: green; margin-bottom: 10px; }
            .btn { padding: 10px 20px; background: #007BFF; color: white; text-decoration: none;
                   border-radius: 5px; display: inline-block; margin-top: 15px; }
            .btn:hover { background: #0056b3; }
        </style>
    </head>
    <body>
        <div class='success-box'>
            <h2>✅ Registration Successful!</h2>
            <p>Your details have been saved successfully.</p>
            <a href='dashboard.php' class='btn'>Go to Dashboard</a>
        </div>
    </body>
    </html>
    ";
} else {
    echo "❌ Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
