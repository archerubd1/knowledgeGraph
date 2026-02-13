<?php
$host = "localhost";
$user = "db_user";
$pass = "db_password";
$db   = "astraal_lxp";

$conn = new mysqli($host,$user,$pass,$db);

if ($conn->connect_error) {
    die("Database connection failed.");
}
?>
