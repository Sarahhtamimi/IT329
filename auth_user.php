
<?php
session_start();

if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'user') {
   echo "false";
    exit();
}
