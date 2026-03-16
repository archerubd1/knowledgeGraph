<?php
// Database Configuration for Astraal LXP
$host = "localhost";
$user = "root";
$pass = "root"; // UwAmp default is 'root', if using XAMPP leave empty ""
$db   = "astraal_lxp";

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    // If connection fails, stop everything and show the error
    die("Database connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 to support all characters
$conn->set_charset("utf8mb4");

// Note: Do not close the PHP tag (
 ?>

 