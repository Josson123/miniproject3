<?php
session_start();
session_unset(); // Unset all session variables
session_destroy(); // Destroy the session

// Redirect to home or login page after logout
header("Location: home.php");
exit();
?>
