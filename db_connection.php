<?php
$host = "localhost";
$dbname = "palmGlow";   
$username = "root";
$password = "root";            //  MAMP , root/root

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>