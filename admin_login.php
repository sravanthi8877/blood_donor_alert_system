<?php
session_start();

// Database connection (InfinityFree MySQL)
$servername = "sql202.infinityfree.com";
$username_db = "if0_39592163";   // InfinityFree DB username
$password_db = "s0Rb9U8iUP6tZ";  // InfinityFree DB password
$dbname = "if0_39592163_blood";  // Your database name

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// Login check
if (isset($_POST['login'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    // Check username and password
    $sql = "SELECT * FROM admin WHERE username='$username' AND password=SHA2('$password', 256)";
    $result = $conn->query($sql);

    if ($result && $result->num_rows == 1) {
        $_SESSION['admin'] = $username;
        header("Location: dashboard.php"); // redirect to dashboard
        exit();
    } else {
        $error = "❌ Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <style>
        body { font-family: Arial; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f2f2f2; }
        .login-container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 0 12px #00000040; width: 300px; }
        input[type="text"], input[type="password"], input[type="submit"] { width: 100%; padding: 12px; margin-top: 10px; border-radius: 6px; border: 1px solid #ccc; }
        input[type="submit"] { background: crimson; color: white; border: none; cursor: pointer; font-weight: bold; }
        input[type="submit"]:hover { background: darkred; }
        .error { color: red; text-align: center; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 style="text-align:center;">Admin Login</h2>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" value="sravanthi123" required><br>
            <input type="password" name="password" placeholder="Password" value="sravanthi123" required><br>
            <input type="submit" name="login" value="Login">
        </form>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
    </div>
</body>
</html>
