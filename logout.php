<?php
session_start();        // Start session
session_destroy();      // Destroy all session data (logout)
header("Location: login.html"); // Redirect back to login
exit();
?>
