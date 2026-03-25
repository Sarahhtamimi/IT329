<?php
include 'auth_admin.php';
include 'db_connection.php';

if (!isset($_POST['recipeID']) || !isset($_POST['action'])) {
    header("Location: admin.php");
    exit();
}

$recipeID = (int) $_POST['recipeID'];
$action = $_POST['action'];

//if dismiss was chosen= delets the report for that recipe then returns to admin.php
if ($action == "dismiss") {
    $deleteReportQuery = "DELETE FROM report WHERE recipeID = $recipeID";
    mysqli_query($conn, $deleteReportQuery);

    header("Location: admin.php");
    exit();
}

//if block user was chosen
if ($action == "block") {

    // 1-get recipe owner info
    $userQuery = "
        SELECT user.id, user.firstName, user.lastName, user.emailAddress
        FROM recipe
        JOIN user ON recipe.userID = user.id
        WHERE recipe.id = $recipeID
    ";
    $userResult = mysqli_query($conn, $userQuery);
    $userRow = mysqli_fetch_assoc($userResult);

    if ($userRow) {
        $blockedUserID = $userRow['id'];
        $firstName = mysqli_real_escape_string($conn, $userRow['firstName']);
        $lastName = mysqli_real_escape_string($conn, $userRow['lastName']);
        $email = mysqli_real_escape_string($conn, $userRow['emailAddress']);

        // 2-add user to blockeduser table
        $insertBlockedQuery = "
            INSERT INTO blockeduser (firstName, lastName, emailAddress)
            VALUES ('$firstName', '$lastName', '$email')
        ";
        mysqli_query($conn, $insertBlockedQuery);

        // 3-get all recipes of that user
        $recipesQuery = "SELECT id, photoFileName, videoFilePath FROM recipe WHERE userID = $blockedUserID";
        $recipesResult = mysqli_query($conn, $recipesQuery);

        while ($recipeRow = mysqli_fetch_assoc($recipesResult)) {
            $currentRecipeID = $recipeRow['id'];

            // 4-delete associated files if they exist
            if (!empty($recipeRow['photoFileName'])) {
                $photoPath = "images/" . $recipeRow['photoFileName'];
                if (file_exists($photoPath) && $recipeRow['photoFileName'] != "Avatar.png") {
                    unlink($photoPath);
                }
            }

            if (!empty($recipeRow['videoFilePath'])) {
                if (file_exists($recipeRow['videoFilePath'])) {
                    unlink($recipeRow['videoFilePath']);
                }
            }

            // 5-delete associated data for each recipe
            mysqli_query($conn, "DELETE FROM favourites WHERE recipeID = $currentRecipeID");
            mysqli_query($conn, "DELETE FROM likes WHERE recipeID = $currentRecipeID");
            mysqli_query($conn, "DELETE FROM comment WHERE recipeID = $currentRecipeID");
            mysqli_query($conn, "DELETE FROM ingredients WHERE recipeID = $currentRecipeID");
            mysqli_query($conn, "DELETE FROM instructions WHERE recipeID = $currentRecipeID");
            mysqli_query($conn, "DELETE FROM report WHERE recipeID = $currentRecipeID");
        }

        // 6-delete all recipes of that user
        mysqli_query($conn, "DELETE FROM recipe WHERE userID = $blockedUserID");

        // 7-delete the user from user table
        mysqli_query($conn, "DELETE FROM user WHERE id = $blockedUserID");
    }

    header("Location: admin.php");
    exit();
}

header("Location: admin.php");
exit();
?>