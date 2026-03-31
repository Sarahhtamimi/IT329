<?php
include 'auth_user.php';
include 'db_connection.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Recipe ID is missing.");
}

$recipeID = $_GET['id'];
$userID = $_SESSION['userID'];

$sqlRecipe = "SELECT * FROM Recipe WHERE id = ?";
$stmtRecipe = $conn->prepare($sqlRecipe);
$stmtRecipe->bind_param("i", $recipeID);
$stmtRecipe->execute();
$resultRecipe = $stmtRecipe->get_result();

if ($resultRecipe->num_rows == 0) {
    die("Recipe not found.");
}

$recipe = $resultRecipe->fetch_assoc();

if ($recipe['userID'] == $userID) {
    header("Location: view_recipe.php?id=" . $recipeID);
    exit();
}

$sqlCheck = "SELECT * FROM Likes WHERE userID = ? AND recipeID = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("ii", $userID, $recipeID);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck->num_rows == 0) {
    $sqlInsert = "INSERT INTO Likes (userID, recipeID) VALUES (?, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("ii", $userID, $recipeID);
    $stmtInsert->execute();
}

header("Location: view_recipe.php?id=" . $recipeID);
exit();