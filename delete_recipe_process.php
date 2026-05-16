<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// return false rather then redirct
if (!isset($_SESSION['userID']) || $_SESSION['userType'] !== 'user') {
    echo "false";
    exit();
}

include 'db_connection.php';

//ID
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo "false";
    exit();
}

$recipeID = (int) $_POST['id'];
$userID   = $_SESSION['userID'];

// retrive recipe
$sql = "SELECT photoFileName, videoFilePath, userID FROM Recipe WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "false";
    exit();
}

$stmt->bind_param("i", $recipeID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "false";
    exit();
}

$recipe = $result->fetch_assoc();

//  user
if ($recipe['userID'] != $userID) {
    echo "false";
    exit();
}

// photo
if (!empty($recipe['photoFileName'])) {
    $imagePath = "images/" . $recipe['photoFileName'];
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
}

// video
if (!empty($recipe['videoFilePath'])) {
    $videoPath = "videos/" . $recipe['videoFilePath'];
    if (file_exists($videoPath)) {
        unlink($videoPath);
    }
}

// delete the recipe 
$deleteStmt = $conn->prepare("DELETE FROM Recipe WHERE id = ?");

if (!$deleteStmt) {
    echo "false";
    exit();
}

$deleteStmt->bind_param("i", $recipeID);

if ($deleteStmt->execute()) {
    echo "true";
} else {
    echo "false";
}

exit();
