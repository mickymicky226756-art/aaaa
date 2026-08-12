<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logging out...</title>
    <script>
        // Clear session storage so the welcome popup will show again on next login
        sessionStorage.clear();
        
        // Redirect to login page
        window.location.replace('login.php');
    </script>
</head>
<body>
</body>
</html>
