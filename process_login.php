<?php
session_start();
include 'includes/db_connection.php';

$email = trim($_POST['email']);
$password = $_POST['password'];

if ($email == "" || $password == "") {
    header("Location: login.php?error=missing_fields");
    exit();
}

// Check blocked user
$blockedQuery = "SELECT * FROM `BlockedUser` WHERE emailAddress='$email'";
$blockedResult = mysqli_query($conn, $blockedQuery);

if (mysqli_num_rows($blockedResult) > 0) {
    header("Location: login.php?error=blocked_user");
    exit();
}

// Check if user exists
$userQuery = "SELECT * FROM `User` WHERE emailAddress='$email'";
$userResult = mysqli_query($conn, $userQuery);

if (mysqli_num_rows($userResult) == 0) {
    header("Location: login.php?error=wrong_email");
    exit();
}

$user = mysqli_fetch_assoc($userResult);

// Check password
if (!password_verify($password, $user['PASSWORD'])) {
    header("Location: login.php?error=wrong_password");
    exit();
}

// Save session
$_SESSION['userID'] = $user['id'];
$_SESSION['userType'] = $user['userType'];

// Redirect by user type
if ($user['userType'] == 'admin') {
    header("Location: admin.php");
} else {
    header("Location: user.php");
}
exit();
?>