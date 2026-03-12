
<?php
session_start();

if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'admin') {
    header("Location: login.php?error=unauthorized");
    exit();
}
?>
