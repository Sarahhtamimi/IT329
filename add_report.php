<?php
include 'auth_user.php';
include 'db_connection.php';

header("Content-Type: text/plain");

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    print "false";
    exit();
}

$recipeID = $_GET['id'];
$userID = $_SESSION['userID'];

// check that the recipe exists 
$sqlRecipe = "SELECT * FROM Recipe WHERE id = ?";
$stmtRecipe = $conn->prepare($sqlRecipe);
$stmtRecipe->bind_param("i", $recipeID);
$stmtRecipe->execute();
$resultRecipe = $stmtRecipe->get_result();

if ($resultRecipe->num_rows == 0) {
    print "false";
    exit();
}

$recipe = $resultRecipe->fetch_assoc();

// Recipe creator cannot report own recipe 
if ($recipe['userID'] == $userID) {
    print "false";
    exit();
}

// check if its already reported 
$sqlCheck = "SELECT * FROM Report WHERE userID = ? AND recipeID = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("ii", $userID, $recipeID);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck->num_rows > 0) {
    print "true";
    exit();
}

//insert report 
$sqlInsert = "INSERT INTO Report (userID, recipeID) VALUES (?, ?)";
$stmtInsert = $conn->prepare($sqlInsert);
$stmtInsert->bind_param("ii", $userID, $recipeID);

if ($stmtInsert->execute()) {
    print "true";
} else {
    print "false";
}
?>