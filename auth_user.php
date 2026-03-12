
<?php
session_start();

if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'user') {
    header("Location: login.php?error=unauthorized");
    exit();
}
?>