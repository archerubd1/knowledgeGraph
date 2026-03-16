<?php
session_start();
session_unset();    // Clear all session variables
session_destroy();  // Destroy the session entirely
header("Location: index.php"); // Redirect back to login
exit();
?>