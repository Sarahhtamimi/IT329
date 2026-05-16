<?php
$host = "localhost";
$username = "root";
$password = "root";
$dbname = "palmglow";
$port = 8889;

$conn = new mysqli($host, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>