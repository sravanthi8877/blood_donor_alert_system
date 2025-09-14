<?php
session_start();

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if ($username === 'admin' && $password === 'admin123') {
    $_SESSION['admin'] = true;
    header("Location: dashboard.php");
    exit();
} else {
    echo "<script>alert('Invalid login'); window.location.href = 'login.html';</script>";
}
?>
