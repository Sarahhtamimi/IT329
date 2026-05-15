<?php
//AJAX endpoint: deletes a favourite and returns "true" or "false"
include 'auth_user.php';
include 'db_connection.php';

header('Content-Type: text/plain');

$userID = $_SESSION['userID'];

if (isset($_GET['recipeID'])) {
    $recipeID = (int) $_GET['recipeID'];

    $deleteQuery = "DELETE FROM favourites WHERE userID = $userID AND recipeID = $recipeID";
    $result = mysqli_query($conn, $deleteQuery);

    if ($result && mysqli_affected_rows($conn) > 0) {
        echo "true";
    } else {
        echo "false";
    }
} else {
    echo "false";
}
?>
