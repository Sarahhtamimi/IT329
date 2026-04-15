<?php
include 'auth_user.php';
include 'db_connection.php';

// Check if recipe ID exists
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Recipe ID is missing.");
}

$recipeID = $_GET['id'];
$userID   = $_SESSION['userID'];


// Get recipe to check ownership and get file names
$sqlRecipe = "SELECT photoFileName, videoFilePath, userID 
              FROM Recipe 
              WHERE id = ?";
$stmtRecipe = $conn->prepare($sqlRecipe);
$stmtRecipe->bind_param("i", $recipeID);
$stmtRecipe->execute();
$resultRecipe = $stmtRecipe->get_result();

if ($resultRecipe->num_rows == 0) {
    die("Recipe not found.");
}

$recipe = $resultRecipe->fetch_assoc();

// Make sure the logged-in user owns the recipe
if ($recipe['userID'] != $userID) {
    die("Unauthorized action.");
}


// Delete related data first
$conn->prepare("DELETE FROM Ingredients WHERE recipeID = ?")
     ->bind_param("i", $recipeID)
     ->execute();

$conn->prepare("DELETE FROM Instructions WHERE recipeID = ?")
     ->bind_param("i", $recipeID)
     ->execute();

$conn->prepare("DELETE FROM comment WHERE recipeID = ?")
     ->bind_param("i", $recipeID)
     ->execute();

$conn->prepare("DELETE FROM Likes WHERE recipeID = ?")
     ->bind_param("i", $recipeID)
     ->execute();

$conn->prepare("DELETE FROM Favourites WHERE recipeID = ?")
     ->bind_param("i", $recipeID)
     ->execute();

$conn->prepare("DELETE FROM Report WHERE recipeID = ?")
     ->bind_param("i", $recipeID)
     ->execute();


// Delete image file
if (!empty($recipe['photoFileName'])) {
    $imagePath = "images/" . $recipe['photoFileName'];
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
}

// Delete video file
if (!empty($recipe['videoFilePath'])) {
    $videoPath = "videos/" . $recipe['videoFilePath'];
    if (file_exists($videoPath)) {
        unlink($videoPath);
    }
}


// Delete the recipe itself
$sqlDelete = "DELETE FROM Recipe WHERE id = ?";
$stmtDelete = $conn->prepare($sqlDelete);
$stmtDelete->bind_param("i", $recipeID);
$stmtDelete->execute();


// Redirect back to My Recipes page
header("Location: my_recipes.php");
exit();