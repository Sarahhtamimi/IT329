<?php
include 'auth_any.php';
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    die("Invalid request.");
}

if (!isset($_POST['recipeID']) || !isset($_POST['comment'])) {
    die("Missing data.");
}

$recipeID = $_POST['recipeID'];
$comment = trim($_POST['comment']);
$userID = $_SESSION['userID'];

if ($comment == "") {
    header("Location: view_recipe.php?id=" . $recipeID);
    exit();
}

$sql = "INSERT INTO Comment (recipeID, userID, COMMENT, DATE) VALUES (?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $recipeID, $userID, $comment);
$stmt->execute();

header("Location: view_recipe.php?id=" . $recipeID);
exit();