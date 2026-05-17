<?php
include 'auth_user.php';
include 'db_connection.php';

$category = $_POST['category'];

if ($category == "all") {
    $query = "
        SELECT 
            r.id,
            r.NAME,
            r.photoFileName,
            rc.categoryName,
            u.firstName,
            u.lastName,
            u.photoFileName AS creatorPhoto,
            COUNT(l.userID) AS likesCount
        FROM recipe r
        JOIN user u ON r.userID = u.id
        JOIN recipecategory rc ON r.categoryID = rc.id
        LEFT JOIN likes l ON r.id = l.recipeID
        GROUP BY r.id
    ";
} else {
    $category = mysqli_real_escape_string($conn, $category);

    $query = "
        SELECT 
            r.id,
            r.NAME,
            r.photoFileName,
            rc.categoryName,
            u.firstName,
            u.lastName,
            u.photoFileName AS creatorPhoto,
            COUNT(l.userID) AS likesCount
        FROM recipe r
        JOIN user u ON r.userID = u.id
        JOIN recipecategory rc ON r.categoryID = rc.id
        LEFT JOIN likes l ON r.id = l.recipeID
        WHERE rc.categoryName = '$category'
        GROUP BY r.id
    ";
}

$result = mysqli_query($conn, $query);

$recipes = [];

while ($row = mysqli_fetch_assoc($result)) {
    $recipes[] = [
        "id" => $row['id'],
        "name" => $row['NAME'],
        "photo" => "images/" . $row['photoFileName'],
        "creatorName" => $row['firstName'] . " " . $row['lastName'],
        "creatorPhoto" => !empty($row['creatorPhoto']) ? "images/" . $row['creatorPhoto'] : "images/Avatar.png",
        "likes" => $row['likesCount'],
        "category" => ucwords($row['categoryName']),
        "link" => "view_recipe.php?id=" . $row['id']
    ];
}

echo json_encode($recipes);
?>