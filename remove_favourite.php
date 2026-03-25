<?php
//when user clicks remove Deletes from DB then redirects to user.php 
include 'auth_user.php';
include 'db_connection.php';

$userID = $_SESSION['userID'];

if (isset($_GET['recipeID'])) {
    $recipeID = (int) $_GET['recipeID'];

    $deleteQuery = "DELETE FROM favourites WHERE userID = $userID AND recipeID = $recipeID";
    mysqli_query($conn, $deleteQuery);
}

header("Location: user.php");
exit();
?>