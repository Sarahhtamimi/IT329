<?php
session_start();
include 'includes/db_connection.php';

$firstName = trim($_POST['firstName']);
$lastName = trim($_POST['lastName']);
$email = trim($_POST['email']);
$password = $_POST['password'];

if ($firstName == "" || $lastName == "" || $email == "" || $password == "") {
    header("Location: signup.php?error=missing");
    exit();
}

/* check if email exists in User */
$checkUser = "SELECT * FROM User WHERE emailAddress='$email'";
$resultUser = mysqli_query($conn, $checkUser);
if (mysqli_num_rows($resultUser) > 0) {
    header("Location: signup.php?error=email_exists");
    exit();
}

/* check if email exists in BlockedUser */
$checkBlocked = "SELECT * FROM BlockedUser WHERE emailAddress='$email'";
$resultBlocked = mysqli_query($conn, $checkBlocked);
if (mysqli_num_rows($resultBlocked) > 0) {
    header("Location: signup.php?error=blocked");
    exit();
}

/* default photo */
$photo = "Avatar.png";

if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] == 0) {
    $fileName = time() . "_" . basename($_FILES['profileImage']['name']);
    $tmpName = $_FILES['profileImage']['tmp_name'];
    move_uploaded_file($tmpName, "images/" . $fileName);
    $photo = $fileName;
}
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\W).{8,}$/', $password)) {
    header("Location: signup.php?error=weak_password");
    exit();
}
/* hash password */
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

/* insert new user */
$sql = "INSERT INTO User (userType, firstName, lastName, emailAddress, password, photoFileName)
VALUES ('user', '$firstName', '$lastName', '$email', '$hashedPassword', '$photo')";

mysqli_query($conn, $sql);

/* session */
$_SESSION['userID'] = mysqli_insert_id($conn);
$_SESSION['userType'] = "user";

/* redirect */
header("Location: user.php");
exit();
?>