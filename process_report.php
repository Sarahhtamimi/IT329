<?php

include 'auth_admin.php';
include 'db_connection.php';

if (!isset($_POST['recipeID']) || !isset($_POST['action'])) {

    echo "false";
    exit();

}

$recipeID = (int) $_POST['recipeID'];
$action = $_POST['action'];


// ==========================
// DISMISS REPORT
// ==========================

if ($action == "dismiss") {

    $deleteReportQuery = "DELETE FROM report WHERE recipeID = $recipeID";

    if (mysqli_query($conn, $deleteReportQuery)) {

        echo "true";

    } else {

        echo "false";

    }

    exit();
}



// ==========================
// BLOCK USER
// ==========================

if ($action == "block") {

    // get recipe owner info
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


        // add to blocked table
        $insertBlockedQuery = "

            INSERT INTO blockeduser

            (firstName, lastName, emailAddress)

            VALUES

            ('$firstName', '$lastName', '$email')

        ";

        mysqli_query($conn, $insertBlockedQuery);



        // get all recipes
        $recipesQuery = "

            SELECT id, photoFileName, videoFilePath

            FROM recipe

            WHERE userID = $blockedUserID

        ";

        $recipesResult = mysqli_query($conn, $recipesQuery);



        while ($recipeRow = mysqli_fetch_assoc($recipesResult)) {

            $currentRecipeID = $recipeRow['id'];



            // delete photo
            if (!empty($recipeRow['photoFileName'])) {

                $photoPath = "images/" . $recipeRow['photoFileName'];

                if (file_exists($photoPath)
                    && $recipeRow['photoFileName'] != "Avatar.png") {

                    unlink($photoPath);

                }

            }



            // delete video
            if (!empty($recipeRow['videoFilePath'])) {

                if (file_exists($recipeRow['videoFilePath'])) {

                    unlink($recipeRow['videoFilePath']);

                }

            }



            // delete related data
            mysqli_query($conn,
                "DELETE FROM favourites WHERE recipeID = $currentRecipeID");

            mysqli_query($conn,
                "DELETE FROM likes WHERE recipeID = $currentRecipeID");

            mysqli_query($conn,
                "DELETE FROM comment WHERE recipeID = $currentRecipeID");

            mysqli_query($conn,
                "DELETE FROM ingredients WHERE recipeID = $currentRecipeID");

            mysqli_query($conn,
                "DELETE FROM instructions WHERE recipeID = $currentRecipeID");

            mysqli_query($conn,
                "DELETE FROM report WHERE recipeID = $currentRecipeID");

        }



        // delete recipes
        mysqli_query($conn,
            "DELETE FROM recipe WHERE userID = $blockedUserID");



        // delete user
        mysqli_query($conn,
            "DELETE FROM user WHERE id = $blockedUserID");



        echo "true";

        exit();

    }

}


echo "false";

?>