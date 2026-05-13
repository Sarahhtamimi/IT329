<?php
include 'auth_user.php';
include 'db_connection.php';

header("Content-Type: text/plain");

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "false";
    exit();
}

$recipeID = $_GET['id'];
$userID = $_SESSION['userID'];

$sqlRecipe = "SELECT * FROM Recipe WHERE id = ?";
$stmtRecipe = $conn->prepare($sqlRecipe);
$stmtRecipe->bind_param("i", $recipeID);
$stmtRecipe->execute();
$resultRecipe = $stmtRecipe->get_result();

if ($resultRecipe->num_rows == 0) {
    echo "false";
    exit();
}

$recipe = $resultRecipe->fetch_assoc();

if ($recipe['userID'] == $userID) {
    echo "false";
    exit();
}

$sqlCheck = "SELECT * FROM Report WHERE userID = ? AND recipeID = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("ii", $userID, $recipeID);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck->num_rows > 0) {
    echo "true";
    exit();
}

$sqlInsert = "INSERT INTO Report (userID, recipeID) VALUES (?, ?)";
$stmtInsert = $conn->prepare($sqlInsert);
$stmtInsert->bind_param("ii", $userID, $recipeID);

if ($stmtInsert->execute()) {
    echo "true";
} else {
    echo "false";
}

exit();
?>